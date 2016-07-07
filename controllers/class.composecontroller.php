<?php defined('APPLICATION') or exit();
/**
 * Copyright (C) 2015  Austin S.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * The controller for the composing of articles.
 */
class ComposeController extends Gdn_Controller {
    /**
     * Models to include.
     */
    public $Uses = array('ArticleModel', 'ArticleCategoryModel', 'ArticleCommentModel', 'ArticleMediaModel', 'Form');

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
        $this->AddJsFile('articles.compose.js');

        // Add CSS files.
        $this->AddCssFile('style.css');
        $this->AddCssFile('articles.css');
        $this->AddCssFile('articles.compose.css');

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
        $this->Permission($PermissionsAllowed, false, 'ArticleCategory', 'any');

        $this->AddModule('ComposeFilterModule');

        // Get recently published articles.
        $RecentlyPublishedOffset = 0;
        $RecentlyPublishedLimit = 5;
        $RecentlyPublishedWheres = array('a.Status' => ArticleModel::STATUS_PUBLISHED);
        $RecentlyPublished = $this->ArticleModel->Get($RecentlyPublishedOffset, $RecentlyPublishedLimit,
            $RecentlyPublishedWheres);
        $this->SetData('RecentlyPublished', $RecentlyPublished);

        // Get recent article comments.
        $RecentCommentsOffset = 0;
        $RecentCommentsLimit = 5;
        $RecentComments = $this->ArticleCommentModel->Get($RecentCommentsOffset,
            $RecentCommentsLimit, null, 'desc');
        $this->SetData('RecentComments', $RecentComments);

