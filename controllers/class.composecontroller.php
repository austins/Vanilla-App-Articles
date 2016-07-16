<?php
/**
 * Compose controller
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Handles displaying an article in most contexts via /compose endpoint.
 */
class ComposeController extends Gdn_Controller {
    /**
     * Models to include.
     */
    public $Uses = array('ArticleModel', 'ArticleCategoryModel', 'ArticleCommentModel', 'ArticleMediaModel', 'Form');

    /** @var ArticleModel */
    public $ArticleModel;

    /** @var ArticleCategoryModel */
    public $ArticleCategoryModel;

    /** @var ArticleCommentModel */
    public $ArticleCommentModel;

    /** @var ArticleMediaModel */
    public $ArticleMediaModel;

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
        $this->addJsFile('jquery.livequery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('jquery.autogrow.js');
        $this->addJsFile('jquery.autocomplete.js');
        $this->addJsFile('global.js');
        $this->addJsFile('articles.js');
        $this->addJsFile('articles.compose.js');

        // Add CSS files.
        $this->addCssFile('style.css');
        $this->addCssFile('articles.css');
        $this->addCssFile('articles.compose.css');

        // Add modules.
        $this->addModule('GuestModule');
        $this->addModule('SignedInModule');

        parent::initialize();
    }

    /**
     * This handles the articles dashboard.
     * Only visible to users that have permission.
     */
    public function index() {
        $this->title(t('Articles Dashboard'));

        // Set allowed permissions.
        // The user only needs one of the specified permissions.
        $permissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        $this->permission($permissionsAllowed, false, 'ArticleCategory', 'any');

        $this->addModule('ComposeFilterModule');

        // Get recently published articles.
        $recentlyPublishedOffset = 0;
        $recentlyPublishedLimit = 5;
        $recentlyPublishedWheres = array('a.Status' => ArticleModel::STATUS_PUBLISHED);
        $recentlyPublished = $this->ArticleModel->get($recentlyPublishedOffset, $recentlyPublishedLimit,
            $recentlyPublishedWheres);
        $this->setData('RecentlyPublished', $recentlyPublished);

        // Get recent article comments.
        $recentCommentsOffset = 0;
        $recentCommentsLimit = 5;
        $recentComments = $this->ArticleCommentModel->get($recentCommentsOffset,
            $recentCommentsLimit, null, 'desc');
        $this->setData('RecentComments', $recentComments);

        // Get recent articles pending review.
        if (Gdn::session()->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', 'any')) {
            $pendingArticlesOffset = 0;
            $pendingArticlesLimit = 5;
            $pendingArticlesWheres = array('a.Status' => ArticleModel::STATUS_PENDING);
            $pendingArticles = $this->ArticleModel->get($pendingArticlesOffset, $pendingArticlesLimit,
                $pendingArticlesWheres);
            $this->setData('PendingArticles', $pendingArticles);
        }

        $this->View = 'index';
        $this->render();
    }

