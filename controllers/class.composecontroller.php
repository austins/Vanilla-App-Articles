<?php
if (!defined('APPLICATION'))
    exit();

/**
 * The controller for the composing of articles.
 */
class ComposeController extends Gdn_Controller {
    /**
     * Models to include.
     */
    public $Uses = array('ArticleModel', 'ArticleCategoryModel', 'ArticleCommentModel', 'Form');

    /**
     * Include JS, CSS, and modules used by all methods.
     * Extended by all other controllers in this application.
     * Always called by dispatcher before controller's requested method.
     */
    public function Initialize() {
        // Set up head.
        $this->Head = new HeadModule($this);

        // Add JS files.
        $this->AddJsFile('jquery.js');
        $this->AddJsFile('jquery.livequery.js');
        $this->AddJsFile('jquery.form.js');
        $this->AddJsFile('jquery.popup.js');
        $this->AddJsFile('jquery.gardenhandleajaxform.js');
        $this->AddJsFile('jquery.autogrow.js');
        $this->AddJsFile('jquery.autocomplete.js');
        $this->AddJsFile('global.js');
        $this->AddJsFile('articles.js');

        // Add CSS files.
        $this->AddCssFile('style.css');
        $this->AddCssFile('articles.css');

        // Add modules.
        $this->AddModule('GuestModule');
        $this->AddModule('SignedInModule');

        parent::Initialize();
    }

    /**
     * This handles the articles dashboard.
     * Only visible to users that have permission.
     */
    public function Index() {
        $this->Title(T('Articles Dashboard'));

        // Set allowed permissions.
        // The user only needs one of the specified permissions.
        $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        $this->Permission($PermissionsAllowed, false);

        // Get recently published articles.
        $RecentlyPublishedOffset = 0;
        $RecentlyPublishedLimit = 5;
        $RecentlyPublishedWheres = array('a.Status' => ArticleModel::STATUS_PUBLISHED);
        $RecentlyPublished = $this->ArticleModel->Get($RecentlyPublishedOffset, $RecentlyPublishedLimit,
            $RecentlyPublishedWheres);
        $this->SetData('RecentlyPublished', $RecentlyPublished);

        // Get recent articles pending review.
        if (Gdn::Session()->CheckPermission('Articles.Articles.Edit')) {
            $PendingArticlesOffset = 0;
            $PendingArticlesLimit = 5;
            $PendingArticlesWheres = array('a.Status' => ArticleModel::STATUS_PENDING);
            $PendingArticles = $this->ArticleModel->Get($PendingArticlesOffset, $PendingArticlesLimit,
                $PendingArticlesWheres);
            $this->SetData('PendingArticles', $PendingArticles);
        }

        $this->View = 'index';
        $this->Render();
    }

    private function GetArticleStatusOptions($Article = false) {
        $StatusOptions = array(
            ArticleModel::STATUS_DRAFT => T('Draft'),
            ArticleModel::STATUS_PENDING => T('Pending Review'),
        );

        if (Gdn::Session()->CheckPermission('Articles.Articles.Edit')
            || ($Article && ((int)$Article->Status == 2))
        )
            $StatusOptions[ArticleModel::STATUS_PUBLISHED] = T('Published');

        return $StatusOptions;
    }

