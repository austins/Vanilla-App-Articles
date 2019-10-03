<?php
/**
 * Article controller
 *
 * @copyright 2015-2018 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Handles displaying an article in most contexts via /article endpoint.
 */
class ArticleController extends Gdn_Controller {
    /** @var array Models to include. */
    public $Uses = array('ArticleModel', 'ArticleCategoryModel', 'ArticleCommentModel', 'ArticleMediaModel', 'Form');

    /** @var ArticleModel */
    public $ArticleModel;

    /** @var ArticleCategoryModel */
    public $ArticleCategoryModel;

    /** @var ArticleCommentModel */
    public $ArticleCommentModel;

    /** @var ArticleMediaModel */
    public $ArticleMediaModel;

    public $Article = false;
    protected $ArticleCategory = false;
    protected $ArticleComments = false;

    /**
     * Include JS, CSS, and modules used by all methods.
     * Extended by all other controllers in this application.
     * Always called by dispatcher before controller's requested method.
     */
    public function initialize() {
        // Set up head.
        $this->Head = new HeadModule($this);

        // Add JS files.
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('jquery.autogrow.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery-ui.js');
        $this->addJsFile('global.js');
        $this->addJsFile('articles.js');

        // Add CSS files.
        $this->addCssFile('style.css');
        $this->addCssFile('articles.css');

        // Add modules.
        $this->addModule('GuestModule');
        $this->addModule('SignedInModule');
        $this->addModule('ArticlesDashboardModule');
        $this->addModule('ArticleCategoriesModule');
        $this->addModule('RecentActivityModule');

        parent::initialize();
    }

    /**
     * Main method of an article.
     *
     * @param int $articleYear in YYYY format
     * @param string $articleUrlCode is a unique code
     * @param bool|object $page is a page entity
     * @throws NotFoundException if article not found
     */
    public function index($articleYear, $articleUrlCode, $page = false) {
        // Get the article.
        $this->Article = $this->ArticleModel->getByUrlCode($articleUrlCode);

        if (!$this->Article) {
            throw notFoundException('Article');
        }

        // Set required permission.
        // If not published...
        if ($this->Article->Status != ArticleModel::STATUS_PUBLISHED) {
            // If author, only require View permission.
            if ($this->Article->InsertUserID == Gdn::session()->UserID) {
                $this->permission('Articles.Articles.View', true, 'ArticleCategory',
                    $this->Article->PermissionArticleCategoryID);
            } else {
                $this->permission('Articles.Articles.Edit', true, 'ArticleCategory',
                    $this->Article->PermissionArticleCategoryID);
            }
        } else {
            $this->permission('Articles.Articles.View', true, 'ArticleCategory',
                $this->Article->PermissionArticleCategoryID);
        }

        // Get the category.
        $this->ArticleCategory = $this->ArticleCategoryModel->getByID($this->Article->ArticleCategoryID);
        $this->setData('ArticleCategory', $this->ArticleCategory);
        $this->setData('Breadcrumbs', array(
            array('Name' => $this->ArticleCategory->Name, 'Url' => articleCategoryUrl($this->ArticleCategory))
        ));

        // Prepare comment arguments.
        // Define the query offset and limit.
        $limit = c('Articles.Comments.PerPage', 30);
        list($offset, $limit) = offsetLimit($page, $limit);

        $pageNumber = pageNumber($offset, $limit);
        $this->setData('Page', $pageNumber);

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        $this->Pager = $pagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';

        $this->Pager->configure(
            $offset,
            $limit,
            $this->Article->CountArticleComments,
            array('ArticleUrl')
        );
        $this->Pager->Record = $this->Article;
        PagerModule::current($this->Pager);
        $this->fireEvent('AfterBuildPager');

        // Set canonical URL.
        $this->canonicalUrl(articleUrl($this->Article, pageNumber($offset, $limit, 0, false)));

        // Get the comments.
        $this->ArticleComments = $this->ArticleCommentModel->getByArticleID($this->Article->ArticleID, $offset, $limit, array('ac.ParentArticleCommentID' => null));

        // Validate slugs.
        $dateInsertedYear = Gdn_Format::date($this->Article->DateInserted, '%Y');
        if (((count($this->RequestArgs) < 2) && (!$articleYear || !$articleUrlCode)) || !is_numeric($articleYear)
            || ($articleUrlCode == '') || !$this->Article
            || ($articleYear != $dateInsertedYear)
        ) {
            throw notFoundException('Article');
        }

        // Set the title.
        $this->title($this->Article->Name);

        // Add the open graph tags
        $this->addMetaTags();

        // Set up comment form.
        $this->Form->setModel($this->ArticleCommentModel);
        $this->Form->Action = url('/compose/comment/' . $this->Article->ArticleID);
        $this->Form->addHidden('ArticleID', $this->Article->ArticleID);

        // Load data for similar articles
        if (c('Articles.Articles.ShowSimilarArticles')) {
            $similarArticles = $this->ArticleModel->getSimilarArticles($this->Article->ArticleID,
                $this->Article->ArticleCategoryID);

            $this->setData('SimilarArticles', $similarArticles);
        }

        $this->View = 'index';

        $this->render();
    }