        // Get recent articles pending review.
        if (Gdn::Session()->CheckPermission('Articles.Articles.Edit', true, 'ArticleCategory', 'any')) {
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

    /**
     * Retrieves status options for an article.
     *
     * @param bool|object $Article entity
     * @return array
     */
    private function GetArticleStatusOptions($Article = false) {
        $StatusOptions = array(
            ArticleModel::STATUS_DRAFT => T('Draft'),
            ArticleModel::STATUS_PENDING => T('Pending Review'),
        );

        if (Gdn::Session()->CheckPermission('Articles.Articles.Edit', true, 'ArticleCategory', 'any')
            || ($Article && ($Article->Status == ArticleModel::STATUS_PUBLISHED))
        ) {
            $StatusOptions[ArticleModel::STATUS_PUBLISHED] = T('Published');
        }

        return $StatusOptions;
    }

    /**
     * Listing of articles.
     *
     * @param bool|object $Page entity
     * @throws NotFoundException if no articles found
     */
    public function Posts($Page = false) {
        $this->Title(T('Article Posts'));

        // Set allowed permissions.
        // The user only needs one of the specified permissions.
        $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        $this->Permission($PermissionsAllowed, false, 'ArticleCategory', 'any');

        $this->SetData('Breadcrumbs', array(
            array('Name' => T('Compose'), 'Url' => '/compose'),
            array('Name' => T('Posts'), 'Url' => '/compose/posts')
        ));

        $this->AddModule('ComposeFilterModule');

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
        if (!$this->Data('_PagerUrl')) {
            $this->SetData('_PagerUrl', 'articles/{Page}');
        }
        $this->SetData('_Page', $Page);
        $this->SetData('_Limit', $Limit);
        $this->FireEvent('AfterBuildPager');

        // If the user is not an article editor, then only show their own articles.
        $Session = Gdn::Session();
        $Wheres = false;
        if (!$Session->CheckPermission('Articles.Articles.Edit', true, 'ArticleCategory', 'any')) {
            $Wheres = array('a.InsertUserID' => $Session->UserID);
        }

        // Get the articles.
        $Articles = $this->ArticleModel->Get($Offset, $Limit, $Wheres);
        $this->SetData('Articles', $Articles);

        $this->View = 'posts';
        $this->Render();
    }

    /**
     * Allows the user to create an article.
     *
     * @param bool|object $Article entity
     * @throws Gdn_UserException if a category isn't selected
     */
    public function Article($Article = false) {
        // If not editing...
        if (!$Article) {
            $this->Title(T('Add Article'));

            // Set allowed permission.
            $this->Permission('Articles.Articles.Add', true, 'ArticleCategory', 'any');
        }

        $this->SetData('Breadcrumbs', array(
            array('Name' => T('Compose'), 'Url' => '/compose'),
            array('Name' => T('New Article'), 'Url' => '/compose/article')
        ));

        // Set the model on the form.
        $this->Form->SetModel($this->ArticleModel);

        $this->AddJsFile('jquery.ajaxfileupload.js');

        // Get categories.
        $Categories = $this->ArticleCategoryModel->Get();

        if ($Categories->NumRows() === 0) {
            throw new Gdn_UserException(T('At least one article category must exist to compose an article.'));
        }

        $this->SetData('Categories', $Categories, true);

        // Set status options.
        $this->SetData('StatusOptions', $this->GetArticleStatusOptions($Article), true);

        $UserModel = new UserModel();
        $Author = false;
        $Preview = false;

        // The form has not been submitted yet.
        if (!$this->Form->AuthenticatedPostBack()) {
            // If editing...
            if ($Article) {
                $this->AddDefinition('ArticleID', $Article->ArticleID);
                $this->SetData('Article', $Article, true);
                $this->Form->SetData($Article);

                $this->Form->AddHidden('UrlCodeIsDefined', '1');

                // Set author field.
                $Author = $UserModel->GetID($Article->InsertUserID);

                $UploadedImages = $this->ArticleMediaModel->GetByArticleID($Article->ArticleID);
                $this->SetData('UploadedImages', $UploadedImages, true);

                $UploadedThumbnail = $this->ArticleMediaModel->GetThumbnailByArticleID($Article->ArticleID);
                $this->SetData('UploadedThumbnail', $UploadedThumbnail, true);
            } else {
                // If not editing...
                $this->Form->AddHidden('UrlCodeIsDefined', '0');
            }

            // If the user with InsertUserID doesn't exist.
            if (!$Author) {
                $Author = Gdn::Session()->User;
            }

            $this->Form->SetValue('AuthorUserName', $Author->Name);
        } else { // The form has been submitted.
            // Manually validate certain fields.
            $FormValues = $this->Form->FormValues();

            $this->Form->ValidateRule('ArticleCategoryID', 'ValidateRequired', T('Article category is required.'));

            // Validate the URL code.
            // Set UrlCode to name of article if it's not defined.
            if ($FormValues['UrlCode'] == '') {
                $FormValues['UrlCode'] = $FormValues['Name'];
            }

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

            if ($Article) {
                $SQL->Where('a.ArticleID <>', $Article->ArticleID);
            }

            $UrlCodeExists = isset($SQL->Get()->FirstRow()->ArticleID);

            if ($UrlCodeExists) {
                $this->Form->AddError('The specified URL code is already in use by another article.', 'UrlCode');
            }

            // Retrieve author user ID.
            if ($FormValues['AuthorUserName'] !== "") {
                $Author = $UserModel->GetByUsername($FormValues['AuthorUserName']);
            }

            // If the inputted author doesn't exist.
            if (!$Author) {
                $Session = Gdn::Session();

                $Category = ArticleCategoryModel::Categories($FormValues['ArticleCategoryID']);
                $PermissionArticleCategoryID = val('PermissionArticleCategoryID', $Category, 'any');
                if (!$Session->CheckPermission('Articles.Articles.Edit', true, 'ArticleCategory', $PermissionArticleCategoryID)
                        && ($FormValues['AuthorUserName'] == "")) {
                    // Set author to current user if current user does not have Edit permission.
                    $Author = $Session->User;
                } else {
                    // Show friendly error messages for author field if user has Edit permission.
                    if ($FormValues['AuthorUserName'] == "") {
                        $this->Form->AddError('Author is required.', 'AuthorUserName');
                    } else {
                        $this->Form->AddError('The user for the author field does not exist.', 'AuthorUserName');
                    }
                }
            }

            $this->Form->SetFormValue('InsertUserID', (int)$Author->UserID);

            // If this was a preview click, create an article shell with the values for this article.
            $Preview = $this->Form->ButtonExists('Preview') ? true : false;
            if ($Preview) {
                $this->Article = new stdClass();
                $this->Article->Name = $this->Form->GetValue('Name', '');
                $this->Preview = new stdClass();
                $this->Preview->InsertUserID = isset($Author->UserID) ? $Author->UserID : $Session->User->UserID;
                $this->Preview->InsertName = $Session->User->Name;
                $this->Preview->InsertPhoto = $Session->User->Photo;
                $this->Preview->DateInserted = Gdn_Format::Date();
                $this->Preview->Body = ArrayValue('Body', $FormValues, '');
                $this->Preview->Format = GetValue('Format', $FormValues, C('Garden.InputFormatter'));

                $this->EventArguments['Article'] = &$this->Article;
                $this->EventArguments['Preview'] = &$this->Preview;
                $this->FireEvent('BeforeArticlePreview');

                if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                    $this->AddAsset('Content', $this->FetchView('preview'));
                } else {
                    $this->View = 'preview';
                }
            } else {
                if ($this->Form->ErrorCount() == 0) {
                    $ArticleID = $this->Form->Save($FormValues);

                    // If the article was saved successfully.
                    if ($ArticleID) {
                        $NewArticle = $this->ArticleModel->GetByID($ArticleID);

                        // If editing.
                        if ($Article) {
                            // If the author has changed from the initial article, then update the counts
                            // for the initial author after the article has been saved.
                            $InitialInsertUserID = val('InsertUserID', $Article, false);

                            if ($InitialInsertUserID != $Author->UserID) {
                                $this->ArticleModel->UpdateUserArticleCount($InitialInsertUserID);

                                // Update the count for the new author.
                                $this->ArticleModel->UpdateUserArticleCount($Author->UserID);
                            }

                            // If the status has changed from non-published to published, then update the DateInserted date.
                            $InitialStatus = val('Status', $Article, false);

                            if (($InitialStatus != ArticleModel::STATUS_PUBLISHED)
                                && ($NewArticle->Status == ArticleModel::STATUS_PUBLISHED)
                            ) {
                                $this->ArticleModel->SetField($ArticleID, 'DateInserted', Gdn_Format::ToDateTime());
                            }

                            // Set thumbnail ID.
                            $UploadedThumbnail = $this->ArticleMediaModel->GetThumbnailByArticleID($Article->ArticleID);
                            if (is_object($UploadedThumbnail) && ($UploadedThumbnail->ArticleMediaID > 0)) {
                                $this->ArticleModel->SetField($ArticleID, 'ThumbnailID', $UploadedThumbnail->ArticleMediaID);
                            }
                        } else {
                            // If not editing.
                            // Assign the new article's ID to any uploaded images.
                            $UploadedImageIDs = $FormValues['UploadedImageIDs'];
                            if (is_array($UploadedImageIDs)) {
                                foreach ($UploadedImageIDs as $ArticleMediaID) {
                                    $this->ArticleMediaModel->SetField($ArticleMediaID, 'ArticleID', $ArticleID);
                                }
                            }

                            // Set thumbnail ID.
                            $UploadedThumbnailID = (int)$FormValues['UploadedThumbnailID'];
                            if ($UploadedThumbnailID > 0) {
                                $this->ArticleModel->SetField($ArticleID, 'ThumbnailID', $UploadedThumbnailID);
                                $this->ArticleMediaModel->SetField($UploadedThumbnailID, 'ArticleID', $ArticleID);
                            }
                        }

                        // Redirect to the article.
                        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                            Redirect(ArticleUrl($NewArticle));
                        } else {
                            $this->RedirectUrl = ArticleUrl($NewArticle, '', true);
                        }
                    }
                }
            }
        }

