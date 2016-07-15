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
 * The class.hooks.php file is essentially a giant plugin container for an app
 * that is automatically enabled when this app is.
 */
class ArticlesHooks extends Gdn_Plugin {
    /**
     * Add link to the articles controller in the main menu.
     *
     * @param Gdn_Controller $Sender
     */
    public function Base_Render_Before($Sender) {
        if ($Sender->Menu) {
            $isMobileThemeActive = Gdn::themeManager()->currentTheme() === 'mobile';
            $isArticlesDefaultController = Gdn::router()->getDestination('DefaultController') === 'articles';

            // Show Articles menu link.
            // If the mobile theme is enabled, it will only show on the mobile theme's
            // menu if articles isn't set as the DefaultController route.
            if (c('Articles.ShowArticlesMenuLink', true)
                    && (!$isMobileThemeActive || ($isMobileThemeActive && !$isArticlesDefaultController))) {
                $Sender->Menu->AddLink('Articles', t('Articles'), '/articles', 'Articles.Articles.View');
            }

            // Show Discussions menu link on mobile theme if articles is set as the DefaultController route
            // and if Vanilla is enabled because the mobile theme has a Home link.
            if ($isMobileThemeActive && $isArticlesDefaultController && Gdn::ApplicationManager()->IsEnabled('Vanilla')) {
                $Sender->Menu->AddLink('Discussions', t('Discussions'), '/discussions', 'Vanilla.Discussions.View');
            }

            if (c('Articles.ShowCategoriesMenuLink', false)) {
                $Sender->Menu->AddLink('ArticleCategories', t('Article Categories'), '/articles/categories',
                    'Articles.Articles.View');
            }
        }
    }

