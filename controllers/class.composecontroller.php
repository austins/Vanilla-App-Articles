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
    public function Index() {
        $this->title(T('Articles Dashboard'));

        // Set allowed permissions.
        // The user only needs one of the specified permissions.
        $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        $this->permission($PermissionsAllowed, false, 'ArticleCategory', 'any');

        $this->addModule('ComposeFilterModule');

        // Get recently published articles.
        $RecentlyPublishedOffset = 0;
        $RecentlyPublishedLimit = 5;
        $RecentlyPublishedWheres = array('a.Status' => ArticleModel::STATUS_PUBLISHED);
        $RecentlyPublished = $this->ArticleModel->get($RecentlyPublishedOffset, $RecentlyPublishedLimit,
            $RecentlyPublishedWheres);
        $this->setData('RecentlyPublished', $RecentlyPublished);

        // Get recent article comments.
        $RecentCommentsOffset = 0;
        $RecentCommentsLimit = 5;
        $RecentComments = $this->ArticleCommentModel->get($RecentCommentsOffset,
            $RecentCommentsLimit, null, 'desc');
        $this->setData('RecentComments', $RecentComments);

        // Get recent articles pending review.
        if (Gdn::session()->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', 'any')) {
            $PendingArticlesOffset = 0;
            $PendingArticlesLimit = 5;
            $PendingArticlesWheres = array('a.Status' => ArticleModel::STATUS_PENDING);
            $PendingArticles = $this->ArticleModel->get($PendingArticlesOffset, $PendingArticlesLimit,
                $PendingArticlesWheres);
            $this->setData('PendingArticles', $PendingArticles);
        }

        $this->View = 'index';
        $this->render();
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

        if (Gdn::session()->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', 'any')
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
        $this->title(T('Article Posts'));

        // Set allowed permissions.
        // The user only needs one of the specified permissions.
        $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        $this->permission($PermissionsAllowed, false, 'ArticleCategory', 'any');

        $this->setData('Breadcrumbs', array(
            array('Name' => T('Compose'), 'Url' => '/compose'),
            array('Name' => T('Posts'), 'Url' => '/compose/posts')
        ));

        $this->addModule('ComposeFilterModule');

        // Get total article count.
        $CountArticles = $this->ArticleModel->getCount();
        $this->setData('CountArticles', $CountArticles);

        // Determine offset from $Page.
        list($Offset, $Limit) = offsetLimit($Page, c('Articles.Articles.PerPage', 12));
        $Page = pageNumber($Offset, $Limit);
        $this->canonicalUrl(url(concatSep('/', 'articles', pageNumber($Offset, $Limit, true, false)), true));

        // Have a way to limit the number of pages on large databases
        // because requesting a super-high page can kill the db.
        $MaxPages = c('Articles.Articles.MaxPages', false);
        if ($MaxPages && $Page > $MaxPages) {
            throw notFoundException();
        }

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        $this->Pager = $PagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure($Offset, $Limit, $CountArticles, 'articles/%1$s');
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'articles/{Page}');
        }
        $this->setData('_Page', $Page);
        $this->setData('_Limit', $Limit);
        $this->fireEvent('AfterBuildPager');

        // If the user is not an article editor, then only show their own articles.
        $session = Gdn::session();
        $Wheres = false;
        if (!$session->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', 'any')) {
            $Wheres = array('a.InsertUserID' => $session->UserID);
        }

        // Get the articles.
        $Articles = $this->ArticleModel->get($Offset, $Limit, $Wheres);
        $this->setData('Articles', $Articles);

        $this->View = 'posts';
        $this->render();
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
            $this->title(T('Add Article'));

            // Set allowed permission.
            $this->permission('Articles.Articles.Add', true, 'ArticleCategory', 'any');
        }

        $this->setData('Breadcrumbs', array(
            array('Name' => T('Compose'), 'Url' => '/compose'),
            array('Name' => T('New Article'), 'Url' => '/compose/article')
        ));

        // Set the model on the form.
        $this->Form->setModel($this->ArticleModel);

        $this->addJsFile('jquery.ajaxfileupload.js');

        // Get categories.
        $Categories = $this->ArticleCategoryModel->get(null, array('Articles.Articles.View', 'Articles.Articles.Add'));

        if ($Categories->numRows() === 0) {
            throw new Gdn_UserException(T('At least one article category must exist to compose an article.'));
        }

        $this->setData('Categories', $Categories, true);

        // Set status options.
        $this->setData('StatusOptions', $this->GetArticleStatusOptions($Article), true);

        $UserModel = new UserModel();
        $Author = false;
        $Preview = false;

        // The form has not been submitted yet.
        if (!$this->Form->authenticatedPostBack()) {
            // If editing...
            if ($Article) {
                $this->AddDefinition('ArticleID', $Article->ArticleID);
                $this->setData('Article', $Article, true);
                $this->Form->setData($Article);

                $this->Form->addHidden('UrlCodeIsDefined', '1');

                // Set author field.
                $Author = $UserModel->getID($Article->InsertUserID);

                $UploadedImages = $this->ArticleMediaModel->getByArticleID($Article->ArticleID);
                $this->setData('UploadedImages', $UploadedImages, true);

                $UploadedThumbnail = $this->ArticleMediaModel->getThumbnailByArticleID($Article->ArticleID);
                $this->setData('UploadedThumbnail', $UploadedThumbnail, true);
            } else {
                // If not editing...
                $this->Form->addHidden('UrlCodeIsDefined', '0');
            }

            // If the user with InsertUserID doesn't exist.
            if (!$Author) {
                $Author = Gdn::session()->User;
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
            $SQL->select('a.ArticleID')
                ->from('Article a')
                ->where('a.UrlCode', $FormValues['UrlCode']);

            if ($Article) {
                $SQL->where('a.ArticleID <>', $Article->ArticleID);
            }

            $UrlCodeExists = isset($SQL->get()->firstRow()->ArticleID);

            if ($UrlCodeExists) {
                $this->Form->addError('The specified URL code is already in use by another article.', 'UrlCode');
            }

            // Retrieve author user ID.
            if ($FormValues['AuthorUserName'] !== "") {
                $Author = $UserModel->GetByUsername($FormValues['AuthorUserName']);
            }

            // If the inputted author doesn't exist.
            if (!$Author) {
                $session = Gdn::session();

                $Category = ArticleCategoryModel::categories($FormValues['ArticleCategoryID']);
                $PermissionArticleCategoryID = val('PermissionArticleCategoryID', $Category, 'any');
                if (!$session->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', $PermissionArticleCategoryID)
                        && ($FormValues['AuthorUserName'] == "")) {
                    // Set author to current user if current user does not have Edit permission.
                    $Author = $session->User;
                } else {
                    // Show friendly error messages for author field if user has Edit permission.
                    if ($FormValues['AuthorUserName'] == "") {
                        $this->Form->addError('Author is required.', 'AuthorUserName');
                    } else {
                        $this->Form->addError('The user for the author field does not exist.', 'AuthorUserName');
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
                $this->Preview->InsertUserID = isset($Author->UserID) ? $Author->UserID : $session->User->UserID;
                $this->Preview->InsertName = $session->User->Name;
                $this->Preview->InsertPhoto = $session->User->Photo;
                $this->Preview->DateInserted = Gdn_Format::date();
                $this->Preview->Body = ArrayValue('Body', $FormValues, '');
                $this->Preview->Format = GetValue('Format', $FormValues, c('Garden.InputFormatter'));

                $this->EventArguments['Article'] = &$this->Article;
                $this->EventArguments['Preview'] = &$this->Preview;
                $this->fireEvent('BeforeArticlePreview');

                if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                    $this->AddAsset('Content', $this->FetchView('preview'));
                } else {
                    $this->View = 'preview';
                }
            } else {
                if ($this->Form->errorCount() == 0) {
                    $ArticleID = $this->Form->Save($FormValues);

                    // If the article was saved successfully.
                    if ($ArticleID) {
                        $NewArticle = $this->ArticleModel->getByID($ArticleID);

                        // If editing.
                        if ($Article) {
                            // If the author has changed from the initial article, then update the counts
                            // for the initial author after the article has been saved.
                            $InitialInsertUserID = val('InsertUserID', $Article, false);

                            if ($InitialInsertUserID != $Author->UserID) {
                                $this->ArticleModel->updateUserArticleCount($InitialInsertUserID);

                                // Update the count for the new author.
                                $this->ArticleModel->updateUserArticleCount($Author->UserID);
                            }

                            // If the status has changed from non-published to published, then update the DateInserted date.
                            $InitialStatus = val('Status', $Article, false);

                            if (($InitialStatus != ArticleModel::STATUS_PUBLISHED)
                                && ($NewArticle->Status == ArticleModel::STATUS_PUBLISHED)
                            ) {
                                $this->ArticleModel->setField($ArticleID, 'DateInserted', Gdn_Format::ToDateTime());
                            }

                            // Set thumbnail ID.
                            $UploadedThumbnail = $this->ArticleMediaModel->getThumbnailByArticleID($Article->ArticleID);
                            if (is_object($UploadedThumbnail) && ($UploadedThumbnail->ArticleMediaID > 0)) {
                                $this->ArticleModel->setField($ArticleID, 'ThumbnailID', $UploadedThumbnail->ArticleMediaID);
                            }
                        } else {
                            // If not editing.
                            // Assign the new article's ID to any uploaded images.
                            $UploadedImageIDs = $FormValues['UploadedImageIDs'];
                            if (is_array($UploadedImageIDs)) {
                                foreach ($UploadedImageIDs as $ArticleMediaID) {
                                    $this->ArticleMediaModel->setField($ArticleMediaID, 'ArticleID', $ArticleID);
                                }
                            }

                            // Set thumbnail ID.
                            $UploadedThumbnailID = (int)$FormValues['UploadedThumbnailID'];
                            if ($UploadedThumbnailID > 0) {
                                $this->ArticleModel->setField($ArticleID, 'ThumbnailID', $UploadedThumbnailID);
                                $this->ArticleMediaModel->setField($UploadedThumbnailID, 'ArticleID', $ArticleID);
                            }
                        }

                        // Redirect to the article.
                        if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                            Redirect(articleUrl($NewArticle));
                        } else {
                            $this->RedirectUrl = articleUrl($NewArticle, '', true);
                        }
                    }
                }
            }
        }

        if (!$Preview) {
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
    public function EditArticle($ArticleID = false) {
        $this->title(T('Edit Article'));

        // Get article.
        if (is_numeric($ArticleID)) {
            $Article = $this->ArticleModel->getByID($ArticleID);
        }

        // If the article doesn't exist, then throw an exception.
        if (!$Article) {
            throw notFoundException('Article');
        }

        // Set allowed permission.
        $this->permission('Articles.Articles.Edit', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);

        $this->setData('Article', $Article, true);

        // Get category.
        $Category = $this->ArticleCategoryModel->getByID($Article->ArticleCategoryID);
        $this->setData('Category', $Category, true);

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
            throw notFoundException('Page');
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
        $session = Gdn::session();
        $PermissionArticleCategoryID = 'any';
        if (is_numeric($ArticleID)) {
            $ArticleModel = new ArticleModel();

            $Article = $ArticleModel->getByID($ArticleID);
            if ($Article) {
                $PermissionArticleCategoryID = $Article->PermissionArticleCategoryID;
            }
        }
        if (!$session->checkPermission('Articles.Articles.Add', true, 'ArticleCategory', $PermissionArticleCategoryID)) {
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
                $SaveWidth = c('Articles.Articles.ThumbnailWidth', 280);
                $SaveHeight = c('Articles.Articles.ThumbnailHeight', 200);
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
                array('OutputType' => $Extension, 'ImageQuality' => c('Garden.UploadImage.Quality', 75))
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
            'InsertUserID' => $session->UserID,
        );

        $ArticleMediaID = $this->ArticleMediaModel->save($MediaValues);

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
            throw notFoundException('Page');
        }

        $Media = $this->ArticleMediaModel->getByID($ArticleMediaID);
        if (!$Media) {
            throw notFoundException('Article media');
        }

        // Check permission.
        $session = Gdn::session();
        $PermissionArticleCategoryID = 'any';
        if (is_numeric($Media->ArticleID)) {
            $ArticleModel = new ArticleModel();

            $Article = $ArticleModel->getByID($Media->ArticleID);
            if ($Article) {
                $PermissionArticleCategoryID = $Article->PermissionArticleCategoryID;
            }
        }
        if (!$session->checkPermission('Articles.Articles.Add', true, 'ArticleCategory', $PermissionArticleCategoryID)) {
            throw permissionException('Articles.Articles.Add');
        }

        $this->_DeliveryMethod = DELIVERY_METHOD_JSON;
        $this->_DeliveryType = DELIVERY_TYPE_BOOL;

        // Delete the image from the database.
        $Deleted = $this->ArticleMediaModel->delete($ArticleMediaID);

        // Delete the image file.
        $ImagePath = PATH_UPLOADS . DS . val('Path', $Media);
        if (file_exists($ImagePath)) {
            @unlink($ImagePath);
        }

        $this->render('Blank', 'Utility', 'Dashboard');
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
        $this->title(T('Post Article Comment'));

        // Set required permission.
        $session = Gdn::session();

        // Get the article.
        $Article = $this->ArticleModel->getByID($ArticleID);

        // Determine if this is a guest commenting
        $GuestCommenting = false;
        if (!$session->isValid()) { // Not logged in, so this could be a guest
            if (c('Articles.Comments.AllowGuests', false)) { // If guest commenting is enabled
                $GuestCommenting = true;
            } else { // Require permission to add comment
                $this->permission('Articles.Comments.Add', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);
            }
        }

        // Determine whether we are editing.
        $ArticleCommentID = isset($this->Comment) && property_exists($this->Comment, 'ArticleCommentID') ? $this->Comment->ArticleCommentID : false;
        $this->EventArguments['ArticleCommentID'] = &$ArticleCommentID;
        $Editing = ($ArticleCommentID > 0);

        // If closed, cancel and go to article.
        if ($Article && $Article->Closed == 1 && !$Editing
                && !$session->checkPermission('Articles.Articles.Close', true, 'ArticleCategory', $Article->PermissionArticleCategoryID)) {
            Redirect(articleUrl($Article));
        }

        // Add hidden IDs to form.
        $this->Form->addHidden('ArticleID', $ArticleID);
        $this->Form->addHidden('ArticleCommentID', $ArticleCommentID);

        // Check permissions.
        if ($session->isValid()) {
            if ($Article && $Editing) {
                // Permission to edit
                if ($this->Comment->InsertUserID != $session->UserID) {
                    $this->permission('Articles.Comments.Edit', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);
                }

                // Make sure that content can (still) be edited.
                $EditContentTimeout = c('Garden.EditContentTimeout', -1);
                $CanEdit = $EditContentTimeout == -1 || strtotime($this->Comment->DateInserted) + $EditContentTimeout > time();
                if (!$CanEdit) {
                    $this->permission('Articles.Comments.Edit', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);
                }

                // Make sure only moderators can edit closed things
                if ($Article->Closed) {
                    $this->permission('Articles.Comments.Edit', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);
                }
            } else {
                if ($Article) {
                    // Permission to add
                    $this->permission('Articles.Comments.Add', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);
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
            $FormValues = $this->Form->FormValues();

            $Type = getIncomingValue('Type');
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
                $ParentComment = $this->ArticleCommentModel->getByID($FormValues['ParentArticleCommentID']);

                // Parent comment doesn't exist.
                if (!$ParentComment) {
                    throw notFoundException('Parent comment');
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
                    $FormValues['GuestName'] = Gdn_Format::plainText($FormValues['GuestName']);
                    $FormValues['GuestEmail'] = Gdn_Format::plainText($FormValues['GuestEmail']);
                }

                $this->Form->SetFormValue('GuestName', $FormValues['GuestName']);
                $this->Form->SetFormValue('GuestEmail', $FormValues['GuestEmail']);
            }

            if ($this->Form->errorCount() > 0) {
                // Return the form errors.
                $this->ErrorMessage($this->Form->Errors());
            } else {
                // There are no form errors.
                if ($Preview) {
                    // If this was a preview click, create a comment shell with the values for this comment
                    $this->Preview = new stdClass();
                    $this->Preview->InsertUserID = $session->User->UserID;
                    $this->Preview->InsertName = $session->User->Name;
                    $this->Preview->InsertPhoto = $session->User->Photo;
                    $this->Preview->DateInserted = Gdn_Format::date();
                    $this->Preview->Body = ArrayValue('Body', $FormValues, '');

                    if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                        $this->AddAsset('Content', $this->FetchView('preview'));
                        $this->Preview->Format = GetValue('Format', $FormValues, c('Garden.InputFormatter'));
                    } else {
                        $this->View = 'preview';
                    }
                } else {
                    $CommentID = $this->Form->Save($FormValues);

                    if ($CommentID) {
                        $this->RedirectUrl = articleCommentUrl($CommentID);
                    }
                }
            }
        }

        if (!$Editing && !$Preview) {
            $this->View = 'comment';
        }

        $this->render();
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
            $this->Comment = $this->ArticleCommentModel->getByID($ArticleCommentID);
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
