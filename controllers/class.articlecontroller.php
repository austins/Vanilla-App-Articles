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
 * The controller for an article.
 */
class ArticleController extends Gdn_Controller {
    /**
     * Models to include.
     */
    public $Uses = array('ArticleModel', 'ArticleCategoryModel', 'ArticleCommentModel', 'ArticleMediaModel', 'Form');

    public $Article = false;
    protected $ArticleCategory = false;
    protected $ArticleComments = false;

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
        $this->AddJsFile('global.js');
        $this->AddJsFile('articles.js');

        // Add CSS files.
        $this->AddCssFile('style.css');
        $this->AddCssFile('articles.css');

        // Add CSS file for mobile theme if active.
        if (Gdn::ThemeManager()->CurrentTheme() === 'mobile') {
            $this->AddCssFile('articles.mobile.css');
        }

        // Add modules.
        $this->AddModule('GuestModule');
        $this->AddModule('SignedInModule');
        $this->AddModule('ArticlesDashboardModule');
        $this->AddModule('ArticleCategoriesModule');
        $this->AddModule('RecentActivityModule');

        parent::Initialize();
    }

    /**
     * Main method of an article.
     *
     * @param int $ArticleYear in YYYY format
     * @param string $ArticleUrlCode is a unique code
     * @param bool|object $Page is a page entity
     * @throws NotFoundException if article not found
     */
    public function Index($ArticleYear, $ArticleUrlCode, $Page = false) {
        // Get the article.
        $this->Article = $this->ArticleModel->GetByUrlCode($ArticleUrlCode);

        if (!$this->Article)
            throw NotFoundException('Article');

        // Set required permission.
        // If not published...
        if ($this->Article->Status != ArticleModel::STATUS_PUBLISHED) {
            // If author, only require View permission.
            if ($this->Article->InsertUserID == Gdn::Session()->UserID) {
                $this->Permission('Articles.Articles.View', true, 'ArticleCategory', $this->Article->PermissionArticleCategoryID);
            } else {
                $this->Permission('Articles.Articles.Edit', true, 'ArticleCategory', $this->Article->PermissionArticleCategoryID);
            }
        } else {
            $this->Permission('Articles.Articles.View', true, 'ArticleCategory', $this->Article->PermissionArticleCategoryID);
        }

        // Get the category.
        $this->ArticleCategory = $this->ArticleCategoryModel->GetByID($this->Article->ArticleCategoryID);
        $this->SetData('ArticleCategory', $this->ArticleCategory);
        $this->SetData('Breadcrumbs', array(
            array('Name' => $this->ArticleCategory->Name, 'Url' => ArticleCategoryUrl($this->ArticleCategory))
        ));

        // Prepare comment arguments.
        // Define the query offset and limit.
        $Limit = C('Articles.Comments.PerPage', 30);
        list($Offset, $Limit) = OffsetLimit($Page, $Limit);

        $PageNumber = PageNumber($Offset, $Limit);
        $this->SetData('Page', $PageNumber);

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->FireEvent('BeforeBuildPager');
        $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';

        $this->Pager->Configure(
            $Offset,
            $Limit,
            $this->Article->CountArticleComments,
            array('ArticleUrl')
        );
        $this->Pager->Record = $this->Article;
        PagerModule::Current($this->Pager);
        $this->FireEvent('AfterBuildPager');

        // Set canonical URL.
        $this->CanonicalUrl(ArticleUrl($this->Article, PageNumber($Offset, $Limit, 0, false)));

        // Get the comments.
        $this->ArticleComments = $this->ArticleCommentModel->GetByArticleID($this->Article->ArticleID, $Offset, $Limit);

        // Validate slugs.
        $DateInsertedYear = Gdn_Format::Date($this->Article->DateInserted, '%Y');
        if (((count($this->RequestArgs) < 2) && (!$ArticleYear || !$ArticleUrlCode)) || !is_numeric($ArticleYear)
            || ($ArticleUrlCode == '') || !$this->Article
            || ($ArticleYear != $DateInsertedYear)
        )
            throw NotFoundException('Article');

        // Set the title.
        $this->Title($this->Article->Name);

        // Add the open graph tags
        $this->AddMetaTags();

        // Set up comment form.
        $this->Form->SetModel($this->ArticleCommentModel);
        $this->Form->Action = Url('/compose/comment/' . $this->Article->ArticleID);
        $this->Form->AddHidden('ArticleID', $this->Article->ArticleID);

        // Load data for similar articles
        if (C('Articles.Articles.ShowSimilarArticles')) {
            $SimilarArticles = $this->ArticleModel->GetSimilarArticles($this->Article->ArticleID,
                $this->Article->ArticleCategoryID);

            $this->SetData('SimilarArticles', $SimilarArticles);
        }

        $this->View = 'index';

        $this->Render();
    }

    /**
     * Adds meta tags to a controller method.
     */
    protected function AddMetaTags() {
        $HeadModule =& $this->Head;
        $Article = $this->Article;

        $HeadModule->AddTag('meta', array('property' => 'og:type', 'content' => 'article'));

        if ($Article->Excerpt != '') {
            $Description = Gdn_Format::PlainText($Article->Excerpt, $Article->Format);
        } else {
            $Description = SliceParagraph(Gdn_Format::PlainText($Article->Body, $Article->Format), C('Articles.Excerpt.MaxLength'));
        }
        $this->Description($Description);

        $HeadModule->AddTag('meta', array('property' => 'article:published_time',
            'content' => date(DATE_ISO8601, strtotime($Article->DateInserted))));
        if ($Article->DateUpdated) {
            $HeadModule->AddTag('meta', array('property' => 'article:modified_time',
                'content' => date(DATE_ISO8601, strtotime($Article->DateUpdated))));
        }

        $Author = Gdn::UserModel()->GetID($Article->InsertUserID);
        $HeadModule->AddTag('meta',
            array('property' => 'article:author', 'content' => Url(UserUrl($Author), true)));
        $HeadModule->AddTag('meta', array('property' => 'article:section', 'content' => $this->ArticleCategory->Name));

        // Image meta info
        $Image = $this->ArticleMediaModel->GetThumbnailByArticleID($Article->ArticleID);
        if(!$Image) {
          $Image = $this->ArticleMediaModel->GetByArticleID($Article->ArticleID)->FirstRow();
        }
        if ($Image) {
            $HeadModule->AddTag('meta', array('property' => 'og:image', 'content' => Url('/uploads' . $Image->Path, true)));
            $HeadModule->AddTag('meta', array('property' => 'og:image:width', 'content' => $Image->ImageWidth));
            $HeadModule->AddTag('meta', array('property' => 'og:image:height', 'content' => $Image->ImageHeight));
        }
        
        // Twitter card
        $HeadModule->AddTag('meta', array('name' => 'twitter:card', 'content' => 'summary'));

        $TwitterUsername = trim(C('Articles.TwitterUsername', ''));
        if ($TwitterUsername != '')
            $HeadModule->AddTag('meta', array('name' => 'twitter:site', 'content' => '@' . $TwitterUsername));

        $HeadModule->AddTag('meta', array('name' => 'twitter:title', 'content' => $Article->Name));
        $HeadModule->AddTag('meta', array('name' => 'twitter:description', 'content' => $Description));

        if ($Image) {
            $HeadModule->AddTag('meta', array('name' => 'twitter:image', 'content' => Url('/uploads' . $Image->Path, true)));
        }
    }

    /**
     * Sends options to a view via JSON.
     *
     * @param mixed $Article is an article entity
     */
    private function SendOptions($Article) {
        require_once($this->FetchViewLocation('helper_functions', 'Article', 'Articles'));

        ob_start();
        ShowArticleOptions($Article);
        $Options = ob_get_clean();

        $this->JsonTarget("#Article_{$Article->ArticleID} .OptionsMenu,.Section-Article .Article .OptionsMenu",
            $Options, 'ReplaceWith');
    }

    /**
     * Allows user to close or re-open an article.
     *
     * If the article isn't closed, this closes it. If it is already
     * closed, this re-opens it. Closed article may not have new
     * comments added to them.
     *
     * @param int $ArticleID Unique article ID.
     * @param bool $Close Whether or not to close the article.
     * @param string $From Where the method is requested from.
     */
    public function Close($ArticleID, $Close = true, $From = 'list') {
        // Make sure we are posting back.
        if (!$this->Request->IsPostBack()) {
            throw PermissionException('Javascript');
        }

        $Article = $this->ArticleModel->GetByID($ArticleID);
        if (!$Article) {
            throw NotFoundException('Article');
        }

        $this->Permission('Articles.Articles.Close', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);

        // Close the article.
        $this->ArticleModel->SetField($ArticleID, 'Closed', $Close);
        $Article->Closed = $Close;

        // Redirect to the front page
        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            $Target = GetIncomingValue('Target', 'articles');
            SafeRedirect($Target);
        }

        $this->SendOptions($Article);

        if ($Close) {
            require_once($this->FetchViewLocation('helper_functions', 'Article', 'Articles'));
            $this->JsonTarget(".Section-ArticleList #Article_$ArticleID .Meta-Article",
                ArticleTag($Article, 'Closed', 'Closed'), 'Prepend');
            $this->JsonTarget(".Section-ArticleList #Article_$ArticleID", 'Closed', 'AddClass');
        } else {
            $this->JsonTarget(".Section-ArticleList #Article_$ArticleID .Tag-Closed", null, 'Remove');
            $this->JsonTarget(".Section-ArticleList #Article_$ArticleID", 'Closed', 'RemoveClass');
        }

        $this->JsonTarget("#Article_$ArticleID", null, 'Highlight');
        $this->JsonTarget(".Article #Item_0", null, 'Highlight');

        $this->Render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Allows user to delete article.
     *
     * @param int $ArticleID
     * @param string $Target
     * @throws NotFoundException if article not found
     */
    public function Delete($ArticleID, $Target = '') {
        $Article = $this->ArticleModel->GetByID($ArticleID);
        if (!$Article) {
            throw NotFoundException('Article');
        }

        $this->Permission('Articles.Articles.Delete', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);

        if ($this->Form->AuthenticatedPostBack()) {
            if (!$this->ArticleModel->Delete($ArticleID))
                $this->Form->AddError('Failed to delete article.');

            if ($this->Form->ErrorCount() == 0) {
                // Remove the "new article" activity for this article.
                $this->ArticleModel->DeleteActivity($ArticleID);

                // Redirect.
                if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
                    SafeRedirect($Target);

                if ($Target)
                    $this->RedirectUrl = Url($Target);

                $this->JsonTarget(".Section-ArticleList #Article_{$ArticleID}", null, 'SlideUp');
            }
        }

        $this->SetData('Title', T('Delete Article'));
        $this->Render();
    }

    /**
     * Allows user to delete a comment.
     *
     * If the comment is the only one in the article, the article will
     * be deleted as well. This is a "hard" delete - it is removed from the database.
     *
     * @param int $ArticleCommentID Unique comment ID.
     * @param string $TransientKey Single-use hash to prove intent.
     */
    public function DeleteComment($ArticleCommentID = '', $TransientKey = '') {
        $Session = Gdn::Session();
        $DefaultTarget = '/articles/';
        $ValidArticleCommentID = is_numeric($ArticleCommentID) && $ArticleCommentID > 0;
        $ValidUser = ($Session->UserID > 0) && $Session->ValidateTransientKey($TransientKey);

        if ($ValidArticleCommentID && $ValidUser) {
            // Get comment and article data.
            $Comment = $this->ArticleCommentModel->GetByID($ArticleCommentID);
            $ArticleID = val('ArticleID', $Comment);
            $Article = $this->ArticleModel->GetByID($ArticleID);

            if ($Comment && $Article) {
                $DefaultTarget = ArticleUrl($Article);

                // Make sure comment is this user's or they have Delete permission
                if ($Comment->InsertUserID != $Session->UserID || !C('Articles.Comments.AllowSelfDelete'))
                    $this->Permission('Articles.Comments.Delete', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);

                // Make sure that content can (still) be edited
                $EditContentTimeout = C('Garden.EditContentTimeout', -1);
                $CanEdit = $EditContentTimeout == -1 || strtotime($Comment->DateInserted) + $EditContentTimeout > time();
                if (!$CanEdit)
                    $this->Permission('Articles.Comments.Delete', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);

                // Delete the comment
                if (!$this->ArticleCommentModel->Delete($ArticleCommentID)) {
                    $this->Form->AddError('Failed to delete comment');
                } else {
                    // Comment was successfully deleted.

                    // Remove the "new article comment" activity for this article comment.
                    $this->ArticleCommentModel->DeleteActivity($ArticleCommentID);
                }
            } else {
                $this->Form->AddError('Invalid comment');
            }
        } else {
            $this->Form->AddError('ErrPermission');
        }

        // Redirect
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            $Target = GetIncomingValue('Target', $DefaultTarget);
            SafeRedirect($Target);
        }

        if ($this->Form->ErrorCount() > 0) {
            $this->SetJson('ErrorMessage', $this->Form->Errors());
        } else {
            $this->JsonTarget("#Comment_$ArticleCommentID", '', 'SlideUp');
        }

        $this->Render();
    }


    /**
     * Display article page starting with a particular comment.
     *
     * @param int $ArticleCommentID Unique comment ID
     */
    public function Comment($ArticleCommentID) {
        // Get the ArticleID
        $Comment = $this->ArticleCommentModel->GetByID($ArticleCommentID);
        if (!$Comment)
            throw NotFoundException('Article comment');

        // Figure out how many comments are before this one
        $Offset = $this->ArticleCommentModel->GetOffset($Comment);
        $Limit = Gdn::Config('Articles.Comments.PerPage', 30);

        $PageNumber = PageNumber($Offset, $Limit, true);
        $this->SetData('Page', $PageNumber);

        $this->View = 'index';

        $Article = $this->ArticleModel->GetByID($Comment->ArticleID);

        $this->Index(Gdn_Format::Date($Article->DateInserted, '%Y'), $Article->UrlCode, $PageNumber);
    }
}