    /**
     * Allows user to close or re-open an article.
     *
     * If the article isn't closed, this closes it. If it is already
     * closed, this re-opens it. Closed article may not have new
     * comments added to them.
     *
     * @param int $articleID Unique article ID.
     * @param bool $close Whether or not to close the article.
     * @param string $from Where the method is requested from.
     */
    public function close($articleID, $close = true, $from = 'list') {
        // Make sure we are posting back.
        if (!$this->Request->isPostBack()) {
            throw permissionException('Javascript');
        }

        $article = $this->ArticleModel->getByID($articleID);
        if (!$article) {
            throw notFoundException('Article');
        }

        $this->permission('Articles.Articles.Close', true, 'ArticleCategory', $article->PermissionArticleCategoryID);

        // Close the article.
        $this->ArticleModel->setField($articleID, 'Closed', $close);
        $article->Closed = $close;

        // Redirect to the front page
        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            $target = getIncomingValue('Target', 'articles');
            safeRedirect($target);
        }

        $this->sendOptions($article);

        if ($close) {
            require_once($this->fetchViewLocation('helper_functions', 'Article', 'Articles'));
            $this->jsonTarget(".Section-ArticleList #Article_$articleID .Meta-Article",
                articleTag($article, 'Closed', 'Closed'), 'Prepend');
            $this->jsonTarget(".Section-ArticleList #Article_$articleID", 'Closed', 'AddClass');
        } else {
            $this->jsonTarget(".Section-ArticleList #Article_$articleID .Tag-Closed", null, 'Remove');
            $this->jsonTarget(".Section-ArticleList #Article_$articleID", 'Closed', 'RemoveClass');
        }

        $this->jsonTarget("#Article_$articleID", null, 'Highlight');
        $this->jsonTarget(".Article #Item_0", null, 'Highlight');

        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Allows user to delete article.
     *
     * @param int $articleID
     * @param string $target
     * @throws NotFoundException if article not found
     */
    public function delete($articleID, $target = '') {
        $article = $this->ArticleModel->getByID($articleID);
        if (!$article) {
            throw notFoundException('Article');
        }

        $this->permission('Articles.Articles.Delete', true, 'ArticleCategory', $article->PermissionArticleCategoryID);

        if ($this->Form->authenticatedPostBack()) {
            if (!$this->ArticleModel->delete($articleID)) {
                $this->Form->addError('Failed to delete article.');
            }

            if ($this->Form->errorCount() == 0) {
                // Remove the "new article" activity for this article.
                $this->ArticleModel->deleteActivity($articleID);

                // Redirect.
                if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
                    safeRedirect($target);
                }

                if ($target) {
                    $this->RedirectUrl = url($target);
                }

                $this->jsonTarget(".Section-ArticleList #Article_{$articleID}", null, 'SlideUp');
            }
        }

