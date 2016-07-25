<?php
/**
 * ArticlesHooks Plugin
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * The class.hooks.php file is essentially a giant plugin container for an app
 * that is automatically enabled when this app is.
 */
class ArticlesHooks extends Gdn_Plugin {
    /**
     * Add link to the articles controller in the main menu.
     *
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        if ($sender->Menu) {
            $isMobileThemeActive = isMobileThemeActive();
            $isArticlesDefaultController = Gdn::router()->getDestination('DefaultController') === 'articles';

            // Show Articles menu link.
            // If the mobile theme is enabled, it will only show on the mobile theme's
            // menu if articles isn't set as the DefaultController route.
            if (c('Articles.ShowArticlesMenuLink', true)
                && (!$isMobileThemeActive || ($isMobileThemeActive && !$isArticlesDefaultController))
            ) {
                $sender->Menu->addLink('Articles', t('Articles'), '/articles', 'Articles.Articles.View');
            }

            // Show Discussions menu link on mobile theme if articles is set as the DefaultController route
            // and if Vanilla is enabled because the mobile theme has a Home link.
            if ($isMobileThemeActive && $isArticlesDefaultController && Gdn::applicationManager()
                    ->isEnabled('Vanilla')
            ) {
                $sender->Menu->addLink('Discussions', t('Discussions'), '/discussions', 'Vanilla.Discussions.View');
            }

            if (c('Articles.ShowCategoriesMenuLink', false)) {
                $sender->Menu->addLink('ArticleCategories', t('Article Categories'), '/articles/categories',
                    'Articles.Articles.View');
            }
        }
    }

    /**
     * Automatically executed when this application is enabled.
     */
    public function setup() {
        // Call structure.php to update database.
        include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'structure.php');
        include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'stub.php');

        // Save version number to config.
        $applicationInfo = array();
        include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'about.php');
        $version = arrayValue('Version', $applicationInfo['Articles'], false);
        if ($version) {
            $save = array('Articles.Version' => $version);
            saveToConfig($save);
        }
    }

    /**
     * The Index method of the Articles setting page.
     *
     * @param SettingsController $sender
     */
    public function controller_index($sender) {
        // Set required permission.
        $sender->permission('Garden.Settings.Manage');

        // Set up the configuration module.
        $configModule = new ConfigurationModule($sender);

        $configModule->initialize(array(
            'Articles.ShowArticlesMenuLink' => array(
                'LabelCode' => 'Show link to Articles page in main menu?',
                'Control' => 'Checkbox'
            ),
            'Articles.ShowCategoriesMenuLink' => array(
                'LabelCode' => 'Show link to Article Categories page in main menu?',
                'Control' => 'Checkbox'
            ),
            'Articles.Articles.ShowAuthorInfo' => array(
                'LabelCode' => 'Show author info (display name and bio) under articles?',
                'Control' => 'Checkbox'
            ),
            'Articles.Articles.ShowSimilarArticles' => array(
                'LabelCode' => 'Show a list of articles readers may be interested in under articles?',
                'Control' => 'Checkbox'
            ),
            'Articles.Comments.EnableThreadedComments' => array(
                'LabelCode' => 'Enable threaded (one level) comment replies?',
                'Control' => 'Checkbox'
            ),
            'Articles.Comments.AllowGuests' => array(
                'LabelCode' => 'Allow guest commenting?',
                'Control' => 'Checkbox'
            ),
            'Articles.TwitterUsername' => array(
                'LabelCode' => 'Enter a Twitter username associated with this website to be used for Twitter card meta tags (optional):',
                'Control' => 'TextBox'
            ),
            'Articles.Modules.ShowCategoriesAsDropDown' => array(
                'LabelCode' => 'Show article categories in the panel in a drop-down menu instead of a list?',
                'Control' => 'Checkbox'
            )
        ));

        $sender->ConfigurationModule = $configModule;

        $sender->title(t('Articles Settings'));

        $sender->addSideMenu('/settings/articles');
        $sender->View = $sender->fetchViewLocation('articles', 'settings', 'articles');
        $sender->render();
    }

    /**
     * Add the article search to the search.
     *
     * @param object $sender SearchModel
     */
    public function searchModel_search_handler($sender) {
        $searchModel = new ArticleSearchModel();
        $searchModel->search($sender);
    }

    /**
     * Create the Articles settings page.
     * Runs the Dispatch method which handles methods for the page.
     *
     * @param SettingsController $sender
     */
    public function settingsController_articles_create($sender) {
        $this->dispatch($sender, $sender->RequestArgs);
    }

    /**
     * Add links for the setting pages to the dashboard sidebar.
     *
     * @param Gdn_Controller $sender
     */
    public function base_getAppSettingsMenuItems_handler($sender) {
        $groupName = 'Articles';
        $menu = &$sender->EventArguments['SideMenu'];

        $menu->addItem($groupName, $groupName, false, array('class' => $groupName));
        $menu->addLink($groupName, t('Settings'), '/settings/articles', 'Garden.Settings.Manage');
        $menu->addLink($groupName, t('Categories'), '/settings/articles/categories', 'Garden.Settings.Manage');
    }

    /**
     * The Categories method of the Articles setting page.
     *
     * @param SettingsController $sender
     */
    public function controller_categories($sender) {
        $sender->title(t('Manage Article Categories'));

        // Set required permission.
        $sender->permission('Garden.Settings.Manage');

        // Add assets.
        $sender->addJsFile('js/library/nestedSortable.1.3.4/jquery-ui-1.8.11.custom.min.js');
        $sender->addJsFile('js/library/nestedSortable.1.3.4/jquery.ui.nestedSortable.js');

        // Set up the article category model.
        $articleCategoryModel = new ArticleCategoryModel();

        $categories = $articleCategoryModel->get();
        $sender->setData('Categories', $categories, true);

        $sender->addSideMenu('/settings/articles/categories');
        $sender->View = $sender->fetchViewLocation('categories', 'settings', 'articles');
        $sender->render();
    }

    /**
     * The Categories method of the Articles setting page.
     *
     * @param SettingsController $sender
     */
    public function controller_addCategory($sender) {
        // Set required permission.
        $sender->permission('Garden.Settings.Manage');

        // Add asset.
        $sender->addJsFile('jquery.gardencheckboxgrid.js');
        $sender->addJsFile('articles.js', 'articles');
        $sender->addJsFile('articles.settings.js', 'articles');

        // Set up the article category model.
        $sender->Form = new Gdn_Form();
        $articleCategoryModel = new ArticleCategoryModel();
        $sender->Form->setModel($articleCategoryModel);

        // If editing a category, then set the data in the form.
        $category = false;
        if ($sender->RequestArgs[0] === 'editcategory') {
            $articleCategoryID = (int)$sender->RequestArgs[1];

            if (is_numeric($articleCategoryID)) {
                $category = $articleCategoryModel->getByID($articleCategoryID);

                $category->CustomPermissions = $articleCategoryID == $category->PermissionArticleCategoryID;

                if ($category) {
                    $sender->Form->setData($category);
                } else {
                    throw notFoundException(t('Article category'));
                }
            } else {
                throw notFoundException(t('Article category'));
            }
        }

        // Set the title of the page.
        if (!$category) {
            $sender->title(t('Add Article Category'));
        } else {
            $sender->title(t('Edit Article Category'));
        }

        // Handle the form.
        if (!$sender->Form->authenticatedPostBack()) {
            if (!$category) {
                $sender->Form->addHidden('UrlCodeIsDefined', '0');
            } else {
                $sender->Form->addHidden('UrlCodeIsDefined', '1');
            }
        } else { // The form was saved.
            // Define some validation rules for the fields being saved.
            $sender->Form->validateRule('Name', 'function:ValidateRequired');
            $sender->Form->validateRule('UrlCode', 'function:ValidateRequired', t('URL code is required.'));

            // Manually validate certain fields.
            $formValues = $sender->Form->formValues();

            if ($category) {
                $formValues['ArticleCategoryID'] = $articleCategoryID;
                $sender->Form->setFormValue('ArticleCategoryID', $articleCategoryID);
            }

            // Format URL code before saving.
            $formValues['UrlCode'] = Gdn_Format::url($formValues['UrlCode']);

            // Check if URL code is in use by another category.
            $categoryWithNewUrlCode = (bool)$articleCategoryModel->getByUrlCode($formValues['UrlCode']);
            if ((!$category && $categoryWithNewUrlCode)
                || ($category && $categoryWithNewUrlCode && ($category->UrlCode != $formValues['UrlCode']))
            ) {
                $sender->Form->addError('The specified URL code is already in use by another category.', 'UrlCode');
            }

            // If there are no errors, then save the category.
            if ($sender->Form->errorCount() == 0) {
                if ($sender->Form->save($formValues)) {
                    if (!$category) {
                        // Inserting.
                        $sender->RedirectUrl = url('/settings/articles/categories/');
                        $sender->informMessage(t('New article category added successfully.'));
                    } else {
                        // Editing.
                        $sender->informMessage(t('The article category has been saved successfully.'));
                    }
                }
            }
        }

        // Get all of the currently selected role/permission combinations for this junction.
        $permissionModel = Gdn::permissionModel();
        if ($category) {
            $permissions = $permissionModel->getJunctionPermissions(array('JunctionID' => isset($articleCategoryID) ? $articleCategoryID : 0),
                'ArticleCategory', '', array('AddDefaults' => !$category->CustomPermissions));
        } else {
            $permissions = $permissionModel->getJunctionPermissions(array('JunctionID' => isset($articleCategoryID) ? $articleCategoryID : 0),
                'ArticleCategory');
        }
        $permissions = $permissionModel->unpivotPermissions($permissions, true);
        if ($sender->deliveryType() == DELIVERY_TYPE_ALL) {
            $sender->setData('PermissionData', $permissions, true);
        }

        $sender->addSideMenu('/settings/articles/categories/');
        $sender->View = $sender->fetchViewLocation('addcategory', 'settings', 'articles');
        $sender->render();
    }

    /**
     * @param SettingsController $sender
     */
    public function controller_aditCategory($sender) {
        $this->controller_addCategory($sender);
    }

    /**
     * @param SettingsController $sender
     */
    public function controller_deleteCategory($sender) {
        // Check permission.
        $sender->permission('Garden.Settings.Manage');

        // Set up head.
        $sender->title(t('Delete Article Category'));
        $sender->addSideMenu('/settings/articles/categories/');
        $sender->addJsFile('articles.js', 'articles');
        $sender->addJsFile('articles.settings.js', 'articles');

        // Get category ID.
        $articleCategoryID = false;
        if (isset($sender->RequestArgs[1]) && is_numeric($sender->RequestArgs[1])) {
            $articleCategoryID = $sender->RequestArgs[1];
        }

        // Get category data.
        $sender->Form = new Gdn_Form();
        $articleCategoryModel = new ArticleCategoryModel();
        $category = $articleCategoryModel->getByID($articleCategoryID);
        $sender->setData('Category', $category, true);

        if (!$category) {
            $sender->Form->addError('The specified article category could not be found.');
        } else {
            // Make sure the form knows which item we are deleting.
            $sender->Form->addHidden('ArticleCategoryID', $articleCategoryID);

            // Get a list of categories other than this one that can act as a replacement.
            $otherCategories = $articleCategoryModel->get(array(
                'ArticleCategoryID <>' => $articleCategoryID,
                'ArticleCategoryID >' => 0
            ));
            $sender->setData('OtherCategories', $otherCategories, true);

            if (!$sender->Form->authenticatedPostBack()) {
                $sender->Form->setFormValue('DeleteArticles', '1'); // Checked by default
            } else {
                $replacementArticleCategoryID = $sender->Form->getValue('ReplacementArticleCategoryID');
                $replacementCategory = $articleCategoryModel->getByID($replacementArticleCategoryID);
                // Error if:
                // 1. The category being deleted is the last remaining category.
                if ($otherCategories->numRows() == 0) {
                    $sender->Form->addError('You cannot remove the only remaining category.');
                }

                if ($sender->Form->errorCount() == 0) {
                    // Go ahead and delete the category.
                    try {
                        $articleCategoryModel->delete($category,
                            $sender->Form->getValue('ReplacementArticleCategoryID'));
                    } catch (Exception $ex) {
                        $sender->Form->addError($ex);
                    }

                    if ($sender->Form->errorCount() == 0) {
                        $sender->RedirectUrl = url('/settings/articles/categories/');
                        $sender->informMessage(t('Deleting article category...'));
                    }
                }
            }
        }

        // Render default view.
        $sender->View = $sender->fetchViewLocation('deletecategory', 'settings', 'articles');
        $sender->render();
    }

    /**
     * Adds 'Articles' tab to profiles and adds CSS & JS files to their head.
     *
     * @param ProfileController $sender
     */
    public function profileController_addProfileTabs_handler($sender) {
        if (is_object($sender->User) && ($sender->User->UserID > 0)) {
            $userID = $sender->User->UserID;

            // Add the article tab
            if (($sender->User->CountArticles > 0) || ArticleModel::canAdd('any', $userID)) {
                $articlesLabel = sprite('SpArticles', 'SpMyDrafts Sprite') . ' ' . t('Articles');

                if (c('Articles.Profile.ShowCounts', true)) {
                    $articlesLabel .= '<span class="Aside">' . countString(getValueR('User.CountArticles', $sender,
                            null), "/profile/count/articles?userid=$userID") . '</span>';
                }

                $sender->addProfileTab(t('Articles'),
                    'profile/articles/' . $sender->User->UserID . '/' . rawurlencode($sender->User->Name), 'Articles',
                    $articlesLabel);
            }

            // Add the article comments tab
            if (($sender->User->CountArticleComments > 0) || ArticleCommentModel::canAdd('any', $userID)) {
                $ArticleCommentsLabel = sprite('SpArticleComments', 'SpQuote Sprite') . ' ' . t('Article Comments');

                if (c('Articles.Profile.ShowCounts', true)) {
                    $ArticleCommentsLabel .= '<span class="Aside">' . countString(getValueR('User.CountArticleComments',
                            $sender, null), "/profile/count/articlecomments?userid=$userID") . '</span>';
                }

                $sender->addProfileTab(t('Article Comments'),
                    'profile/articlecomments/' . $sender->User->UserID . '/' . rawurlencode($sender->User->Name),
                    'ArticleComments', $ArticleCommentsLabel);
            }

            // Add the article tab's CSS and Javascript.
            $sender->addJsFile('jquery.gardenmorepager.js');
            $sender->addJsFile('articles.js');
        }
    }

    /**
     * Creates virtual 'Articles' method in ProfileController.
     *
     * @param ProfileController $sender
     */
    public function profileController_articles_create($sender, $userReference = '', $username = '', $page = '',
                                                      $userID = '') {
        // User must have at least one article or have permission to add articles for this page to be viewable.
        if (is_numeric($userReference)) {
            $user = Gdn::userModel()->getID($userReference);
        } else if (is_string($userReference)) {
            $user = Gdn::userModel()->getByUsername($userReference);
        }

        $userCanAddArticle = ArticleModel::canAdd('any', $user->UserID);
        if ($user && (!$userCanAddArticle || (!$userCanAddArticle && ($user->CountArticles == 0)))) {
            redirect(userUrl($user));
        }

        $sender->editMode(false);

        // Tell the ProfileController what tab to load
        $sender->getUserInfo($userReference, $username, $userID);
        $sender->_setBreadcrumbs(t('Articles'), '/profile/articles');
        $sender->setTabView('Articles', 'Articles', 'Profile', 'Articles');
        $sender->CountArticleCommentsPerPage = c('Articles.Articles.PerPage', 12);

        list($offset, $limit) = offsetLimit($page, Gdn::config('Articles.Articles.PerPage', 12));

        $articleModel = new ArticleModel();
        $articles = $articleModel->getByUser($sender->User->UserID, $offset, $limit,
            array('Status' => ArticleModel::STATUS_PUBLISHED))->result();
        $countArticles = $offset + $articleModel->LastArticleCount + 1;
        $sender->setData('Articles', $articles);

        $sender->ArticleCategoryModel = new ArticleCategoryModel();
        $sender->ArticleMediaModel = new ArticleMediaModel();

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $sender->Pager = $pagerFactory->getPager('MorePager', $sender);
        $sender->Pager->MoreCode = 'More Articles';
        $sender->Pager->LessCode = 'Newer Articles';
        $sender->Pager->ClientID = 'Pager';
        $sender->Pager->configure(
            $offset,
            $limit,
            $countArticles,
            userUrl($sender->User, '', 'articles') . '/{Page}'
        );

        // Deliver JSON data if necessary
        if ($sender->deliveryType() != DELIVERY_TYPE_ALL && $offset > 0) {
            $sender->setJson('LessRow', $sender->Pager->toString('less'));
            $sender->setJson('MoreRow', $sender->Pager->toString('more'));
            $sender->View = 'articles';
        }

        // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
        $sender->HandlerType = HANDLER_TYPE_NORMAL;

        // Do not show article options
        $sender->ShowOptions = false;

        if ($sender->Head) {
            // These pages offer only duplicate content to search engines and are a bit slow.
            $sender->Head->addTag('meta', array('name' => 'robots', 'content' => 'noindex,noarchive'));
        }

        // Render the ProfileController
        $sender->addCssFile('articles.css', 'articles');

        // Add CSS file for mobile theme if active.
        if (isMobileThemeActive()) {
            $sender->addCssFile('articles.mobile.css', 'articles');
        }

        $sender->render();
    }

    /**
     * Creates virtual 'Article Comments' method in ProfileController.
     *
     * @param ProfileController $sender
     */
    public function profileController_articleComments_create($sender, $userReference = '', $username = '',
                                                             $page = '', $userID = '') {
        // User must have at least one comment or have permission to add comments for this page to be viewable.
        if (is_numeric($userReference)) {
            $user = Gdn::userModel()->getID($userReference);
        } else if (is_string($userReference)) {
            $user = Gdn::userModel()->getByUsername($userReference);
        }

        $userCanAddComment = ArticleCommentModel::canAdd('any', $user->UserID);
        if ($user && (!$userCanAddComment || (!$userCanAddComment && ($user->CountArticleComments == 0)))) {
            redirect(userUrl($user));
        }

        $sender->editMode(false);

        // Tell the ProfileController what tab to load
        $sender->getUserInfo($userReference, $username, $userID);
        $sender->_setBreadcrumbs(t('Article Comments'), '/profile/articlecomments');
        $sender->setTabView('Article Comments', 'Comments', 'Profile', 'Articles');
        $sender->CountArticleCommentsPerPage = c('Articles.Comments.PerPage', 30);

        list($offset, $limit) = offsetLimit($page, Gdn::config('Articles.Comments.PerPage', 30));

        $articleCommentModel = new ArticleCommentModel();
        $comments = $articleCommentModel->getByUser($sender->User->UserID, $offset, $limit)->result();
        $countArticleComments = $offset + $articleCommentModel->LastCommentCount + 1;
        $sender->setData('Comments', $comments);

        $sender->ArticleModel = new ArticleModel();

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $sender->Pager = $pagerFactory->getPager('MorePager', $sender);
        $sender->Pager->MoreCode = 'More Article Comments';
        $sender->Pager->LessCode = 'Newer Article Comments';
        $sender->Pager->ClientID = 'Pager';
        $sender->Pager->configure(
            $offset,
            $limit,
            $countArticleComments,
            userUrl($sender->User, '', 'articlecomments') . '/{Page}'
        );

        // Deliver JSON data if necessary
        if ($sender->deliveryType() != DELIVERY_TYPE_ALL && $offset > 0) {
            $sender->setJson('LessRow', $sender->Pager->toString('less'));
            $sender->setJson('MoreRow', $sender->Pager->toString('more'));
            $sender->View = 'comments';
        }

        // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
        $sender->HandlerType = HANDLER_TYPE_NORMAL;

        // Do not show article options
        $sender->ShowOptions = false;

        if ($sender->Head) {
            // These pages offer only duplicate content to search engines and are a bit slow.
            $sender->Head->addTag('meta', array('name' => 'robots', 'content' => 'noindex,noarchive'));
        }

        // Render the ProfileController
        $sender->render();
    }

    /**
     * Load author meta into the form when editing.
     *
     * @param ProfileController $sender ProfileController
     */
    public function profileController_beforeEdit_handler($sender) {
        $userMeta = Gdn::userModel()->getMeta($sender->User->UserID, 'Articles.%', 'Articles.');
        if (!is_array($userMeta)) {
            return;
        }

        if (isset($userMeta['AuthorDisplayName'])) {
            $sender->Form->setValue('Articles.AuthorDisplayName', $userMeta['AuthorDisplayName']);
        }

        if (isset($userMeta['AuthorBio'])) {
            $sender->Form->setValue('Articles.AuthorBio', $userMeta['AuthorBio']);
        }
    }

    /**
     * Display author meta inputs when editing.
     *
     * @param ProfileController $sender ProfileController
     */
    public function profileController_editMyAccountAfter_handler($sender) {
        if (Gdn::session()
            ->checkPermission(array('Garden.Users.Edit', 'Articles.Articles.Add'), false, 'ArticleCategory', 'any')
        ) {
            echo wrap(
                $sender->Form->label('Author Display Name', 'Articles.AuthorDisplayName') .
                $sender->Form->textbox('Articles.AuthorDisplayName'),
                'li');

            echo wrap(
                $sender->Form->label('Author Bio', 'Articles.AuthorBio') .
                $sender->Form->textbox('Articles.AuthorBio', array('multiline' => true)),
                'li');
        }
    }

    /**
     * Display Articles user meta fields on user profile page.
     *
     * @param ProfileController $sender ProfileController
     */
    public function profileController_afterUserInfo_handler($sender) {
        // Get the custom fields.
        $userMeta = Gdn::userModel()->getMeta($sender->User->UserID, 'Articles.%', 'Articles.');
        if (!is_array($userMeta)) {
            return;
        }

        // Display author display name.
        if (isset($userMeta['AuthorDisplayName']) && ($userMeta['AuthorDisplayName'] != '')) {
            echo '<dl id="BoxProfileAuthorDisplayName" class="About">';
            echo ' <dt class="Articles Profile AuthorDisplayName">' . t('Author Display Name') . '</dt> ';
            echo ' <dd class="Articles Profile AuthorDisplayName">'
                . Gdn_Format::html($userMeta['AuthorDisplayName']) . '</dd> ';
            echo '</dl>';
        }

        // Display author bio.
        if (isset($userMeta['AuthorBio']) && ($userMeta['AuthorBio'] != '')) {
            echo '<dl id="BoxProfileAuthorBio" class="About">';
            echo ' <dt class="Articles Profile AuthorBio">' . t('Author Bio') . '</dt> ';
            echo ' <dd class="Articles Profile AuthorBio">' . Gdn_Format::html($userMeta['AuthorBio']) . '</dd> ';
            echo '</dl>';
        }
    }

    /**
     * Save the author meta if it exists.
     *
     * @param UserModel $sender UserModel
     */
    public function userModel_afterSave_handler($sender) {
        $userID = val('UserID', $sender->EventArguments);
        $formValues = val('FormPostValues', $sender->EventArguments, array());
        $authorInfo = array_intersect_key($formValues,
            array('Articles.AuthorDisplayName' => 1, 'Articles.AuthorBio' => 1));

        foreach ($authorInfo as $k => $v) {
            Gdn::userMetaModel()->setUserMeta($userID, $k, $v);
        }
    }

    /**
     * Remove Articles data when deleting a user.
     *
     * @param UserModel $sender UserModel.
     */
    public function userModel_beforeDeleteUser_handler($sender) {
        $userID = val('UserID', $sender->EventArguments);
        $options = val('Options', $sender->EventArguments, array());
        $options = is_array($options) ? $options : array();
        $content =& $sender->EventArguments['Content'];

        $this->deleteUserData($userID, $options, $content);
    }

    /**
     * Adds count jobs for the DbaModel.
     *
     * @param DbaController $sender
     */
    public function dbaController_countJobs_handler($sender) {
        $counts = array(
            'Article' => array('CountArticleComments', 'FirstArticleCommentID', 'LastArticleCommentID',
                'DateLastArticleComment', 'LastArticleCommentUserID'),
            'ArticleCategory' => array('CountArticles', 'CountArticleComments', 'LastArticleID', 'LastArticleCommentID',
                'LastDateInserted')
        );

        foreach ($counts as $table => $columns) {
            foreach ($columns as $Column) {
                $name = "Recalculate $table.$Column";
                $url = "/dba/counts.json?" . http_build_query(array('table' => $table, 'column' => $Column));

                $sender->Data['Jobs'][$name] = $url;
            }
        }
    }

    /**
     * Add link to Articles Dashboard to the MeModule fly-out menu.
     *
     * @param MeModule $sender
     */
    public function meModule_flyoutMenu_handler($sender) {
        $session = Gdn::session();

        $permissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        if ($session->checkPermission($permissionsAllowed, false, 'ArticleCategory', 'any')) {
            echo wrap(anchor(sprite('SpMyDiscussions') . ' ' . t('Articles Dashboard'), '/compose'), 'li');
        }
    }

    // TODO: The search/results.php view outputs a UserAnchor; the guest name gets linked to a profile.
    //    // Set the username of article guest comment search results to the GuestName.
    //    public function SearchController_BeforeItemContent_Handler($sender) {
    //        $Row = &$sender->EventArguments['Row'];
    //
    //        if (($Row->RecordType === 'ArticleComment') && !$Row->UserID) {
    //            $ArticleCommentModel = new ArticleCommentModel();
    //            $Comment = $ArticleCommentModel->getByID($Row->PrimaryID);
    //
    //            $Row->Name = $Comment->GuestName;
    //        }
    //    }

    public function discussionsController_render_before($sender) {
        $sender->addModule('ArticlesModule');
    }

    public function categoriesController_render_before($sender) {
        $sender->addModule('ArticlesModule');
    }

    /**
     * Provide default permissions for roles, based on the value in their Type column.
     *
     * @param PermissionModel $sender Instance of permission model that fired the event
     */
    public function permissionModel_defaultPermissions_handler($sender) {
        // Guest defaults
        $guestDefaults = array('Articles.Articles.View' => 1);
        $sender->addDefault(RoleModel::TYPE_GUEST, $guestDefaults);
        $sender->addDefault(RoleModel::TYPE_GUEST, $guestDefaults, 'ArticleCategory', -1);

        // Unconfirmed defaults
        $unconfirmedDefaults = array('Articles.Articles.View' => 1);
        $sender->addDefault(RoleModel::TYPE_UNCONFIRMED, $unconfirmedDefaults);
        $sender->addDefault(RoleModel::TYPE_UNCONFIRMED, $unconfirmedDefaults, 'ArticleCategory', -1);

        // Applicant defaults
        $applicantDefaults = array('Articles.Articles.View' => 1);
        $sender->addDefault(RoleModel::TYPE_APPLICANT, $applicantDefaults);
        $sender->addDefault(RoleModel::TYPE_APPLICANT, $applicantDefaults, 'ArticleCategory', -1);

        // Member defaults
        $memberDefaults = array(
            'Articles.Articles.View' => 1,
            'Articles.Comments.Add' => 1
        );
        $sender->addDefault(RoleModel::TYPE_MEMBER, $memberDefaults);
        $sender->addDefault(RoleModel::TYPE_MEMBER, $memberDefaults, 'ArticleCategory', -1);

        // Moderator defaults
        $moderatorDefaults = array(
            'Articles.Articles.Add' => 1,
            'Articles.Articles.Close' => 1,
            'Articles.Articles.Delete' => 1,
            'Articles.Articles.Edit' => 1,
            'Articles.Articles.View' => 1,
            'Articles.Comments.Add' => 1,
            'Articles.Comments.Delete' => 1,
            'Articles.Comments.Edit' => 1
        );
        $sender->addDefault(RoleModel::TYPE_MODERATOR, $moderatorDefaults);
        $sender->addDefault(RoleModel::TYPE_MODERATOR, $moderatorDefaults, 'ArticleCategory', -1);

        // Administrator defaults
        $administratorDefaults = array(
            'Articles.Articles.Add' => 1,
            'Articles.Articles.Close' => 1,
            'Articles.Articles.Delete' => 1,
            'Articles.Articles.Edit' => 1,
            'Articles.Articles.View' => 1,
            'Articles.Comments.Add' => 1,
            'Articles.Comments.Delete' => 1,
            'Articles.Comments.Edit' => 1
        );
        $sender->addDefault(RoleModel::TYPE_ADMINISTRATOR, $administratorDefaults);
        $sender->addDefault(RoleModel::TYPE_ADMINISTRATOR, $administratorDefaults, 'ArticleCategory', -1);
    }

    /**
     * Delete all of the Articles related information for a specific user.
     *
     * @param int $userID The ID of the user to delete.
     * @param array $options An array of options:
     *  - DeleteMethod: One of delete, wipe, or null
     */
    private function deleteUserData($userID, $options = array(), &$data = null) {
        $sql = Gdn::sql();

        // Comment deletion depends on method selected.
        $deleteMethod = val('DeleteMethod', $options, 'delete');
        if ($deleteMethod == 'delete') {
            // Clear out the last posts to the categories.
            $sql->update('ArticleCategory c')
                ->join('Article a', 'a.ArticleID = c.LastArticleID')
                ->where('a.InsertUserID', $userID)
                ->set('c.LastArticleID', null)
                ->set('c.LastArticleCommentID', null)
                ->put();

            $sql->update('ArticleCategory c')
                ->join('ArticleComment ac', 'ac.ArticleCommentID = c.LastArticleCommentID')
                ->where('ac.InsertUserID', $userID)
                ->set('c.LastArticleID', null)
                ->set('c.LastArticleCommentID', null)
                ->put();

            // Grab all of the articles that the user has engaged in.
            $articleIDs = $sql
                ->select('ArticleID')
                ->from('ArticleComment')
                ->where('InsertUserID', $userID)
                ->groupBy('ArticleID')
                ->get()->resultArray();
            $articleIDs = consolidateArrayValuesByKey($articleIDs, 'ArticleID');

            Gdn::userModel()->getDelete('ArticleComment', array('InsertUserID' => $userID), $data);

            // Update the comment counts.
            $commentCounts = $sql
                ->select('ArticleID')
                ->select('ArticleCommentID', 'count', 'CountArticleComments')
                ->select('ArticleCommentID', 'max', 'LastArticleCommentID')
                ->whereIn('ArticleID', $articleIDs)
                ->groupBy('ArticleID')
                ->get('ArticleComment')->resultArray();

            foreach ($commentCounts as $row) {
                $sql->put('Article',
                    array('CountArticleComments' => $row['CountArticleComments'] + 1,
                        'LastArticleCommentID' => $row['LastArticleCommentID']),
                    array('ArticleID' => $row['ArticleID']));
            }

            // Update the last user IDs.
            $sql->update('Article a')
                ->join('ArticleComment ac', 'a.LastArticleCommentID = ac.ArticleCommentID', 'left')
                ->set('a.LastArticleCommentUserID', 'ac.InsertUserID', false, false)
                ->set('a.DateLastArticleComment', 'ac.DateInserted', false, false)
                ->whereIn('a.ArticleID', $articleIDs)
                ->put();

            // Update the last posts.
            $articles = $sql
                ->whereIn('ArticleID', $articleIDs)
                ->where('LastArticleCommentUserID', $userID)
                ->get('Article');

            // Delete the user's articles
            Gdn::userModel()->getDelete('Article', array('InsertUserID' => $userID), $data);

            // Update the appropriate recent posts in the categories.
            $articleCategoryModel = new ArticleCategoryModel();
            $categories = $articleCategoryModel->getWhere(array('LastArticleID' => null))->resultArray();
            foreach ($categories as $category) {
                $articleCategoryModel->setRecentPost($category['ArticleCategoryID']);
            }
        } else if ($deleteMethod == 'wipe') {
            // Erase the user's articles
            $sql->update('Article')
                ->set('Status', 'Trash')
                ->where('InsertUserID', $userID)
                ->put();

            $sql->update('ArticleComment')
                ->set('Body', t('The user and all related content has been deleted.'))
                ->set('Format', 'Deleted')
                ->where('InsertUserID', $userID)
                ->put();
        }

        // Remove the user's profile information related to this application
        $sql->update('User')
            ->set(array(
                'CountArticles' => 0,
                'CountArticleComments' => 0))
            ->where('UserID', $userID)
            ->put();
    }
}