    /**
     * Listing of articles.
     *
     * @param bool|object $page entity
     * @throws NotFoundException if no articles found
     */
    public function posts($page = false) {
        $this->title(t('Article Posts'));

        // Set allowed permissions.
        // The user only needs one of the specified permissions.
        $permissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        $this->permission($permissionsAllowed, false, 'ArticleCategory', 'any');

        $this->setData('Breadcrumbs', array(
            array('Name' => t('Compose'), 'Url' => '/compose'),
            array('Name' => t('Posts'), 'Url' => '/compose/posts')
        ));

        $this->addModule('ComposeFilterModule');

        // Get total article count.
        $countArticles = $this->ArticleModel->getCount();
        $this->setData('CountArticles', $countArticles);

        // Determine offset from $Page.
        list($offset, $limit) = offsetLimit($page, c('Articles.Articles.PerPage', 12));
        $page = pageNumber($offset, $limit);
        $this->canonicalUrl(url(concatSep('/', 'articles', pageNumber($offset, $limit, true, false)), true));

        // Have a way to limit the number of pages on large databases
        // because requesting a super-high page can kill the db.
        $maxPages = c('Articles.Articles.MaxPages', false);
        if ($maxPages && $page > $maxPages) {
            throw notFoundException();
        }

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        $this->Pager = $pagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure($offset, $limit, $countArticles, 'articles/%1$s');
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'articles/{Page}');
        }
        $this->setData('_Page', $page);
        $this->setData('_Limit', $limit);
        $this->fireEvent('AfterBuildPager');

        // If the user is not an article editor, then only show their own articles.
        $session = Gdn::session();
        $wheres = false;
        if (!$session->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', 'any')) {
            $wheres = array('a.InsertUserID' => $session->UserID);
        }

        // Get the articles.
        $articles = $this->ArticleModel->get($offset, $limit, $wheres);
        $this->setData('Articles', $articles);

        $this->View = 'posts';
        $this->render();
    }

    /**
     * Allows the user to create an article.
     *
     * @param bool|object $article entity
     * @throws Gdn_UserException if a category isn't selected
     */
    public function article($article = false) {
        // If not editing...
        if (!$article) {
            $this->title(t('Add Article'));

            // Set allowed permission.
            $this->permission('Articles.Articles.Add', true, 'ArticleCategory', 'any');
        }

        $this->setData('Breadcrumbs', array(
            array('Name' => t('Compose'), 'Url' => '/compose'),
            array('Name' => t('New Article'), 'Url' => '/compose/article')
        ));

        // Set the model on the form.
        $this->Form->setModel($this->ArticleModel);

        $this->addJsFile('jquery.ajaxfileupload.js');

        // Get categories.
        $categories = $this->ArticleCategoryModel->get(null, array('Articles.Articles.View', 'Articles.Articles.Add'));

        if ($categories->numRows() === 0) {
            throw new Gdn_UserException(t('At least one article category must exist to compose an article.'));
        }

        $this->setData('Categories', $categories, true);

        // Set status options.
        $this->setData('StatusOptions', $this->getArticleStatusOptions($article), true);

        $userModel = new UserModel();
        $author = false;
        $preview = false;

        // The form has not been submitted yet.
        if (!$this->Form->authenticatedPostBack()) {
            // If editing...
            if ($article) {
                $this->addDefinition('ArticleID', $article->ArticleID);
                $this->setData('Article', $article, true);
                $this->Form->setData($article);

                $this->Form->addHidden('UrlCodeIsDefined', '1');

                // Set author field.
                $author = $userModel->getID($article->InsertUserID);

                $uploadedImages = $this->ArticleMediaModel->getByArticleID($article->ArticleID);
                $this->setData('UploadedImages', $uploadedImages, true);

                $uploadedThumbnail = $this->ArticleMediaModel->getThumbnailByArticleID($article->ArticleID);
                $this->setData('UploadedThumbnail', $uploadedThumbnail, true);
            } else {
                // If not editing...
                $this->Form->addHidden('UrlCodeIsDefined', '0');
            }

            // If the user with InsertUserID doesn't exist.
            if (!$author) {
                $author = Gdn::session()->User;
            }

            $this->Form->setValue('AuthorUserName', $author->Name);
        } else { // The form has been submitted.
            // Manually validate certain fields.
            $formValues = $this->Form->formValues();

            $this->Form->validateRule('ArticleCategoryID', 'ValidateRequired', t('Article category is required.'));

            // Validate the URL code.
            // Set UrlCode to name of article if it's not defined.
            if ($formValues['UrlCode'] == '') {
                $formValues['UrlCode'] = $formValues['Name'];
            }

            // Format the UrlCode.
            $formValues['UrlCode'] = Gdn_Format::url($formValues['UrlCode']);
            $this->Form->setFormValue('UrlCode', $formValues['UrlCode']);

            // If editing, make sure the ArticleID is passed to the form save method.
            $sql = Gdn::database()->sql();
            if ($article) {
                $this->Form->setFormValue('ArticleID', (int)$article->ArticleID);
            }

            // Make sure that the UrlCode is unique among articles.
            $sql->select('a.ArticleID')
                ->from('Article a')
                ->where('a.UrlCode', $formValues['UrlCode']);

            if ($article) {
                $sql->where('a.ArticleID <>', $article->ArticleID);
            }

            $urlCodeExists = isset($sql->get()->firstRow()->ArticleID);

            if ($urlCodeExists) {
                $this->Form->addError('The specified URL code is already in use by another article.', 'UrlCode');
            }

            // Retrieve author user ID.
            if ($formValues['AuthorUserName'] !== "") {
                $author = $userModel->getByUsername($formValues['AuthorUserName']);
            }

            // If the inputted author doesn't exist.
            if (!$author) {
                $session = Gdn::session();

                $category = ArticleCategoryModel::categories($formValues['ArticleCategoryID']);
                $permissionArticleCategoryID = val('PermissionArticleCategoryID', $category, 'any');
                if (!$session->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory',
                        $permissionArticleCategoryID)
                    && ($formValues['AuthorUserName'] == "")
                ) {
                    // Set author to current user if current user does not have Edit permission.
                    $author = $session->User;
                } else {
                    // Show friendly error messages for author field if user has Edit permission.
                    if ($formValues['AuthorUserName'] == "") {
                        $this->Form->addError('Author is required.', 'AuthorUserName');
                    } else {
                        $this->Form->addError('The user for the author field does not exist.', 'AuthorUserName');
                    }
                }
            }

            $this->Form->setFormValue('InsertUserID', (int)$author->UserID);

            // If this was a preview click, create an article shell with the values for this article.
            $preview = $this->Form->ButtonExists('Preview') ? true : false;
            if ($preview) {
                $this->Article = new stdClass();
                $this->Article->Name = $this->Form->getValue('Name', '');
                $this->Preview = new stdClass();
                $this->Preview->InsertUserID = isset($author->UserID) ? $author->UserID : $session->User->UserID;
                $this->Preview->InsertName = $session->User->Name;
                $this->Preview->InsertPhoto = $session->User->Photo;
                $this->Preview->DateInserted = Gdn_Format::date();
                $this->Preview->Body = arrayValue('Body', $formValues, '');
                $this->Preview->Format = getValue('Format', $formValues, c('Garden.InputFormatter'));

                $this->EventArguments['Article'] = &$this->Article;
                $this->EventArguments['Preview'] = &$this->Preview;
                $this->fireEvent('BeforeArticlePreview');

                if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                    $this->addAsset('Content', $this->fetchView('preview'));
                } else {
                    $this->View = 'preview';
                }
            } else {
                if ($this->Form->errorCount() == 0) {
                    $articleID = $this->Form->save($formValues);

                    // If the article was saved successfully.
                    if ($articleID) {
                        $newArticle = $this->ArticleModel->getByID($articleID);

                        // If editing.
                        if ($article) {
                            // If the author has changed from the initial article, then update the counts
                            // for the initial author after the article has been saved.
                            $initialInsertUserID = val('InsertUserID', $article, false);

                            if ($initialInsertUserID != $author->UserID) {
                                $this->ArticleModel->updateUserArticleCount($initialInsertUserID);

                                // Update the count for the new author.
                                $this->ArticleModel->updateUserArticleCount($author->UserID);
                            }

                            // If the status has changed from non-published to published, then update the DateInserted date.
                            $initialStatus = val('Status', $article, false);

                            if (($initialStatus != ArticleModel::STATUS_PUBLISHED)
                                && ($newArticle->Status == ArticleModel::STATUS_PUBLISHED)
                            ) {
                                $this->ArticleModel->setField($articleID, 'DateInserted', Gdn_Format::toDateTime());
                            }

                            // Set thumbnail ID.
                            $uploadedThumbnail = $this->ArticleMediaModel->getThumbnailByArticleID($article->ArticleID);
                            if (is_object($uploadedThumbnail) && ($uploadedThumbnail->ArticleMediaID > 0)) {
                                $this->ArticleModel->setField($articleID, 'ThumbnailID',
                                    $uploadedThumbnail->ArticleMediaID);
                            }
                        } else {
                            // If not editing.
                            // Assign the new article's ID to any uploaded images.
                            $uploadedImageIDs = $formValues['UploadedImageIDs'];
                            if (is_array($uploadedImageIDs)) {
                                foreach ($uploadedImageIDs as $articleMediaID) {
                                    $this->ArticleMediaModel->setField($articleMediaID, 'ArticleID', $articleID);
                                }
                            }

                            // Set thumbnail ID.
                            $uploadedThumbnailID = (int)$formValues['UploadedThumbnailID'];
                            if ($uploadedThumbnailID > 0) {
                                $this->ArticleModel->setField($articleID, 'ThumbnailID', $uploadedThumbnailID);
                                $this->ArticleMediaModel->setField($uploadedThumbnailID, 'ArticleID', $articleID);
                            }
                        }

                        // Redirect to the article.
                        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                            redirect(articleUrl($newArticle));
                        } else {
                            $this->RedirectUrl = articleUrl($newArticle, '', true);
                        }
                    }
                }
            }
        }

        if (!$preview) {
            $this->View = 'article';
        }

        $this->CssClass = 'NoPanel';

        $this->render();
    }

    /**
     * Allows the user to edit an article.
     * Wrapper for Article() method.
     *
     * @param bool|object $Article entity
     * @throws NotFoundException if article not found
     */
    public function editArticle($articleID = false) {
        $this->title(t('Edit Article'));

        // Get article.
        if (is_numeric($articleID)) {
            $article = $this->ArticleModel->getByID($articleID);
        }

        // If the article doesn't exist, then throw an exception.
        if (!$article) {
            throw notFoundException('Article');
        }

        // Set allowed permission.
        $this->permission('Articles.Articles.Edit', true, 'ArticleCategory', $article->PermissionArticleCategoryID);

        $this->setData('Article', $article, true);

        // Get category.
        $category = $this->ArticleCategoryModel->getByID($article->ArticleCategoryID);
        $this->setData('Category', $category, true);

        $this->View = 'article';
        $this->article($article);
    }

    /**
     * Allows the user to upload an image to an article via AJAX.
     *
     * @return false on failure
     * @throws NotFoundException if no files posted
     * @throws PermissionException if user doesn't have permission to upload
     */
    public function uploadImage() {
        // Check for file data.
        if (!$_FILES) {
            throw notFoundException('Page');
        }

        // Handle the file data.
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        $this->deliveryType(DELIVERY_TYPE_VIEW);

        // ArticleID is saved with media model if editing. ArticleID is null if new article.
        // Null ArticleID is replaced by ArticleID when new article is saved.
        $articleID = $_POST['ArticleID'];
        if (!is_numeric($articleID) || ($articleID <= 0)) {
            $articleID = null;
        }

        // Check permission.
        $session = Gdn::session();
        $permissionArticleCategoryID = 'any';
        if (is_numeric($articleID)) {
            $articleModel = new ArticleModel();

            $article = $articleModel->getByID($articleID);
            if ($article) {
                $permissionArticleCategoryID = $article->PermissionArticleCategoryID;
            }
        }
        if (!$session->checkPermission('Articles.Articles.Add', true, 'ArticleCategory',
            $permissionArticleCategoryID)
        ) {
            throw permissionException('Articles.Articles.Add');
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
            $uploadFieldName = 'UploadThumbnail_New';
            $isThumbnail = true;
        } else {
            $uploadFieldName = 'UploadImage_New';
        }

        // Upload the image.
        $uploadImage = new Gdn_UploadImage();
        try {
            $tmpFileName = $uploadImage->validateUpload($uploadFieldName);

            // Generate the target image name.
            $currentYear = date('Y');
            $currentMonth = date('m');
            $uploadPath = PATH_UPLOADS . '/articles/' . $currentYear . '/' . $currentMonth;
            $targetImage = $uploadImage->generateTargetName($uploadPath, false, false);
            $basename = pathinfo($targetImage, PATHINFO_BASENAME);
            $extension = trim(pathinfo($targetImage, PATHINFO_EXTENSION), '.');
            $uploadsSubdir = '/articles/' . $currentYear . '/' . $currentMonth;

            if ($isThumbnail) {
                $saveWidth = c('Articles.Articles.ThumbnailWidth', 280);
                $saveHeight = c('Articles.Articles.ThumbnailHeight', 200);
            } else {
                $saveWidth = null;
                $saveHeight = null;
            }

            // Save the uploaded image.
            $props = $uploadImage->saveImageAs(
                $tmpFileName,
                $uploadsSubdir . '/' . $basename,
                $saveHeight,
                $saveWidth, // change these configs and add quality etc.
                array('OutputType' => $extension, 'ImageQuality' => c('Garden.UploadImage.Quality', 75))
            );

            $uploadedImagePath = sprintf($props['SaveFormat'], $uploadsSubdir . '/' . $basename);
        } catch (Exception $ex) {
            return false;
        }

        // Save the image.
        $imageProps = getimagesize($targetImage);
        $mediaValues = array(
            'ArticleID' => $articleID,
            'Name' => $basename,
            'Type' => $imageProps['mime'],
            'Size' => filesize($targetImage),
            'ImageWidth' => $imageProps[0],
            'ImageHeight' => $imageProps[1],
            'StorageMethod' => 'local',
            'IsThumbnail' => $isThumbnail,
            'Path' => $uploadedImagePath,
            'DateInserted' => Gdn_Format::toDateTime(),
            'InsertUserID' => $session->UserID,
        );

        $articleMediaID = $this->ArticleMediaModel->save($mediaValues);

        // Return path to the uploaded image in the following format.
        // Example: '/articles/year/month/filename.jpg'
        $jsonData = array(
            'ArticleMediaID' => $articleMediaID,
            'Name' => $basename,
            'Path' => $uploadedImagePath
        );

        $jsonReturn = json_encode($jsonData);

        die($jsonReturn);
    }

    /**
     * Allows the user to delete an image from an article.
     *
     * @param int $articleMediaID
     * @throws NotFoundException if invalid ArticleMediaID
     * @throws PermissionException if user doesn't have permission to upload
     */
    public function deleteImage($articleMediaID) {
        if (!is_numeric($articleMediaID)
            || ($this->_DeliveryMethod != DELIVERY_METHOD_JSON) || ($this->_DeliveryType != DELIVERY_TYPE_BOOL)
        ) {
            throw notFoundException('Page');
        }

        $media = $this->ArticleMediaModel->getByID($articleMediaID);
        if (!$media) {
            throw notFoundException('Article media');
        }

        // Check permission.
        $session = Gdn::session();
        $permissionArticleCategoryID = 'any';
        if (is_numeric($media->ArticleID)) {
            $articleModel = new ArticleModel();

            $article = $articleModel->getByID($media->ArticleID);
            if ($article) {
                $permissionArticleCategoryID = $article->PermissionArticleCategoryID;
            }
        }
        if (!$session->checkPermission('Articles.Articles.Add', true, 'ArticleCategory',
            $permissionArticleCategoryID)
        ) {
            throw permissionException('Articles.Articles.Add');
        }

        $this->_DeliveryMethod = DELIVERY_METHOD_JSON;
        $this->_DeliveryType = DELIVERY_TYPE_BOOL;

        // Delete the image from the database.
        $deleted = $this->ArticleMediaModel->delete($articleMediaID);

        // Delete the image file.
        $imagePath = PATH_UPLOADS . DS . val('Path', $media);
        if (file_exists($imagePath)) {
            @unlink($imagePath);
        }

        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Allows the user to comment on an article.
     *
     * @param int $articleID
     * @param bool $parentArticleCommentID
     * @throws NotFoundException if ArticleID not found.
     * @throws ForbiddenException if invalid reply
     */
    public function comment($articleID, $parentArticleCommentID = false) {
        $this->title(t('Post Article Comment'));

        // Set required permission.
        $session = Gdn::session();

        // Get the article.
        $article = $this->ArticleModel->getByID($articleID);

        // Determine if this is a guest commenting
        $guestCommenting = false;
        if (!$session->isValid()) { // Not logged in, so this could be a guest
            if (c('Articles.Comments.AllowGuests', false)) { // If guest commenting is enabled
                $guestCommenting = true;
            } else { // Require permission to add comment
                $this->permission('Articles.Comments.Add', true, 'ArticleCategory',
                    $article->PermissionArticleCategoryID);
            }
        }

        // Determine whether we are editing.
        $articleCommentID = isset($this->Comment) && property_exists($this->Comment,
            'ArticleCommentID') ? $this->Comment->ArticleCommentID : false;
        $this->EventArguments['ArticleCommentID'] = &$articleCommentID;
        $editing = ($articleCommentID > 0);

        // If closed, cancel and go to article.
        if ($article && $article->Closed == 1 && !$editing
            && !$session->checkPermission('Articles.Articles.Close', true, 'ArticleCategory',
                $article->PermissionArticleCategoryID)
        ) {
            redirect(articleUrl($article));
        }

        // Add hidden IDs to form.
        $this->Form->addHidden('ArticleID', $articleID);
        $this->Form->addHidden('ArticleCommentID', $articleCommentID);

        // Check permissions.
        if ($session->isValid()) {
            if ($article && $editing) {
                // Permission to edit
                if ($this->Comment->InsertUserID != $session->UserID) {
                    $this->permission('Articles.Comments.Edit', true, 'ArticleCategory',
                        $article->PermissionArticleCategoryID);
                }

                // Make sure that content can (still) be edited.
                $editContentTimeout = c('Garden.EditContentTimeout', -1);
                $canEdit = $editContentTimeout == -1 || strtotime($this->Comment->DateInserted) + $editContentTimeout > time();
                if (!$canEdit) {
                    $this->permission('Articles.Comments.Edit', true, 'ArticleCategory',
                        $article->PermissionArticleCategoryID);
                }

                // Make sure only moderators can edit closed things
                if ($article->Closed) {
                    $this->permission('Articles.Comments.Edit', true, 'ArticleCategory',
                        $article->PermissionArticleCategoryID);
                }
            } else {
                if ($article) {
                    // Permission to add
                    $this->permission('Articles.Comments.Add', true, 'ArticleCategory',
                        $article->PermissionArticleCategoryID);
                }
            }
        }

        // Set the model on the form.
        $this->Form->setModel($this->ArticleCommentModel);

        if (!$this->Form->authenticatedPostBack()) {
            if (isset($this->Comment)) {
                $this->Form->setData((array)$this->Comment);
            }
        } else {
            // Form was validly submitted.
            // Validate fields.
            $formValues = $this->Form->formValues();

            $type = getIncomingValue('Type');
            $preview = ($type == 'Preview');

            $this->Form->validateRule('Body', 'ValidateRequired');

            // Set article ID.
            $formValues['ArticleID'] = $articleID;
            $this->Form->setFormValue('ArticleID', $formValues['ArticleID']);

            // If the form didn't have ParentArticleCommentID set, then set it to the method argument as a default.
            if (!is_numeric($formValues['ParentArticleCommentID'])) {
                $parentArticleCommentID = is_numeric($parentArticleCommentID) ? $parentArticleCommentID : null;

                $formValues['ParentArticleCommentID'] = $parentArticleCommentID;
                $this->Form->setFormValue('ParentArticleCommentID', $parentArticleCommentID);
            }

            // Validate parent comment.
            $parentComment = false;
            if (is_numeric($formValues['ParentArticleCommentID'])) {
                $parentComment = $this->ArticleCommentModel->getByID($formValues['ParentArticleCommentID']);

                // Parent comment doesn't exist.
                if (!$parentComment) {
                    throw notFoundException('Parent comment');
                }

                // Only allow one level of threading.
                if (is_numeric($parentComment->ParentArticleCommentID) && ($parentComment->ParentArticleCommentID > 0)) {
                    throw forbiddenException('reply to a comment more than one level down');
                }
            }

            // If the user is signed in, then nullify the guest properties.
            if (!$editing) {
                if (!$guestCommenting) {
                    $formValues['GuestName'] = null;
                    $formValues['GuestEmail'] = null;
                } else {
                    // The InsertUserID should be null for inserting a guest comment.
                    $formValues['InsertUserID'] = null;
                    $this->Form->setFormValue('InsertUserID', $formValues['InsertUserID']);

                    // Require the guest fields.
                    $this->Form->validateRule('GuestName', 'ValidateRequired', t('Guest name is required.'));
                    $this->Form->validateRule('GuestEmail', 'ValidateRequired', t('Guest email is required.'));
                    $this->Form->validateRule('GuestEmail', 'ValidateEmail', t('That email address is not valid.'));

                    // Sanitize the guest properties.
                    $formValues['GuestName'] = Gdn_Format::plainText($formValues['GuestName']);
                    $formValues['GuestEmail'] = Gdn_Format::plainText($formValues['GuestEmail']);
                }

                $this->Form->setFormValue('GuestName', $formValues['GuestName']);
                $this->Form->setFormValue('GuestEmail', $formValues['GuestEmail']);
            }

            if ($this->Form->errorCount() > 0) {
                // Return the form errors.
                $this->errorMessage($this->Form->errors());
            } else {
                // There are no form errors.
                if ($preview) {
                    // If this was a preview click, create a comment shell with the values for this comment
                    $this->Preview = new stdClass();
                    $this->Preview->InsertUserID = $session->User->UserID;
                    $this->Preview->InsertName = $session->User->Name;
                    $this->Preview->InsertPhoto = $session->User->Photo;
                    $this->Preview->DateInserted = Gdn_Format::date();
                    $this->Preview->Body = arrayValue('Body', $formValues, '');

                    if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                        $this->addAsset('Content', $this->fetchView('preview'));
                        $this->Preview->Format = getValue('Format', $formValues, c('Garden.InputFormatter'));
                    } else {
                        $this->View = 'preview';
                    }
                } else {
                    $commentID = $this->Form->save($formValues);

                    if ($commentID) {
                        $this->RedirectUrl = articleCommentUrl($commentID);
                    }
                }
            }
        }

        if (!$editing && !$preview) {
            $this->View = 'comment';
        }

        $this->render();
    }

    /**
     * Edit a comment (wrapper for the Comment method).
     *
     * @param int $articleCommentID Unique ID of the comment to edit.
     */
    public function editComment($articleCommentID = '') {
        if (!is_numeric($articleCommentID)) {
            throw new InvalidArgumentException('The comment ID must be a numeric value.');
        }

        if ($articleCommentID > 0) {
            $this->Comment = $this->ArticleCommentModel->getByID($articleCommentID);
        }

        $this->Form->setFormValue('Format', val('Format', $this->Comment));

        $this->View = 'editcomment';

        $parentArticleCommentID = null;
        if (!is_numeric($this->Comment->ParentArticleCommentID) && ($this->Comment->ParentArticleCommentID > 0)) {
            $parentArticleCommentID = $this->Comment->ParentArticleCommentID;
        }

        $this->comment($this->Comment->ArticleID, $parentArticleCommentID);
    }

    /**
     * Retrieves status options for an article.
     *
     * @param bool|object $article entity
     * @return array
     */
    private function getArticleStatusOptions($article = false) {
        $statusOptions = array(
            ArticleModel::STATUS_DRAFT => t('Draft'),
            ArticleModel::STATUS_PENDING => t('Pending Review'),
        );

        if (Gdn::session()->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', 'any')
            || ($article && ($article->Status == ArticleModel::STATUS_PUBLISHED))
        ) {
            $statusOptions[ArticleModel::STATUS_PUBLISHED] = t('Published');
        }

        return $statusOptions;
    }
}