    public function Posts($Page = false) {
        $this->Title(T('Article Posts'));

        // Set allowed permissions.
        // The user only needs one of the specified permissions.
        $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        $this->Permission($PermissionsAllowed, false);

        // Get total article count.
        $CountArticles = $this->ArticleModel->GetCount();
        $this->SetData('CountArticles', $CountArticles);

        // Determine offset from $Page.
        list($Offset, $Limit) = OffsetLimit($Page, C('Articles.Articles.PerPage', 12));
        $Page = PageNumber($Offset, $Limit);
        $this->CanonicalUrl(Url(ConcatSep('/', 'articles', PageNumber($Offset, $Limit, true, false)), true));

        // Have a way to limit the number of pages on large databases
        // because requesting a super-high page can kill the db.
        $MaxPages = C('Articles.Articles.MaxPages', false);
        if ($MaxPages && $Page > $MaxPages) {
            throw NotFoundException();
        }

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->FireEvent('BeforeBuildPager');
        $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->Configure($Offset, $Limit, $CountArticles, 'articles/%1$s');
        if (!$this->Data('_PagerUrl'))
            $this->SetData('_PagerUrl', 'articles/{Page}');
        $this->SetData('_Page', $Page);
        $this->SetData('_Limit', $Limit);
        $this->FireEvent('AfterBuildPager');

        // If the user is not an article editor, then only show their own articles.
        $Session = Gdn::Session();
        $Wheres = false;
        if (!$Session->CheckPermission('Articles.Articles.Edit'))
            $Wheres = array('a.AttributionUserID' => $Session->UserID);

        // Get the articles.
        $Articles = $this->ArticleModel->Get($Offset, $Limit, $Wheres);
        $this->SetData('Articles', $Articles);

        $this->View = 'posts';
        $this->Render();
    }

    public function Article($Article = false) {
        // If not editing...
        if (!$Article) {
            $this->Title(T('Add Article'));

            // Set allowed permission.
            $this->Permission('Articles.Articles.Add');
        }

        // Set the model on the form.
        $this->Form->SetModel($this->ArticleModel);

        // Get categories.
        $Categories = $this->ArticleCategoryModel->Get();
        $this->SetData('Categories', $Categories, true);

        // Set status options.
        $this->SetData('StatusOptions', $this->GetArticleStatusOptions($Article), true);

        $UserModel = new UserModel();

        // The form has not been submitted yet.
        if (!$this->Form->AuthenticatedPostBack()) {
            // If editing...
            if ($Article) {
                $this->Form->SetData($Article);

                $this->Form->AddHidden('UrlCodeIsDefined', '1');

                // Set author field.
                $Author = $UserModel->GetID($Article->AttributionUserID);

                // If the user with AttributionUserID doesn't exist.
                if (!$Author)
                    $Author = $UserModel->GetID($Article->InsertUserID);
            } else {
                // If not editing...
                $this->Form->AddHidden('UrlCodeIsDefined', '0');
            }

            // If the user with InsertUserID doesn't exist.
            if (!$Author)
                $Author = Gdn::Session()->User;

            $this->Form->SetValue('AuthorUserName', $Author->Name);
        } else { // The form has been submitted.
            // Manually validate certain fields.
            $FormValues = $this->Form->FormValues();

            // Validate the URL code.
            // Set UrlCode to name of article if it's not defined.
            if ($FormValues['UrlCode'] == '')
                $FormValues['UrlCode'] = $FormValues['Name'];

            // Format the UrlCode.
            $FormValues['UrlCode'] = Gdn_Format::Url($FormValues['UrlCode']);
            $this->Form->SetFormValue('UrlCode', $FormValues['UrlCode']);

            // If editing, make sure the ArticleID is passed to the form save method.
            $SQL = Gdn::Database()->SQL();
            if ($Article) {
                $this->Form->SetFormValue('ArticleID', (int)$Article->ArticleID);
            }

            // Make sure that the UrlCode is unique among articles.
            $SQL->Select('a.ArticleID')
                ->From('Article a')
                ->Where('a.UrlCode', $FormValues['UrlCode']);

            if ($Article)
                $SQL->Where('a.ArticleID <>', $Article->ArticleID);

            $UrlCodeExists = isset($SQL->Get()->FirstRow()->ArticleID);

            if ($UrlCodeExists)
                $this->Form->AddError('The specified URL code is already in use by another article.', 'UrlCode');

            // Retrieve author user ID.
            if ($FormValues['AuthorUserName'] !== "")
                $Author = $UserModel->GetByUsername($FormValues['AuthorUserName']);

            // If the inputted author doesn't exist.
            if (!$Author) {
                $Session = Gdn::Session();

                if (!$Session->CheckPermission('Articles.Articles.Edit') && ($FormValues['AuthorUserName'] == ""))
                    $Author = $Session->User;
                else if ($FormValues['AuthorUserName'] == "")
                    $this->Form->AddError('Author is required.', 'AuthorUserName');
                else
                    $this->Form->AddError('The user for the author field does not exist.', 'AuthorUserName');
            }

            $this->Form->SetFormValue('AttributionUserID', (int)$Author->UserID);

            if ($this->Form->ErrorCount() == 0) {
                $ArticleID = $this->Form->Save($FormValues);

                // If the article was saved successfully.
                if ($ArticleID) {
                    // If editing and the author has changed from the initial article, then
                    // update the counts for the initial author after the article has been saved.
                    if ($Article) {
                        $InitialAttributionUserID = GetValue('AttributionUserID', $Article, false);

                        if ($InitialAttributionUserID != $Author->UserID) {
                            $this->ArticleModel->UpdateUserArticleCount($InitialAttributionUserID);

                            // Update the count for the new author.
                            $this->ArticleModel->UpdateUserArticleCount($Author->UserID);
                        }
                    }

                    $Article = $this->ArticleModel->GetByID($ArticleID);

                    // Redirect to the article.
                    if ($this->_DeliveryType == DELIVERY_TYPE_ALL)
                        Redirect(ArticleUrl($Article));
                    else
                        $this->RedirectUrl = ArticleUrl($Article, '', true);
                }
            }
        }

        $this->View = 'article';
        $this->Render();
    }