        $this->setData('Title', t('Delete Article'));
        $this->render();
    }

    /**
     * Allows user to delete a comment.
     *
     * If the comment is the only one in the article, the article will
     * be deleted as well. This is a "hard" delete - it is removed from the database.
     *
     * @param int $articleCommentID Unique comment ID.
     * @param string $transientKey Single-use hash to prove intent.
     */
    public function deleteComment($articleCommentID = '', $transientKey = '') {
        $session = Gdn::session();
        $defaultTarget = '/articles/';
        $validArticleCommentID = is_numeric($articleCommentID) && $articleCommentID > 0;
        $validUser = ($session->UserID > 0) && $session->validateTransientKey($transientKey);

        if ($validArticleCommentID && $validUser) {
            // Get comment and article data.
            $comment = $this->ArticleCommentModel->getByID($articleCommentID);
            $articleID = val('ArticleID', $comment);
            $article = $this->ArticleModel->getByID($articleID);

            if ($comment && $article) {
                $defaultTarget = articleUrl($article);

                // Make sure comment is this user's or they have Delete permission
                if ($comment->InsertUserID != $session->UserID || !c('Articles.Comments.AllowSelfDelete')) {
                    $this->permission('Articles.Comments.Delete', true, 'ArticleCategory',
                        $article->PermissionArticleCategoryID);
                }

                // Make sure that content can (still) be edited
                $editContentTimeout = c('Garden.EditContentTimeout', -1);
                $canEdit = $editContentTimeout == -1 || strtotime($comment->DateInserted) + $editContentTimeout > time();
                if (!$canEdit) {
                    $this->permission('Articles.Comments.Delete', true, 'ArticleCategory',
                        $article->PermissionArticleCategoryID);
                }

                // Delete the comment
                if (!$this->ArticleCommentModel->delete($articleCommentID)) {
                    $this->Form->addError('Failed to delete comment');
                } else {
                    // Comment was successfully deleted.

                    // Remove the "new article comment" activity for this article comment.
                    $this->ArticleCommentModel->deleteActivity($articleCommentID);
                }
            } else {
                $this->Form->addError('Invalid comment');
            }
        } else {
            $this->Form->addError('ErrPermission');
        }

        // Redirect
        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            $Target = getIncomingValue('Target', $defaultTarget);
            safeRedirect($Target);
        }

        if ($this->Form->errorCount() > 0) {
            $this->setJson('ErrorMessage', $this->Form->errors());
        } else {
            $this->jsonTarget("#Comment_$articleCommentID", '', 'SlideUp');
        }

        $this->render();
    }

    /**
     * Display article page starting with a particular comment.
     *
     * @param int $articleCommentID Unique comment ID
     */
    public function comment($articleCommentID) {
        // Get the ArticleID
        $comment = $this->ArticleCommentModel->getByID($articleCommentID);
        if (!$comment) {
            throw notFoundException('Article comment');
        }

        // Figure out how many comments are before this one
        $offset = $this->ArticleCommentModel->getOffset($comment);
        $limit = Gdn::config('Articles.Comments.PerPage', 30);

        $pageNumber = pageNumber($offset, $limit, true);
        $this->setData('Page', $pageNumber);

        $this->View = 'index';

        $article = $this->ArticleModel->getByID($comment->ArticleID);

        $this->index(Gdn_Format::date($article->DateInserted, '%Y'), $article->UrlCode, $pageNumber);
    }

    /**
     * Adds meta tags to a controller method.
     */
    protected function addMetaTags() {
        $headModule =& $this->Head;
        $article = $this->Article;

        $headModule->addTag('meta', array('property' => 'og:type', 'content' => 'article'));

        if ($article->Excerpt != '') {
            $description = Gdn_Format::plainText($article->Excerpt, $article->Format);
        } else {
            $description = sliceParagraph(Gdn_Format::plainText($article->Body, $article->Format),
                c('Articles.Excerpt.MaxLength'));
        }
        $this->description($description);

        $headModule->addTag('meta', array('property' => 'article:published_time',
            'content' => date(DATE_ISO8601, strtotime($article->DateInserted))));
        if ($article->DateUpdated) {
            $headModule->addTag('meta', array('property' => 'article:modified_time',
                'content' => date(DATE_ISO8601, strtotime($article->DateUpdated))));
        }

        $author = Gdn::userModel()->getID($article->InsertUserID);
        $headModule->addTag('meta',
            array('property' => 'article:author', 'content' => url(userUrl($author), true)));
        $headModule->addTag('meta', array('property' => 'article:section', 'content' => $this->ArticleCategory->Name));

        // Image meta info
        $image = $this->ArticleMediaModel->getThumbnailByArticleID($article->ArticleID);
        if (!$image) {
            $image = $this->ArticleMediaModel->getByArticleID($article->ArticleID)->firstRow();
        }
        if ($image) {
            $headModule->addTag('meta',
                array('property' => 'og:image', 'content' => url('/uploads' . $image->Path, true)));
            $headModule->addTag('meta', array('property' => 'og:image:width', 'content' => $image->ImageWidth));
            $headModule->addTag('meta', array('property' => 'og:image:height', 'content' => $image->ImageHeight));
        }

        // Twitter card
        $headModule->addTag('meta', array('name' => 'twitter:card', 'content' => 'summary'));

        $twitterUsername = trim(c('Articles.TwitterUsername', ''));
        if ($twitterUsername != '') {
            $headModule->addTag('meta', array('name' => 'twitter:site', 'content' => '@' . $twitterUsername));
        }

        $headModule->addTag('meta', array('name' => 'twitter:title', 'content' => $article->Name));
        $headModule->addTag('meta', array('name' => 'twitter:description', 'content' => $description));

        if ($image) {
            $headModule->addTag('meta',
                array('name' => 'twitter:image', 'content' => url('/uploads' . $image->Path, true)));
        }
    }

    /**
     * Sends options to a view via JSON.
     *
     * @param mixed $article is an article entity
     */
    private function sendOptions($article) {
        require_once($this->fetchViewLocation('helper_functions', 'Article', 'Articles'));

        ob_start();
        showArticleOptions($article);
        $options = ob_get_clean();

        $this->jsonTarget("#Article_{$article->ArticleID} .OptionsMenu,.Section-Article .Article .OptionsMenu",
            $options, 'ReplaceWith');
    }
}