        if (!$Preview) {
            $this->View = 'article';
        }

        $this->CssClass = 'NoPanel';

        $this->Render();
    }

    /**
     * Allows the user to edit an article.
     * Wrapper for Article() method.
     *
     * @param bool|object $Article entity
     * @throws NotFoundException if article not found
     */
    public function EditArticle($ArticleID = false) {
        $this->Title(T('Edit Article'));

        // Get article.
        if (is_numeric($ArticleID)) {
            $Article = $this->ArticleModel->GetByID($ArticleID);
        }

        // If the article doesn't exist, then throw an exception.
        if (!$Article) {
            throw NotFoundException('Article');
        }

        // Set allowed permission.
        $this->Permission('Articles.Articles.Edit', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);

        $this->SetData('Article', $Article, true);

        // Get category.
        $Category = $this->ArticleCategoryModel->GetByID($Article->ArticleCategoryID);
        $this->SetData('Category', $Category, true);

        $this->View = 'article';
        $this->Article($Article);
    }

    /**
     * Allows the user to upload an image to an article via AJAX.
     *
     * @return false on failure
     * @throws NotFoundException if no files posted
     * @throws PermissionException if user doesn't have permission to upload
     */
    public function UploadImage() {
        // Check for file data.
        if (!$_FILES) {
            throw NotFoundException('Page');
        }

        // Handle the file data.
        $this->DeliveryMethod(DELIVERY_METHOD_JSON);
        $this->DeliveryType(DELIVERY_TYPE_VIEW);

        // ArticleID is saved with media model if editing. ArticleID is null if new article.
        // Null ArticleID is replaced by ArticleID when new article is saved.
        $ArticleID = $_POST['ArticleID'];
        if (!is_numeric($ArticleID) || ($ArticleID <= 0)) {
            $ArticleID = null;
        }

        // Check permission.
        $Session = Gdn::Session();
        $PermissionArticleCategoryID = 'any';
        if (is_numeric($ArticleID)) {
            $ArticleModel = new ArticleModel();

            $Article = $ArticleModel->GetByID($ArticleID);
            if ($Article) {
                $PermissionArticleCategoryID = $Article->PermissionArticleCategoryID;
            }
        }
        if (!$Session->CheckPermission('Articles.Articles.Add', true, 'ArticleCategory', $PermissionArticleCategoryID)) {
            throw PermissionException('Articles.Articles.Add');
        }

        /*
         * $_FILES['UploadImage_New'] array format:
         *  'name' => 'example.jpg',
         *  'type' => 'image/jpeg',
         *  'tmp_name' => 'C:\example\tmp\example.tmp' (temp. path on the user's computer to .tmp file)
         *  'error' => 0 (valid data)
         *  'size' => 15517 (bytes)
         */
        //$ImageData = $_FILES[$UploadFieldName];
        $isThumbnail = false;
        if (isset($_FILES['UploadThumbnail_New'])) {
            $UploadFieldName = 'UploadThumbnail_New';
            $isThumbnail = true;
        } else {
            $UploadFieldName = 'UploadImage_New';
        }

        // Upload the image.
        $UploadImage = new Gdn_UploadImage();
        try {
            $TmpFileName = $UploadImage->ValidateUpload($UploadFieldName);

            // Generate the target image name.
            $CurrentYear = date('Y');
            $CurrentMonth = date('m');
            $UploadPath = PATH_UPLOADS . '/articles/' . $CurrentYear . '/' . $CurrentMonth;
            $TargetImage = $UploadImage->GenerateTargetName($UploadPath, false, false);
            $Basename = pathinfo($TargetImage, PATHINFO_BASENAME);
            $Extension = trim(pathinfo($TargetImage, PATHINFO_EXTENSION), '.');
            $UploadsSubdir = '/articles/' . $CurrentYear . '/' . $CurrentMonth;

            if ($isThumbnail) {
                $SaveWidth = C('Articles.Articles.ThumbnailWidth', 280);
                $SaveHeight = C('Articles.Articles.ThumbnailHeight', 200);
            } else {
                $SaveWidth = null;
                $SaveHeight = null;
            }

            // Save the uploaded image.
            $Props = $UploadImage->SaveImageAs(
                $TmpFileName,
                $UploadsSubdir . '/' . $Basename,
                $SaveHeight,
                $SaveWidth, // change these configs and add quality etc.
                array('OutputType' => $Extension, 'ImageQuality' => C('Garden.UploadImage.Quality', 75))
            );

            $UploadedImagePath = sprintf($Props['SaveFormat'], $UploadsSubdir . '/' . $Basename);
        } catch (Exception $ex) {
            return false;
        }

        // Save the image.
        $ImageProps = getimagesize($TargetImage);
        $MediaValues = array(
            'ArticleID' => $ArticleID,
            'Name' => $Basename,
            'Type' => $ImageProps['mime'],
            'Size' => filesize($TargetImage),
            'ImageWidth' => $ImageProps[0],
            'ImageHeight' => $ImageProps[1],
            'StorageMethod' => 'local',
            'IsThumbnail' => $isThumbnail,
            'Path' => $UploadedImagePath,
            'DateInserted' => Gdn_Format::ToDateTime(),
            'InsertUserID' => $Session->UserID,
        );

        $ArticleMediaID = $this->ArticleMediaModel->Save($MediaValues);

        // Return path to the uploaded image in the following format.
        // Example: '/articles/year/month/filename.jpg'
        $JsonData = array(
            'ArticleMediaID' => $ArticleMediaID,
            'Name' => $Basename,
            'Path' => $UploadedImagePath
        );

        $JsonReturn = json_encode($JsonData);

        die($JsonReturn);
    }

    /**
     * Allows the user to delete an image from an article.
     *
     * @param int $ArticleMediaID
     * @throws NotFoundException if invalid ArticleMediaID
     * @throws PermissionException if user doesn't have permission to upload
     */
    public function DeleteImage($ArticleMediaID) {
        if (!is_numeric($ArticleMediaID)
            || ($this->_DeliveryMethod != DELIVERY_METHOD_JSON) || ($this->_DeliveryType != DELIVERY_TYPE_BOOL)
        ) {
            throw NotFoundException('Page');
        }

        $Media = $this->ArticleMediaModel->GetByID($ArticleMediaID);
        if (!$Media) {
            throw NotFoundException('Article media');
        }

        // Check permission.
        $Session = Gdn::Session();
        $PermissionArticleCategoryID = 'any';
        if (is_numeric($Media->ArticleID)) {
            $ArticleModel = new ArticleModel();

            $Article = $ArticleModel->GetByID($Media->ArticleID);
            if ($Article) {
                $PermissionArticleCategoryID = $Article->PermissionArticleCategoryID;
            }
        }
        if (!$Session->CheckPermission('Articles.Articles.Add', true, 'ArticleCategory', $PermissionArticleCategoryID)) {
            throw PermissionException('Articles.Articles.Add');
        }

        $this->_DeliveryMethod = DELIVERY_METHOD_JSON;
        $this->_DeliveryType = DELIVERY_TYPE_BOOL;

        // Delete the image from the database.
        $Deleted = $this->ArticleMediaModel->Delete($ArticleMediaID);

        // Delete the image file.
        $ImagePath = PATH_UPLOADS . DS . val('Path', $Media);
        if (file_exists($ImagePath)) {
            @unlink($ImagePath);
        }

        $this->Render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Allows the user to comment on an article.
     *
     * @param int $ArticleID
     * @param bool $ParentArticleCommentID
     * @throws NotFoundException if ArticleID not found.
     * @throws ForbiddenException if invalid reply
     */
    public function Comment($ArticleID, $ParentArticleCommentID = false) {
        $this->Title(T('Post Article Comment'));

        // Set required permission.
        $Session = Gdn::Session();

        // Get the article.
        $Article = $this->ArticleModel->GetByID($ArticleID);

        // Determine if this is a guest commenting
        $GuestCommenting = false;
        if (!$Session->IsValid()) { // Not logged in, so this could be a guest
            if (C('Articles.Comments.AllowGuests', false)) { // If guest commenting is enabled
                $GuestCommenting = true;
            } else { // Require permission to add comment
                $this->Permission('Articles.Comments.Add', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);
            }
        }

        // Determine whether we are editing.
        $ArticleCommentID = isset($this->Comment) && property_exists($this->Comment, 'ArticleCommentID') ? $this->Comment->ArticleCommentID : false;
        $this->EventArguments['ArticleCommentID'] = &$ArticleCommentID;
        $Editing = ($ArticleCommentID > 0);

        // If closed, cancel and go to article.
        if ($Article && $Article->Closed == 1 && !$Editing
                && !$Session->CheckPermission('Articles.Articles.Close', true, 'ArticleCategory', $Article->PermissionArticleCategoryID)) {
            Redirect(ArticleUrl($Article));
        }

        // Add hidden IDs to form.
        $this->Form->AddHidden('ArticleID', $ArticleID);
        $this->Form->AddHidden('ArticleCommentID', $ArticleCommentID);

        // Check permissions.
        if ($Session->IsValid()) {
            if ($Article && $Editing) {
                // Permission to edit
                if ($this->Comment->InsertUserID != $Session->UserID) {
                    $this->Permission('Articles.Comments.Edit', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);
                }

                // Make sure that content can (still) be edited.
                $EditContentTimeout = C('Garden.EditContentTimeout', -1);
                $CanEdit = $EditContentTimeout == -1 || strtotime($this->Comment->DateInserted) + $EditContentTimeout > time();
                if (!$CanEdit) {
                    $this->Permission('Articles.Comments.Edit', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);
                }

                // Make sure only moderators can edit closed things
                if ($Article->Closed) {
                    $this->Permission('Articles.Comments.Edit', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);
                }
            } else {
                if ($Article) {
                    // Permission to add
                    $this->Permission('Articles.Comments.Add', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);
                }
            }
        }

        // Set the model on the form.
        $this->Form->SetModel($this->ArticleCommentModel);

        if (!$this->Form->AuthenticatedPostBack()) {
            if (isset($this->Comment)) {
                $this->Form->SetData((array)$this->Comment);
            }
        } else {
            // Form was validly submitted.
            // Validate fields.
            $FormValues = $this->Form->FormValues();

            $Type = GetIncomingValue('Type');
            $Preview = ($Type == 'Preview');

            $this->Form->ValidateRule('Body', 'ValidateRequired');

            // Set article ID.
            $FormValues['ArticleID'] = $ArticleID;
            $this->Form->SetFormValue('ArticleID', $FormValues['ArticleID']);

            // If the form didn't have ParentArticleCommentID set, then set it to the method argument as a default.
            if (!is_numeric($FormValues['ParentArticleCommentID'])) {
                $ParentArticleCommentID = is_numeric($ParentArticleCommentID) ? $ParentArticleCommentID : null;

                $FormValues['ParentArticleCommentID'] = $ParentArticleCommentID;
                $this->Form->SetFormValue('ParentArticleCommentID', $ParentArticleCommentID);
            }

            // Validate parent comment.
            $ParentComment = false;
            if (is_numeric($FormValues['ParentArticleCommentID'])) {
                $ParentComment = $this->ArticleCommentModel->GetByID($FormValues['ParentArticleCommentID']);

                // Parent comment doesn't exist.
                if (!$ParentComment) {
                    throw NotFoundException('Parent comment');
                }

                // Only allow one level of threading.
                if (is_numeric($ParentComment->ParentArticleCommentID) && ($ParentComment->ParentArticleCommentID > 0)) {
                    throw ForbiddenException('reply to a comment more than one level down');
                }
            }

            // If the user is signed in, then nullify the guest properties.
            if (!$Editing) {
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
                if ($Preview) {
                    // If this was a preview click, create a comment shell with the values for this comment
                    $this->Preview = new stdClass();
                    $this->Preview->InsertUserID = $Session->User->UserID;
                    $this->Preview->InsertName = $Session->User->Name;
                    $this->Preview->InsertPhoto = $Session->User->Photo;
                    $this->Preview->DateInserted = Gdn_Format::Date();
                    $this->Preview->Body = ArrayValue('Body', $FormValues, '');

                    if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                        $this->AddAsset('Content', $this->FetchView('preview'));
                        $this->Preview->Format = GetValue('Format', $FormValues, C('Garden.InputFormatter'));
                    } else {
                        $this->View = 'preview';
                    }
                } else {
                    $CommentID = $this->Form->Save($FormValues);

                    if ($CommentID) {
                        $this->RedirectUrl = ArticleCommentUrl($CommentID);
                    }
                }
            }
        }

        if (!$Editing && !$Preview) {
            $this->View = 'comment';
        }

        $this->Render();
    }

    /**
     * Edit a comment (wrapper for the Comment method).
     *
     * @param int $ArticleCommentID Unique ID of the comment to edit.
     */
    public function EditComment($ArticleCommentID = '') {
        if (!is_numeric($ArticleCommentID)) {
            throw new InvalidArgumentException('The comment ID must be a numeric value.');
        }

        if ($ArticleCommentID > 0) {
            $this->Comment = $this->ArticleCommentModel->GetByID($ArticleCommentID);
        }

        $this->Form->SetFormValue('Format', val('Format', $this->Comment));

        $this->View = 'editcomment';

        $ParentArticleCommentID = null;
        if (!is_numeric($this->Comment->ParentArticleCommentID) && ($this->Comment->ParentArticleCommentID > 0)) {
            $ParentArticleCommentID = $this->Comment->ParentArticleCommentID;
        }

        $this->Comment($this->Comment->ArticleID, $ParentArticleCommentID);
    }
}