    public function EditArticle($ArticleID = false) {
        $this->Title(T('Edit Article'));

        // Set allowed permission.
        $this->Permission('Articles.Articles.Edit');

        // Get article.
        if (is_numeric($ArticleID))
            $Article = $this->ArticleModel->GetByID($ArticleID);

        // If the article doesn't exist, then throw an exception.
        if (!$Article)
            throw NotFoundException('Article');

        $this->SetData('Article', $Article, true);

        // Get category.
        $Category = $this->ArticleCategoryModel->GetByID($Article->CategoryID);
        $this->SetData('Category', $Category, true);

        $this->View = 'article';
        $this->Article($Article);
    }

    public function Comment($ArticleID, $ParentCommentID = false) {
        $this->Title(T('Post Article Comment'));

        // Set required permission.
        $GuestCommenting = false;
        $Session = Gdn::Session();

        if(!is_numeric($ArticleID))
            throw NotFoundException('Article');

        // Get the article.
        $Article = $this->ArticleModel->GetByID($ArticleID);

        // Determine whether we are editing.
        $CommentID = isset($this->Comment) && property_exists($this->Comment, 'CommentID') ? $this->Comment->CommentID : false;
        $this->EventArguments['CommentID'] = &$CommentID;
        $Editing = ($CommentID > 0);

        // If closed, cancel and go to article.
        if ($Article && $Article->Closed == 1 && !$Editing && !$Session->CheckPermission('Articles.Articles.Close'))
            Redirect(ArticleUrl($Article));

        // Add hidden IDs to form.
        $this->Form->AddHidden('ArticleID', $ArticleID);
        $this->Form->AddHidden('CommentID', $CommentID);

        // Check permissions.
        if ($Session->IsValid()) {
            if ($Article && $Editing) {
                // Permission to edit
                if ($this->Comment->InsertUserID != $Session->UserID)
                    $this->Permission('Articles.Comments.Edit');

                // Make sure that content can (still) be edited.
                $EditContentTimeout = C('Garden.EditContentTimeout', -1);
                $CanEdit = $EditContentTimeout == -1 || strtotime($this->Comment->DateInserted) + $EditContentTimeout > time();
                if (!$CanEdit)
                    $this->Permission('Articles.Comments.Edit');

                // Make sure only moderators can edit closed things
                if ($Article->Closed)
                    $this->Permission('Articles.Comments.Edit');
            } else if ($Article) {
                // Permission to add
                $this->Permission('Articles.Comments.Add');
            }
        }

        // Set the model on the form.
        $this->Form->SetModel($this->ArticleCommentModel);

        if (!$this->Form->IsPostBack()) {
            if (isset($this->Comment)) {
                $this->Form->SetData((array)$this->Comment);
            }
        } else {
            // Form was validly submitted.
            // Validate fields.
            $FormValues = $this->Form->FormValues();

            $this->Form->ValidateRule('Body', 'ValidateRequired');

            // Set article ID.
            $FormValues['ArticleID'] = $ArticleID;
            $this->Form->SetFormValue('ArticleID', $FormValues['ArticleID']);

            // If the form didn't have ParentCommentID set, then set it to the method argument as a default.
            if(!is_numeric($FormValues['ParentCommentID'])) {
                $ParentCommentID = is_numeric($ParentCommentID) ? $ParentCommentID : null;

                $FormValues['ParentCommentID'] = $ParentCommentID;
                $this->Form->SetFormValue('ParentCommentID', $ParentCommentID);
            }

            // Validate parent comment.
            $ParentComment = false;
            if(is_numeric($FormValues['ParentCommentID'])) {
                $ParentComment = $this->ArticleCommentModel->GetByID($FormValues['ParentCommentID']);

                // Parent comment doesn't exist.
                if(!$ParentComment)
                    throw NotFoundException('Parent comment');

                // Only allow one level of threading.
                if(is_numeric($ParentComment->ParentCommentID) && ($ParentComment->ParentCommentID > 0))
                    throw ForbiddenException('reply to a comment more than one level down');
            }

            // If the user is signed in, then nullify the guest properties.
            if (!$Editing) {
                $GuestCommenting = (C('Articles.Comments.AllowGuests', false) && !$Session->IsValid());

                if (!$GuestCommenting) {
                    $FormValues['GuestName'] = null;
                    $FormValues['GuestEmail'] = null;
                } else {
                    // The InsertUserID should be null for inserting a guest comment.
                    $FormValues['InsertUserID'] = null;
                    $this->Form->SetFormValue('InsertUserID', $FormValues['InsertUserID']);

                    // Require the guest fields.
                    $this->Form->ValidateRule('GuestName', 'ValidateRequired', T('Guest name is required.'));
                    $this->Form->ValidateRule('GuestEmail', 'ValidateRequired', T('Guest email is required.'));
                    $this->Form->ValidateRule('GuestEmail', 'ValidateEmail', T('That email address is not valid.'));

                    // Sanitize the guest properties.
                    $FormValues['GuestName'] = Gdn_Format::PlainText($FormValues['GuestName']);
                    $FormValues['GuestEmail'] = Gdn_Format::PlainText($FormValues['GuestEmail']);
                }

                $this->Form->SetFormValue('GuestName', $FormValues['GuestName']);
                $this->Form->SetFormValue('GuestEmail', $FormValues['GuestEmail']);
            }

            if ($this->Form->ErrorCount() > 0) {
                // Return the form errors.
                $this->ErrorMessage($this->Form->Errors());
            } else {
                // There are no form errors.
                if ($this->Form->Save($FormValues)) {
                    $this->RedirectUrl = ArticleUrl($Article);
                }
            }
        }

        if (!$Editing)
            $this->View = 'comment';

        $this->Render();
    }

    /**
     * Edit a comment (wrapper for the Comment method).
     *
     * @param int $CommentID Unique ID of the comment to edit.
     */
    public function EditComment($CommentID = '') {
        if (!is_numeric($CommentID))
            throw new InvalidArgumentException('The comment ID must be a numeric value.');

        if ($CommentID > 0)
            $this->Comment = $this->ArticleCommentModel->GetByID($CommentID);

        $this->Form->SetFormValue('Format', GetValue('Format', $this->Comment));

        $this->View = 'editcomment';

        $ParentCommentID = null;
        if (!is_numeric($this->Comment->ParentCommentID) && ($this->Comment->ParentCommentID > 0))
            $ParentCommentID = $this->Comment->ParentCommentID;

        $this->Comment($this->Comment->ArticleID, $ParentCommentID);
    }
}
