<?php
if (!defined('APPLICATION'))
    exit();

/**
 * The controller for an article.
 */
class ArticleController extends Gdn_Controller {
    /**
     * Models to include.
     */
    public $Uses = array('ArticleModel', 'ArticleCategoryModel', 'ArticleCommentModel', 'Form');

    public $Article = false;
    protected $Category = false;
    protected $Comments = false;

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
     * The main method of this controller.
     */
    public function Index($ArticleYear, $ArticleUrlCode) {
        // Add module.
        $this->AddModule('ArticlesDashboardModule');

        // Get the article.
        $this->Article = $this->ArticleModel->GetByUrlCode($ArticleUrlCode);

        if (!$this->Article)
            throw NotFoundException('Article');

        // Set required permission.
        $UserModel = new UserModel();
        if ($this->Article->Status != ArticleModel::STATUS_PUBLISHED)
            if (($this->Article->AttributionUserID == Gdn::Session()->UserID)
                && !$UserModel->CheckPermission($this->Article->AttributionUserID, 'Articles.Articles.Edit')
            )
                $this->Permission('Articles.Articles.View');
            else
                $this->Permission('Articles.Articles.Edit');
        else
            $this->Permission('Articles.Articles.View');

        // Get the category.
        $this->Category = $this->ArticleCategoryModel->GetByID($this->Article->CategoryID);

        // Get the comments.
        $this->Comments = $this->ArticleCommentModel->GetByArticleID($this->Article->ArticleID);

        // Validate slugs.
        $DateInsertedYear = Gdn_Format::Date($this->Article->DateInserted, '%Y');
        if ((count($this->RequestArgs) < 2) || !is_numeric($ArticleYear)
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

        $this->View = 'index';
        
        $this->Render();
    }
    
    protected function AddMetaTags() {
      $HeadModule =& $this->Head;
      $Article = $this->Article;

      $HeadModule->AddTag('meta', array('property' => 'og:type', 'content' => 'article'));
      
      if($Article->Excerpt != '') {
        $this->Description(Gdn_Format::To($Article->Excerpt, $Article->Format));
      }
      else {
        $this->Description(SliceParagraph(Gdn_Format::PlainText($Article->Body, $Article->Format), C('Articles.Excerpt.MaxLength')));
      }
      
      $HeadModule->AddTag('meta', array('property' => 'article:published_time', 'content' => date(DATE_ISO8601, strtotime($Article->DateInserted))));
      if($Article->DateUpdated) {
        $HeadModule->AddTag('meta', array('property' => 'article:modified_time', 'content' => date(DATE_ISO8601, strtotime($Article->DateUpdated))));
      }
      
      // TODO: Add expiration date meta
      // $HeadModule->AddTag('meta', array('property' => 'article:expiration_time', 'content' => $Article->DateExpired));
      
      $HeadModule->AddTag('meta', array('property' => 'article:author', 'content' => Url('/profile/' . $Article->AuthorName, TRUE)));
      $HeadModule->AddTag('meta', array('property' => 'article:section', 'content' => $this->Category->Name));
      
      // TODO: Add in image meta info
      // $HeadModule->AddTag('meta', array('property' => 'og:image', 'content' => $Article->Photo));
      // $HeadModule->AddTag('meta', array('property' => 'og:image:width', 'content' => $Article->PhotoWidth));
      // $HeadModule->AddTag('meta', array('property' => 'og:image:height', 'content' => $Article->PhotoHeight));
      
      // TODO: Add article tags
      // foreach($Article->Tags as $Tag) {
      //   $HeadModule->AddTag('meta', array('property' => 'article:tag', 'content' => $Tag));
      // }
    }

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
     */
    public function Close($ArticleID, $Close = true, $From = 'list') {
        // Make sure we are posting back.
        if (!$this->Request->IsPostBack()) {
            throw PermissionException('Javascript');
        }

        $this->Permission('Articles.Articles.Close');

        $Article = $this->ArticleModel->GetID($ArticleID);

        if (!$Article) {
            throw NotFoundException('Article');
        }

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

    public function Delete($ArticleID, $Target = '') {
        $this->Permission('Articles.Articles.Delete');

        $Article = $this->ArticleModel->GetID($ArticleID);
        if (!$Article)
            throw NotFoundException('Article');

        if ($this->Form->AuthenticatedPostBack()) {
            if (!$this->ArticleModel->Delete($ArticleID))
                $this->Form->AddError('Failed to delete article.');

            if ($this->Form->ErrorCount() == 0) {
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
     * @param int $CommentID Unique comment ID.
     * @param string $TransientKey Single-use hash to prove intent.
     */
    public function DeleteComment($CommentID = '', $TransientKey = '') {
        $Session = Gdn::Session();
        $DefaultTarget = '/articles/';
        $ValidCommentID = is_numeric($CommentID) && $CommentID > 0;
        $ValidUser = ($Session->UserID > 0) && $Session->ValidateTransientKey($TransientKey);

        if ($ValidCommentID && $ValidUser) {
            // Get comment and article data.
            $Comment = $this->ArticleCommentModel->GetID($CommentID);
            $ArticleID = GetValue('ArticleID', $Comment);
            $Article = $this->ArticleModel->GetID($ArticleID);

            if ($Comment && $Article) {
                $DefaultTarget = ArticleUrl($Article);

                // Make sure comment is this user's or they have Delete permission
                if ($Comment->InsertUserID != $Session->UserID || !C('Articles.Comments.AllowSelfDelete'))
                    $this->Permission('Articles.Comments.Delete');

                // Make sure that content can (still) be edited
                $EditContentTimeout = C('Garden.EditContentTimeout', -1);
                $CanEdit = $EditContentTimeout == -1 || strtotime($Comment->DateInserted) + $EditContentTimeout > time();
                if (!$CanEdit)
                    $this->Permission('Articles.Comments.Delete');

                // Delete the comment
                if (!$this->ArticleCommentModel->Delete($CommentID))
                    $this->Form->AddError('Failed to delete comment');
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
            $this->JsonTarget("#Comment_$CommentID", '', 'SlideUp');
        }

        $this->Render();
    }
}