    /**
     * Automatically executed when this application is enabled.
     */
    public function Setup() {
        // Call structure.php to update database.
        include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'structure.php');
        include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'stub.php');

        // Save version number to config.
        $ApplicationInfo = array();
        include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'about.php');
        $Version = arrayValue('Version', $ApplicationInfo['Articles'], false);
        if ($Version) {
            $Save = array('Articles.Version' => $Version);
            SaveToConfig($Save);
        }
    }

    /**
     * Add the article search to the search.
     *
     * @param object $Sender SearchModel
     */
    public function SearchModel_Search_Handler($Sender) {
        $SearchModel = new ArticleSearchModel();
        $SearchModel->Search($Sender);
    }

    /**
     * Create the Articles settings page.
     * Runs the Dispatch method which handles methods for the page.
     *
     * @param SettingsController $Sender
     */
    public function SettingsController_Articles_Create($Sender) {
        $this->Dispatch($Sender, $Sender->RequestArgs);
    }

    /**
     * Add links for the setting pages to the dashboard sidebar.
     *
     * @param Gdn_Controller $Sender
     */
    public function Base_GetAppSettingsMenuItems_Handler($Sender) {
        $GroupName = 'Articles';
        $Menu = &$Sender->EventArguments['SideMenu'];

        $Menu->AddItem($GroupName, $GroupName, false, array('class' => $GroupName));
        $Menu->AddLink($GroupName, t('Settings'), '/settings/articles', 'Garden.Settings.Manage');
        $Menu->AddLink($GroupName, t('Categories'), '/settings/articles/categories', 'Garden.Settings.Manage');
    }

    /**
     * The Index method of the Articles setting page.
     *
     * @param SettingsController $Sender
     */
    public function Controller_Index($Sender) {
        // Set required permission.
        $Sender->permission('Garden.Settings.Manage');

        // Set up the configuration module.
        $ConfigModule = new ConfigurationModule($Sender);

        $ConfigModule->Initialize(array(
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

        $Sender->ConfigurationModule = $ConfigModule;

        $Sender->title(t('Articles Settings'));

        $Sender->AddSideMenu('/settings/articles');
        $Sender->View = $Sender->fetchViewLocation('articles', 'settings', 'articles');
        $Sender->render();
    }

    /**
     * The Categories method of the Articles setting page.
     *
     * @param SettingsController $Sender
     */
    public function Controller_Categories($Sender) {
        $Sender->title(t('Manage Article Categories'));

        // Set required permission.
        $Sender->permission('Garden.Settings.Manage');

        // Add assets.
        $Sender->addJsFile('js/library/nestedSortable.1.3.4/jquery-ui-1.8.11.custom.min.js');
        $Sender->addJsFile('js/library/nestedSortable.1.3.4/jquery.ui.nestedSortable.js');

        // Set up the article category model.
        $ArticleCategoryModel = new ArticleCategoryModel();

        $Categories = $ArticleCategoryModel->get();
        $Sender->setData('Categories', $Categories, true);

        $Sender->AddSideMenu('/settings/articles/categories');
        $Sender->View = $Sender->fetchViewLocation('categories', 'settings', 'articles');
        $Sender->render();
    }

    /**
     * The Categories method of the Articles setting page.
     *
     * @param SettingsController $Sender
     */
    public function Controller_AddCategory($Sender) {
        // Set required permission.
        $Sender->permission('Garden.Settings.Manage');

        // Add asset.
        $Sender->addJsFile('jquery.gardencheckboxgrid.js');
        $Sender->addJsFile('articles.js', 'articles');
        $Sender->addJsFile('articles.settings.js', 'articles');

        // Set up the article category model.
        $Sender->Form = new Gdn_Form();
        $ArticleCategoryModel = new ArticleCategoryModel();
        $Sender->Form->setModel($ArticleCategoryModel);

        // If editing a category, then set the data in the form.
        $Category = false;
        if ($Sender->RequestArgs[0] === 'editcategory') {
            $ArticleCategoryID = (int)$Sender->RequestArgs[1];

            if (is_numeric($ArticleCategoryID)) {
                $Category = $ArticleCategoryModel->getByID($ArticleCategoryID);

                $Category->CustomPermissions = $ArticleCategoryID == $Category->PermissionArticleCategoryID;

                if ($Category)
                    $Sender->Form->setData($Category);
                else
                    throw notFoundException(t('Article category'));
            } else {
                throw notFoundException(t('Article category'));
            }
        }

        // Set the title of the page.
        if (!$Category)
            $Sender->title(t('Add Article Category'));
        else
            $Sender->title(t('Edit Article Category'));

        // Handle the form.
        if (!$Sender->Form->authenticatedPostBack()) {
            if (!$Category)
                $Sender->Form->addHidden('UrlCodeIsDefined', '0');
            else
                $Sender->Form->addHidden('UrlCodeIsDefined', '1');
        } else { // The form was saved.
            // Define some validation rules for the fields being saved.
            $Sender->Form->validateRule('Name', 'function:ValidateRequired');
            $Sender->Form->validateRule('UrlCode', 'function:ValidateRequired', t('URL code is required.'));

            // Manually validate certain fields.
            $FormValues = $Sender->Form->formValues();

            if ($Category) {
                $FormValues['ArticleCategoryID'] = $ArticleCategoryID;
                $Sender->Form->setFormValue('ArticleCategoryID', $ArticleCategoryID);
            }

            // Format URL code before saving.
            $FormValues['UrlCode'] = Gdn_Format::url($FormValues['UrlCode']);

            // Check if URL code is in use by another category.
            $CategoryWithNewUrlCode = (bool)$ArticleCategoryModel->getByUrlCode($FormValues['UrlCode']);
            if ((!$Category && $CategoryWithNewUrlCode)
                || ($Category && $CategoryWithNewUrlCode && ($Category->UrlCode != $FormValues['UrlCode']))
            )
                $Sender->Form->addError('The specified URL code is already in use by another category.', 'UrlCode');

            // If there are no errors, then save the category.
            if ($Sender->Form->errorCount() == 0) {
                if ($Sender->Form->save($FormValues)) {
                    if (!$Category) {
                        // Inserting.
                        $Sender->RedirectUrl = url('/settings/articles/categories/');
                        $Sender->InformMessage(t('New article category added successfully.'));
                    } else {
                        // Editing.
                        $Sender->InformMessage(t('The article category has been saved successfully.'));
                    }
                }
            }
        }

        // Get all of the currently selected role/permission combinations for this junction.
        $PermissionModel = Gdn::permissionModel();
        if ($Category) {
            $Permissions = $PermissionModel->GetJunctionPermissions(array('JunctionID' => $ArticleCategoryID), 'ArticleCategory', '', array('AddDefaults' => !$Category->CustomPermissions));
        } else {
            $Permissions = $PermissionModel->GetJunctionPermissions(array('JunctionID' => isset($ArticleCategoryID) ? $ArticleCategoryID : 0), 'ArticleCategory');
        }
        $Permissions = $PermissionModel->UnpivotPermissions($Permissions, true);
        if ($Sender->deliveryType() == DELIVERY_TYPE_ALL) {
            $Sender->setData('PermissionData', $Permissions, true);
        }

        $Sender->AddSideMenu('/settings/articles/categories/');
        $Sender->View = $Sender->fetchViewLocation('addcategory', 'settings', 'articles');
        $Sender->render();
    }

    /**
     * @param SettingsController $Sender
     */
    public function Controller_EditCategory($Sender) {
        $this->Controller_AddCategory($Sender);
    }

    /**
     * @param SettingsController $Sender
     */
    public function Controller_DeleteCategory($Sender) {
        // Check permission.
        $Sender->permission('Garden.Settings.Manage');

        // Set up head.
        $Sender->title(t('Delete Article Category'));
        $Sender->AddSideMenu('/settings/articles/categories/');
        $Sender->addJsFile('articles.js', 'articles');
        $Sender->addJsFile('articles.settings.js', 'articles');

        // Get category ID.
        $ArticleCategoryID = false;
        if (isset($Sender->RequestArgs[1]) && is_numeric($Sender->RequestArgs[1]))
            $ArticleCategoryID = $Sender->RequestArgs[1];

        // Get category data.
        $Sender->Form = new Gdn_Form();
        $ArticleCategoryModel = new ArticleCategoryModel();
        $Category = $ArticleCategoryModel->getByID($ArticleCategoryID);
        $Sender->setData('Category', $Category, true);

        if (!$Category) {
            $Sender->Form->addError('The specified article category could not be found.');
        } else {
            // Make sure the form knows which item we are deleting.
            $Sender->Form->addHidden('ArticleCategoryID', $ArticleCategoryID);

            // Get a list of categories other than this one that can act as a replacement.
            $OtherCategories = $ArticleCategoryModel->get(array(
                'ArticleCategoryID <>' => $ArticleCategoryID,
                'ArticleCategoryID >' => 0
            ));
            $Sender->setData('OtherCategories', $OtherCategories, true);

            if (!$Sender->Form->authenticatedPostBack()) {
                $Sender->Form->setFormValue('DeleteArticles', '1'); // Checked by default
            } else {
                $ReplacementArticleCategoryID = $Sender->Form->getValue('ReplacementArticleCategoryID');
                $ReplacementCategory = $ArticleCategoryModel->getByID($ReplacementArticleCategoryID);
                // Error if:
                // 1. The category being deleted is the last remaining category.
                if ($OtherCategories->numRows() == 0)
                    $Sender->Form->addError('You cannot remove the only remaining category.');

                if ($Sender->Form->errorCount() == 0) {
                    // Go ahead and delete the category.
                    try {
                        $ArticleCategoryModel->delete($Category,
                            $Sender->Form->getValue('ReplacementArticleCategoryID'));
                    } catch (Exception $ex) {
                        $Sender->Form->addError($ex);
                    }

                    if ($Sender->Form->errorCount() == 0) {
                        $Sender->RedirectUrl = url('/settings/articles/categories/');
                        $Sender->InformMessage(t('Deleting article category...'));
                    }
                }
            }
        }

        // Render default view.
        $Sender->View = $Sender->fetchViewLocation('deletecategory', 'settings', 'articles');
        $Sender->render();
    }

    /**
     * Adds 'Articles' tab to profiles and adds CSS & JS files to their head.
     *
     * @param ProfileController $Sender
     */
    public function ProfileController_AddProfileTabs_Handler($Sender) {
        if (is_object($Sender->User) && ($Sender->User->UserID > 0)) {
            $UserID = $Sender->User->UserID;

            // Add the article tab
            if (($Sender->User->CountArticles > 0) || ArticleModel::canAdd('any', $UserID)) {
                $ArticlesLabel = Sprite('SpArticles', 'SpMyDrafts Sprite') . ' ' . t('Articles');

                if (c('Articles.Profile.ShowCounts', true))
                    $ArticlesLabel .= '<span class="Aside">' . CountString(GetValueR('User.CountArticles', $Sender,
                            null), "/profile/count/articles?userid=$UserID") . '</span>';

                $Sender->AddProfileTab(t('Articles'),
                    'profile/articles/' . $Sender->User->UserID . '/' . rawurlencode($Sender->User->Name), 'Articles',
                    $ArticlesLabel);
            }

            // Add the article comments tab
            if (($Sender->User->CountArticleComments > 0) || ArticleCommentModel::canAdd('any', $UserID)) {
                $ArticleCommentsLabel = Sprite('SpArticleComments', 'SpQuote Sprite') . ' ' . t('Article Comments');

                if (c('Articles.Profile.ShowCounts', true))
                    $ArticleCommentsLabel .= '<span class="Aside">' . CountString(GetValueR('User.CountArticleComments',
                            $Sender, null), "/profile/count/articlecomments?userid=$UserID") . '</span>';

                $Sender->AddProfileTab(t('Article Comments'),
                    'profile/articlecomments/' . $Sender->User->UserID . '/' . rawurlencode($Sender->User->Name),
                    'ArticleComments', $ArticleCommentsLabel);
            }

            // Add the article tab's CSS and Javascript.
            $Sender->addJsFile('jquery.gardenmorepager.js');
            $Sender->addJsFile('articles.js');
        }
    }

    /**
     * Creates virtual 'Articles' method in ProfileController.
     *
     * @param ProfileController $Sender
     */
    public function ProfileController_Articles_Create($Sender, $UserReference = '', $Username = '', $Page = '', $UserID = '') {
        // User must have at least one article or have permission to add articles for this page to be viewable.
        if (is_numeric($UserReference))
            $User = Gdn::userModel()->getID($UserReference);
        else if (is_string($UserReference))
            $User = Gdn::userModel()->getByUsername($UserReference);

        $UserCanAddArticle = ArticleModel::canAdd('any', $User->UserID);
        if ($User && (!$UserCanAddArticle || (!$UserCanAddArticle && ($User->CountArticles == 0))))
            redirect(userUrl($User));

        $Sender->EditMode(false);

        // Tell the ProfileController what tab to load
        $Sender->GetUserInfo($UserReference, $Username, $UserID);
        $Sender->_SetBreadcrumbs(t('Articles'), '/profile/articles');
        $Sender->SetTabView('Articles', 'Articles', 'Profile', 'Articles');
        $Sender->CountArticleCommentsPerPage = c('Articles.Articles.PerPage', 12);

        list($Offset, $Limit) = offsetLimit($Page, Gdn::config('Articles.Articles.PerPage', 12));

        $ArticleModel = new ArticleModel();
        $Articles = $ArticleModel->getByUser($Sender->User->UserID, $Offset, $Limit,
            array('Status' => ArticleModel::STATUS_PUBLISHED))->result();
        $CountArticles = $Offset + $ArticleModel->LastArticleCount + 1;
        $Sender->setData('Articles', $Articles);

        $Sender->ArticleCategoryModel = new ArticleCategoryModel();
        $Sender->ArticleMediaModel = new ArticleMediaModel();

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $Sender->Pager = $PagerFactory->getPager('MorePager', $Sender);
        $Sender->Pager->MoreCode = 'More Articles';
        $Sender->Pager->LessCode = 'Newer Articles';
        $Sender->Pager->ClientID = 'Pager';
        $Sender->Pager->configure(
            $Offset,
            $Limit,
            $CountArticles,
            userUrl($Sender->User, '', 'articles') . '/{Page}'
        );

        // Deliver JSON data if necessary
        if ($Sender->deliveryType() != DELIVERY_TYPE_ALL && $Offset > 0) {
            $Sender->setJson('LessRow', $Sender->Pager->ToString('less'));
            $Sender->setJson('MoreRow', $Sender->Pager->ToString('more'));
            $Sender->View = 'articles';
        }

        // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
        $Sender->HandlerType = HANDLER_TYPE_NORMAL;

        // Do not show article options
        $Sender->ShowOptions = false;

        if ($Sender->Head) {
            // These pages offer only duplicate content to search engines and are a bit slow.
            $Sender->Head->addTag('meta', array('name' => 'robots', 'content' => 'noindex,noarchive'));
        }

        // Render the ProfileController
        $Sender->addCssFile('articles.css', 'articles');

        // Add CSS file for mobile theme if active.
        if (Gdn::themeManager()->currentTheme() === 'mobile') {
            $Sender->addCssFile('articles.mobile.css', 'articles');
        }

        $Sender->render();
    }

    /**
     * Creates virtual 'Article Comments' method in ProfileController.
     *
     * @param ProfileController $Sender
     */
    public function ProfileController_ArticleComments_Create($Sender, $UserReference = '', $Username = '',
                                                             $Page = '', $UserID = '') {
        // User must have at least one comment or have permission to add comments for this page to be viewable.
        if (is_numeric($UserReference))
            $User = Gdn::userModel()->getID($UserReference);
        else if (is_string($UserReference))
            $User = Gdn::userModel()->getByUsername($UserReference);

        $UserCanAddComment = ArticleCommentModel::canAdd('any', $User->UserID);
        if ($User && (!$UserCanAddComment || (!$UserCanAddComment && ($User->CountArticleComments == 0))))
            redirect(userUrl($User));

        $Sender->EditMode(false);

        // Tell the ProfileController what tab to load
        $Sender->GetUserInfo($UserReference, $Username, $UserID);
        $Sender->_SetBreadcrumbs(t('Article Comments'), '/profile/articlecomments');
        $Sender->SetTabView('Article Comments', 'Comments', 'Profile', 'Articles');
        $Sender->CountArticleCommentsPerPage = c('Articles.Comments.PerPage', 30);

        list($Offset, $Limit) = offsetLimit($Page, Gdn::config('Articles.Comments.PerPage', 30));

        $ArticleCommentModel = new ArticleCommentModel();
        $Comments = $ArticleCommentModel->getByUser($Sender->User->UserID, $Offset, $Limit)->result();
        $CountArticleComments = $Offset + $ArticleCommentModel->LastCommentCount + 1;
        $Sender->setData('Comments', $Comments);

        $Sender->ArticleModel = new ArticleModel();

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $Sender->Pager = $PagerFactory->getPager('MorePager', $Sender);
        $Sender->Pager->MoreCode = 'More Article Comments';
        $Sender->Pager->LessCode = 'Newer Article Comments';
        $Sender->Pager->ClientID = 'Pager';
        $Sender->Pager->configure(
            $Offset,
            $Limit,
            $CountArticleComments,
            userUrl($Sender->User, '', 'articlecomments') . '/{Page}'
        );

        // Deliver JSON data if necessary
        if ($Sender->deliveryType() != DELIVERY_TYPE_ALL && $Offset > 0) {
            $Sender->setJson('LessRow', $Sender->Pager->ToString('less'));
            $Sender->setJson('MoreRow', $Sender->Pager->ToString('more'));
            $Sender->View = 'comments';
        }

        // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
        $Sender->HandlerType = HANDLER_TYPE_NORMAL;

        // Do not show article options
        $Sender->ShowOptions = false;

        if ($Sender->Head) {
            // These pages offer only duplicate content to search engines and are a bit slow.
            $Sender->Head->addTag('meta', array('name' => 'robots', 'content' => 'noindex,noarchive'));
        }

        // Render the ProfileController
        $Sender->render();
    }

    /**
     * Load author meta into the form when editing.
     *
     * @param ProfileController $Sender ProfileController
     */
    public function ProfileController_BeforeEdit_Handler($Sender) {
        $UserMeta = Gdn::userModel()->GetMeta($Sender->User->UserID, 'Articles.%', 'Articles.');
        if (!is_array($UserMeta))
            return;

        if (isset($UserMeta['AuthorDisplayName']))
            $Sender->Form->setValue('Articles.AuthorDisplayName', $UserMeta['AuthorDisplayName']);

        if (isset($UserMeta['AuthorBio']))
            $Sender->Form->setValue('Articles.AuthorBio', $UserMeta['AuthorBio']);
    }

    /**
     * Display author meta inputs when editing.
     *
     * @param ProfileController $Sender ProfileController
     */
    public function ProfileController_EditMyAccountAfter_Handler($Sender) {
        if (Gdn::session()->checkPermission(array('Garden.Users.Edit', 'Articles.Articles.Add'), false, 'ArticleCategory', 'any')) {
            echo Wrap(
                $Sender->Form->Label('Author Display Name', 'Articles.AuthorDisplayName') .
                $Sender->Form->Textbox('Articles.AuthorDisplayName'),
                'li');

            echo Wrap(
                $Sender->Form->Label('Author Bio', 'Articles.AuthorBio') .
                $Sender->Form->Textbox('Articles.AuthorBio', array('multiline' => true)),
                'li');
        }
    }

    /**
     * Display custom fields on profile.
     *
     * @param UserInfoModule $Sender UserInfoModule
     */
    public function UserInfoModule_OnBasicInfo_Handler($Sender) {
        // Get the custom fields.
        $UserMeta = Gdn::userModel()->GetMeta($Sender->User->UserID, 'Articles.%', 'Articles.');
        if (!is_array($UserMeta))
            return;

        // Display author display name.
        if (isset($UserMeta['AuthorDisplayName']) && ($UserMeta['AuthorDisplayName'] != '')
                && ($Sender->User->Name != $UserMeta['AuthorDisplayName'])) {
            echo ' <dt class="Articles Profile AuthorDisplayName">' . t('Author Display Name') . '</dt> ';
            echo ' <dd class="Articles Profile AuthorDisplayName">' . Gdn_Format::Html($UserMeta['AuthorDisplayName']) . '</dd> ';
        }
    }

    /**
     * Display author bio on profile.
     *
     * @param ProfileController $Sender ProfileController
     */
    public function ProfileController_AfterUserInfo_Handler($Sender) {
        // Get the custom fields.
        $UserMeta = Gdn::userModel()->GetMeta($Sender->User->UserID, 'Articles.%', 'Articles.');
        if (!is_array($UserMeta))
            return;

        // Display author display name.
        if (isset($UserMeta['AuthorBio']) && ($UserMeta['AuthorBio'] != '')) {
            echo '<dl id="BoxProfileAuthorBio" class="About">';
            echo ' <dt class="Articles Profile AuthorBio">' . t('Author Bio') . '</dt> ';
            echo ' <dd class="Articles Profile AuthorBio">' . Gdn_Format::Html($UserMeta['AuthorBio']) . '</dd> ';
            echo '</dl>';
        }
    }

    /**
     * Save the author meta if it exists.
     *
     * @param UserModel $Sender UserModel
     */
    public function UserModel_AfterSave_Handler($Sender) {
        $UserID = val('UserID', $Sender->EventArguments);
        $FormValues = val('FormPostValues', $Sender->EventArguments, array());
        $AuthorInfo = array_intersect_key($FormValues,
            array('Articles.AuthorDisplayName' => 1, 'Articles.AuthorBio' => 1));

        foreach ($AuthorInfo as $k => $v) {
            Gdn::UserMetaModel()->SetUserMeta($UserID, $k, $v);
        }
    }

    /**
     * Remove Articles data when deleting a user.
     *
     * @param UserModel $Sender UserModel.
     */
    public function UserModel_BeforeDeleteUser_Handler($Sender) {
        $UserID = val('UserID', $Sender->EventArguments);
        $Options = val('Options', $Sender->EventArguments, array());
        $Options = is_array($Options) ? $Options : array();
        $Content =& $Sender->EventArguments['Content'];

        $this->DeleteUserData($UserID, $Options, $Content);
    }

    /**
     * Delete all of the Articles related information for a specific user.
     *
     * @param int $UserID The ID of the user to delete.
     * @param array $Options An array of options:
     *  - DeleteMethod: One of delete, wipe, or null
     */
    private function DeleteUserData($UserID, $Options = array(), &$Data = null) {
        $SQL = Gdn::SQL();

        // Comment deletion depends on method selected.
        $DeleteMethod = val('DeleteMethod', $Options, 'delete');
        if ($DeleteMethod == 'delete') {
            // Clear out the last posts to the categories.
            $SQL->update('ArticleCategory c')
                ->join('Article a', 'a.ArticleID = c.LastArticleID')
                ->where('a.InsertUserID', $UserID)
                ->set('c.LastArticleID', null)
                ->set('c.LastArticleCommentID', null)
                ->put();

            $SQL->update('ArticleCategory c')
                ->join('ArticleComment ac', 'ac.ArticleCommentID = c.LastArticleCommentID')
                ->where('ac.InsertUserID', $UserID)
                ->set('c.LastArticleID', null)
                ->set('c.LastArticleCommentID', null)
                ->put();

            // Grab all of the articles that the user has engaged in.
            $ArticleIDs = $SQL
                ->select('ArticleID')
                ->from('ArticleComment')
                ->where('InsertUserID', $UserID)
                ->groupBy('ArticleID')
                ->get()->resultArray();
            $ArticleIDs = consolidateArrayValuesByKey($ArticleIDs, 'ArticleID');

            Gdn::userModel()->GetDelete('ArticleComment', array('InsertUserID' => $UserID), $Data);

            // Update the comment counts.
            $CommentCounts = $SQL
                ->select('ArticleID')
                ->select('ArticleCommentID', 'count', 'CountArticleComments')
                ->select('ArticleCommentID', 'max', 'LastArticleCommentID')
                ->whereIn('ArticleID', $ArticleIDs)
                ->groupBy('ArticleID')
                ->get('ArticleComment')->resultArray();

            foreach ($CommentCounts as $Row) {
                $SQL->put('Article',
                    array('CountArticleComments' => $Row['CountArticleComments'] + 1,
                        'LastArticleCommentID' => $Row['LastArticleCommentID']),
                    array('ArticleID' => $Row['ArticleID']));
            }

            // Update the last user IDs.
            $SQL->update('Article a')
                ->join('ArticleComment ac', 'a.LastArticleCommentID = ac.ArticleCommentID', 'left')
                ->set('a.LastArticleCommentUserID', 'ac.InsertUserID', false, false)
                ->set('a.DateLastArticleComment', 'ac.DateInserted', false, false)
                ->whereIn('a.ArticleID', $ArticleIDs)
                ->put();

            // Update the last posts.
            $Articles = $SQL
                ->whereIn('ArticleID', $ArticleIDs)
                ->where('LastArticleCommentUserID', $UserID)
                ->get('Article');

            // Delete the user's articles
            Gdn::userModel()->GetDelete('Article', array('InsertUserID' => $UserID), $Data);

            // Update the appropriate recent posts in the categories.
            $ArticleCategoryModel = new ArticleCategoryModel();
            $Categories = $ArticleCategoryModel->getWhere(array('LastArticleID' => null))->resultArray();
            foreach ($Categories as $Category) {
                $ArticleCategoryModel->SetRecentPost($Category['ArticleCategoryID']);
            }
        } else if ($DeleteMethod == 'wipe') {
            // Erase the user's articles
            $SQL->update('Article')
                ->set('Status', 'Trash')
                ->where('InsertUserID', $UserID)
                ->put();

            $SQL->update('ArticleComment')
                ->set('Body', t('The user and all related content has been deleted.'))
                ->set('Format', 'Deleted')
                ->where('InsertUserID', $UserID)
                ->put();
        }

        // Remove the user's profile information related to this application
        $SQL->update('User')
            ->set(array(
                'CountArticles' => 0,
                'CountArticleComments' => 0))
            ->where('UserID', $UserID)
            ->put();
    }

    /**
     * Adds count jobs for the DbaModel.
     *
     * @param DbaController $Sender
     */
    public function DbaController_CountJobs_Handler($Sender) {
        $Counts = array(
            'Article' => array('CountArticleComments', 'FirstArticleCommentID', 'LastArticleCommentID',
                'DateLastArticleComment', 'LastArticleCommentUserID'),
            'ArticleCategory' => array('CountArticles', 'CountArticleComments', 'LastArticleID', 'LastArticleCommentID',
                'LastDateInserted')
        );

        foreach ($Counts as $Table => $Columns) {
            foreach ($Columns as $Column) {
                $Name = "Recalculate $Table.$Column";
                $Url = "/dba/counts.json?" . http_build_query(array('table' => $Table, 'column' => $Column));

                $Sender->Data['Jobs'][$Name] = $Url;
            }
        }
    }

    // TODO: The search/results.php view outputs a UserAnchor; the guest name gets linked to a profile.
    //    // Set the username of article guest comment search results to the GuestName.
    //    public function SearchController_BeforeItemContent_Handler($Sender) {
    //        $Row = &$Sender->EventArguments['Row'];
    //
    //        if (($Row->RecordType === 'ArticleComment') && !$Row->UserID) {
    //            $ArticleCommentModel = new ArticleCommentModel();
    //            $Comment = $ArticleCommentModel->getByID($Row->PrimaryID);
    //
    //            $Row->Name = $Comment->GuestName;
    //        }
    //    }

    /**
     * Add link to Articles Dashboard to the MeModule fly-out menu.
     *
     * @param MeModule $Sender
     */
    public function MeModule_FlyoutMenu_Handler($Sender) {
        $session = Gdn::session();

        $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        if ($session->checkPermission($PermissionsAllowed, false, 'ArticleCategory', 'any')) {
            echo Wrap(Anchor(Sprite('SpMyDiscussions').' '.T('Articles Dashboard'), '/compose'), 'li');
        }
    }

    public function DiscussionsController_Render_Before($Sender) {
        $Sender->addModule('ArticlesModule');
    }

    public function CategoriesController_Render_Before($Sender) {
        $Sender->addModule('ArticlesModule');
    }

    /**
     * Provide default permissions for roles, based on the value in their Type column.
     *
     * @param PermissionModel $Sender Instance of permission model that fired the event
     */
    public function PermissionModel_DefaultPermissions_Handler($Sender) {
        // Guest defaults
        $guestDefaults = array('Articles.Articles.View' => 1);
        $Sender->AddDefault(RoleModel::TYPE_GUEST, $guestDefaults);
        $Sender->AddDefault(RoleModel::TYPE_GUEST, $guestDefaults, 'ArticleCategory', -1);

        // Unconfirmed defaults
        $unconfirmedDefaults = array('Articles.Articles.View' => 1);
        $Sender->AddDefault(RoleModel::TYPE_UNCONFIRMED, $unconfirmedDefaults);
        $Sender->AddDefault(RoleModel::TYPE_UNCONFIRMED, $unconfirmedDefaults, 'ArticleCategory', -1);

        // Applicant defaults
        $applicantDefaults = array('Articles.Articles.View' => 1);
        $Sender->AddDefault(RoleModel::TYPE_APPLICANT, $applicantDefaults);
        $Sender->AddDefault(RoleModel::TYPE_APPLICANT, $applicantDefaults, 'ArticleCategory', -1);

        // Member defaults
        $memberDefaults = array(
            'Articles.Articles.View' => 1,
            'Articles.Comments.Add' => 1
        );
        $Sender->AddDefault(RoleModel::TYPE_MEMBER, $memberDefaults);
        $Sender->AddDefault(RoleModel::TYPE_MEMBER, $memberDefaults, 'ArticleCategory', -1);

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
        $Sender->AddDefault(RoleModel::TYPE_MODERATOR, $moderatorDefaults);
        $Sender->AddDefault(RoleModel::TYPE_MODERATOR, $moderatorDefaults, 'ArticleCategory', -1);

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
        $Sender->AddDefault(RoleModel::TYPE_ADMINISTRATOR, $administratorDefaults);
        $Sender->AddDefault(RoleModel::TYPE_ADMINISTRATOR, $administratorDefaults, 'ArticleCategory', -1);
    }
}
