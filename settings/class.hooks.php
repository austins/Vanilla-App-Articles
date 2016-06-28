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
            if (C('Articles.ShowArticlesMenuLink', true))
                $Sender->Menu->AddLink('Articles', T('Articles'), '/articles', 'Articles.Articles.View');

            if (C('Articles.ShowCategoriesMenuLink', false))
                $Sender->Menu->AddLink('ArticleCategories', T('Article Categories'), '/articles/categories',
                    'Articles.Articles.View');
        }
    }

    /**
     * Automatically executed when this application is enabled.
     */
    public function Setup() {
        // Initialize variables that are used for the structuring and stub inserts.
        $Database = Gdn::Database();
        $SQL = $Database->SQL();
        $Drop = false; // Gdn::Config('Articles.Version') === false ? true : false;
        $Explicit = true;

        // Call structure.php to update database.
        include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'structure.php');
        include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'stub.php');

        // Save version number to config.
        $ApplicationInfo = array();
        include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'about.php');
        $Version = ArrayValue('Version', $ApplicationInfo['Articles'], false);
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
        $Menu->AddLink($GroupName, T('Settings'), '/settings/articles', 'Garden.Settings.Manage');
        $Menu->AddLink($GroupName, T('Categories'), '/settings/articles/categories', 'Garden.Settings.Manage');
    }

    /**
     * The Index method of the Articles setting page.
     *
     * @param SettingsController $Sender
     */
    public function Controller_Index($Sender) {
        // Set required permission.
        $Sender->Permission('Garden.Settings.Manage');

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

        $Sender->Title(T('Articles Settings'));

        $Sender->AddSideMenu('/settings/articles');
        $Sender->View = $Sender->FetchViewLocation('articles', 'settings', 'articles');
        $Sender->Render();
    }

    /**
     * The Categories method of the Articles setting page.
     *
     * @param SettingsController $Sender
     */
    public function Controller_Categories($Sender) {
        $Sender->Title(T('Manage Article Categories'));

        // Set required permission.
        $Sender->Permission('Garden.Settings.Manage');

        // Add assets.
        $Sender->AddJsFile('js/library/nestedSortable.1.3.4/jquery-ui-1.8.11.custom.min.js');
        $Sender->AddJsFile('js/library/nestedSortable.1.3.4/jquery.ui.nestedSortable.js');

        // Set up the article category model.
        $ArticleCategoryModel = new ArticleCategoryModel();

        $Categories = $ArticleCategoryModel->Get();
        $Sender->SetData('Categories', $Categories, true);

        $Sender->AddSideMenu('/settings/articles/categories');
        $Sender->View = $Sender->FetchViewLocation('categories', 'settings', 'articles');
        $Sender->Render();
    }

    /**
     * The Categories method of the Articles setting page.
     *
     * @param SettingsController $Sender
     */
    public function Controller_AddCategory($Sender) {
        // Set required permission.
        $Sender->Permission('Garden.Settings.Manage');

        // Add asset.
        $Sender->addJsFile('jquery.gardencheckboxgrid.js');
        $Sender->AddJsFile('articles.js', 'articles');
        $Sender->AddJsFile('articles.settings.js', 'articles');

        // Set up the article category model.
        $Sender->Form = new Gdn_Form();
        $ArticleCategoryModel = new ArticleCategoryModel();
        $Sender->Form->SetModel($ArticleCategoryModel);

        // If editing a category, then set the data in the form.
        $Category = false;
        if ($Sender->RequestArgs[0] === 'editcategory') {
            $ArticleCategoryID = (int)$Sender->RequestArgs[1];

            if (is_numeric($ArticleCategoryID)) {
                $Category = $ArticleCategoryModel->GetByID($ArticleCategoryID);

                $Category->CustomPermissions = $ArticleCategoryID == $Category->PermissionArticleCategoryID;

                if ($Category)
                    $Sender->Form->SetData($Category);
                else
                    throw NotFoundException(T('Article category'));
            } else {
                throw NotFoundException(T('Article category'));
            }
        }

        // Set the title of the page.
        if (!$Category)
            $Sender->Title(T('Add Article Category'));
        else
            $Sender->Title(T('Edit Article Category'));

        // Handle the form.
        if (!$Sender->Form->AuthenticatedPostBack()) {
            if (!$Category)
                $Sender->Form->AddHidden('UrlCodeIsDefined', '0');
            else
                $Sender->Form->AddHidden('UrlCodeIsDefined', '1');
        } else { // The form was saved.
            // Define some validation rules for the fields being saved.
            $Sender->Form->ValidateRule('Name', 'function:ValidateRequired');
            $Sender->Form->ValidateRule('UrlCode', 'function:ValidateRequired', T('URL code is required.'));

            // Manually validate certain fields.
            $FormValues = $Sender->Form->FormValues();

            if ($Category) {
                $FormValues['ArticleCategoryID'] = $ArticleCategoryID;
                $Sender->Form->SetFormValue('ArticleCategoryID', $ArticleCategoryID);
            }

            // Format URL code before saving.
            $FormValues['UrlCode'] = Gdn_Format::Url($FormValues['UrlCode']);

            // Check if URL code is in use by another category.
            $CategoryWithNewUrlCode = (bool)$ArticleCategoryModel->GetByUrlCode($FormValues['UrlCode']);
            if ((!$Category && $CategoryWithNewUrlCode)
                || ($Category && $CategoryWithNewUrlCode && ($Category->UrlCode != $FormValues['UrlCode']))
            )
                $Sender->Form->AddError('The specified URL code is already in use by another category.', 'UrlCode');

            // If there are no errors, then save the category.
            if ($Sender->Form->ErrorCount() == 0) {
                if ($Sender->Form->Save($FormValues)) {
                    if (!$Category) {
                        // Inserting.
                        $Sender->RedirectUrl = Url('/settings/articles/categories/');
                        $Sender->InformMessage(T('New article category added successfully.'));
                    } else {
                        // Editing.
                        $Sender->InformMessage(T('The article category has been saved successfully.'));
                    }
                }
            }
        }

        // Get all of the currently selected role/permission combinations for this junction.
        $PermissionModel = Gdn::PermissionModel();
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
        $Sender->View = $Sender->FetchViewLocation('addcategory', 'settings', 'articles');
        $Sender->Render();
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
        $Sender->Permission('Garden.Settings.Manage');

        // Set up head.
        $Sender->Title(T('Delete Article Category'));
        $Sender->AddSideMenu('/settings/articles/categories/');
        $Sender->AddJsFile('articles.js', 'articles');
        $Sender->AddJsFile('articles.settings.js', 'articles');

        // Get category ID.
        $ArticleCategoryID = false;
        if (isset($Sender->RequestArgs[1]) && is_numeric($Sender->RequestArgs[1]))
            $ArticleCategoryID = $Sender->RequestArgs[1];

        // Get category data.
        $Sender->Form = new Gdn_Form();
        $ArticleCategoryModel = new ArticleCategoryModel();
        $Category = $ArticleCategoryModel->GetByID($ArticleCategoryID);
        $Sender->SetData('Category', $Category, true);

        if (!$Category) {
            $Sender->Form->AddError('The specified article category could not be found.');
        } else {
            // Make sure the form knows which item we are deleting.
            $Sender->Form->AddHidden('ArticleCategoryID', $ArticleCategoryID);

            // Get a list of categories other than this one that can act as a replacement.
            $OtherCategories = $ArticleCategoryModel->Get(array(
                'ArticleCategoryID <>' => $ArticleCategoryID,
                'ArticleCategoryID >' => 0
            ));
            $Sender->SetData('OtherCategories', $OtherCategories, true);

            if (!$Sender->Form->AuthenticatedPostBack()) {
                $Sender->Form->SetFormValue('DeleteArticles', '1'); // Checked by default
            } else {
                $ReplacementArticleCategoryID = $Sender->Form->GetValue('ReplacementArticleCategoryID');
                $ReplacementCategory = $ArticleCategoryModel->GetByID($ReplacementArticleCategoryID);
                // Error if:
                // 1. The category being deleted is the last remaining category.
                if ($OtherCategories->NumRows() == 0)
                    $Sender->Form->AddError('You cannot remove the only remaining category.');

                if ($Sender->Form->ErrorCount() == 0) {
                    // Go ahead and delete the category.
                    try {
                        $ArticleCategoryModel->Delete($Category,
                            $Sender->Form->GetValue('ReplacementArticleCategoryID'));
                    } catch (Exception $ex) {
                        $Sender->Form->AddError($ex);
                    }

                    if ($Sender->Form->ErrorCount() == 0) {
                        $Sender->RedirectUrl = Url('/settings/articles/categories/');
                        $Sender->InformMessage(T('Deleting article category...'));
                    }
                }
            }
        }

        // Render default view.
        $Sender->View = $Sender->FetchViewLocation('deletecategory', 'settings', 'articles');
        $Sender->Render();
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
            if (($Sender->User->CountArticles > 0) || Gdn::UserModel()->CheckPermission($Sender->User, 'Articles.Articles.Add')) {
                $ArticlesLabel = Sprite('SpArticles', 'SpMyDrafts Sprite') . ' ' . T('Articles');

                if (C('Articles.Profile.ShowCounts', true))
                    $ArticlesLabel .= '<span class="Aside">' . CountString(GetValueR('User.CountArticles', $Sender,
                            null), "/profile/count/articles?userid=$UserID") . '</span>';

                $Sender->AddProfileTab(T('Articles'),
                    'profile/articles/' . $Sender->User->UserID . '/' . rawurlencode($Sender->User->Name), 'Articles',
                    $ArticlesLabel);
            }

            // Add the article comments tab
            if (($Sender->User->CountArticleComments > 0) || Gdn::UserModel()->CheckPermission($Sender->User, 'Articles.Comments.Add')) {
                $ArticleCommentsLabel = Sprite('SpArticleComments', 'SpQuote Sprite') . ' ' . T('Article Comments');

                if (C('Articles.Profile.ShowCounts', true))
                    $ArticleCommentsLabel .= '<span class="Aside">' . CountString(GetValueR('User.CountArticleComments',
                            $Sender, null), "/profile/count/articlecomments?userid=$UserID") . '</span>';

                $Sender->AddProfileTab(T('Article Comments'),
                    'profile/articlecomments/' . $Sender->User->UserID . '/' . rawurlencode($Sender->User->Name),
                    'ArticleComments', $ArticleCommentsLabel);
            }

            // Add the article tab's CSS and Javascript.
            $Sender->AddJsFile('jquery.gardenmorepager.js');
            $Sender->AddJsFile('articles.js');
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
            $User = Gdn::UserModel()->GetID($UserReference);
        else if (is_string($UserReference))
            $User = Gdn::UserModel()->GetByUsername($UserReference);

        $UserCanAddArticle = Gdn::UserModel()->CheckPermission($User, 'Articles.Articles.Add');
        if ($User && (!$UserCanAddArticle || (!$UserCanAddArticle && ($User->CountArticles == 0))))
            Redirect(UserUrl($User));

        $Sender->EditMode(false);

        // Tell the ProfileController what tab to load
        $Sender->GetUserInfo($UserReference, $Username, $UserID);
        $Sender->_SetBreadcrumbs(T('Articles'), '/profile/articles');
        $Sender->SetTabView('Articles', 'Articles', 'Profile', 'Articles');
        $Sender->CountArticleCommentsPerPage = C('Articles.Articles.PerPage', 12);

        list($Offset, $Limit) = OffsetLimit($Page, Gdn::Config('Articles.Articles.PerPage', 12));

        $ArticleModel = new ArticleModel();
        $Articles = $ArticleModel->GetByUser($Sender->User->UserID, $Offset, $Limit,
            array('Status' => ArticleModel::STATUS_PUBLISHED))->Result();
        $CountArticles = $Offset + $ArticleModel->LastArticleCount + 1;
        $Sender->SetData('Articles', $Articles);

        $Sender->ArticleCategoryModel = new ArticleCategoryModel();
        $Sender->ArticleMediaModel = new ArticleMediaModel();

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $Sender->Pager = $PagerFactory->GetPager('MorePager', $Sender);
        $Sender->Pager->MoreCode = 'More Articles';
        $Sender->Pager->LessCode = 'Newer Articles';
        $Sender->Pager->ClientID = 'Pager';
        $Sender->Pager->Configure(
            $Offset,
            $Limit,
            $CountArticles,
            UserUrl($Sender->User, '', 'articles') . '/{Page}'
        );

        // Deliver JSON data if necessary
        if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL && $Offset > 0) {
            $Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
            $Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
            $Sender->View = 'articles';
        }

        // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
        $Sender->HandlerType = HANDLER_TYPE_NORMAL;

        // Do not show article options
        $Sender->ShowOptions = false;

        if ($Sender->Head) {
            // These pages offer only duplicate content to search engines and are a bit slow.
            $Sender->Head->AddTag('meta', array('name' => 'robots', 'content' => 'noindex,noarchive'));
        }

        // Render the ProfileController
        $Sender->AddCssFile('articles.css', 'articles');
        $Sender->Render();
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
            $User = Gdn::UserModel()->GetID($UserReference);
        else if (is_string($UserReference))
            $User = Gdn::UserModel()->GetByUsername($UserReference);

        $UserCanAddComment = Gdn::UserModel()->CheckPermission($User, 'Articles.Comments.Add');
        if ($User && (!$UserCanAddComment || (!$UserCanAddComment && ($User->CountArticleComments == 0))))
            Redirect(UserUrl($User));

        $Sender->EditMode(false);

        // Tell the ProfileController what tab to load
        $Sender->GetUserInfo($UserReference, $Username, $UserID);
        $Sender->_SetBreadcrumbs(T('Article Comments'), '/profile/articlecomments');
        $Sender->SetTabView('Article Comments', 'Comments', 'Profile', 'Articles');
        $Sender->CountArticleCommentsPerPage = C('Articles.Comments.PerPage', 30);

        list($Offset, $Limit) = OffsetLimit($Page, Gdn::Config('Articles.Comments.PerPage', 30));

        $ArticleCommentModel = new ArticleCommentModel();
        $Comments = $ArticleCommentModel->GetByUser($Sender->User->UserID, $Offset, $Limit)->Result();
        $CountArticleComments = $Offset + $ArticleCommentModel->LastCommentCount + 1;
        $Sender->SetData('Comments', $Comments);

        $Sender->ArticleModel = new ArticleModel();

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $Sender->Pager = $PagerFactory->GetPager('MorePager', $Sender);
        $Sender->Pager->MoreCode = 'More Article Comments';
        $Sender->Pager->LessCode = 'Newer Article Comments';
        $Sender->Pager->ClientID = 'Pager';
        $Sender->Pager->Configure(
            $Offset,
            $Limit,
            $CountArticleComments,
            UserUrl($Sender->User, '', 'articlecomments') . '/{Page}'
        );

        // Deliver JSON data if necessary
        if ($Sender->DeliveryType() != DELIVERY_TYPE_ALL && $Offset > 0) {
            $Sender->SetJson('LessRow', $Sender->Pager->ToString('less'));
            $Sender->SetJson('MoreRow', $Sender->Pager->ToString('more'));
            $Sender->View = 'comments';
        }

        // Set the HandlerType back to normal on the profilecontroller so that it fetches it's own views
        $Sender->HandlerType = HANDLER_TYPE_NORMAL;

        // Do not show article options
        $Sender->ShowOptions = false;

        if ($Sender->Head) {
            // These pages offer only duplicate content to search engines and are a bit slow.
            $Sender->Head->AddTag('meta', array('name' => 'robots', 'content' => 'noindex,noarchive'));
        }

        // Render the ProfileController
        $Sender->Render();
    }

    /**
     * Load author meta into the form when editing.
     *
     * @param ProfileController $Sender ProfileController
     */
    public function ProfileController_BeforeEdit_Handler($Sender) {
        $UserMeta = Gdn::UserModel()->GetMeta($Sender->User->UserID, 'Articles.%', 'Articles.');
        if (!is_array($UserMeta))
            return;

        if (isset($UserMeta['AuthorDisplayName']))
            $Sender->Form->SetValue('Articles.AuthorDisplayName', $UserMeta['AuthorDisplayName']);
        
        if (isset($UserMeta['AuthorBio']))
            $Sender->Form->SetValue('Articles.AuthorBio', $UserMeta['AuthorBio']);
    }

    /**
     * Display author meta inputs when editing.
     *
     * @param ProfileController $Sender ProfileController
     */
    public function ProfileController_EditMyAccountAfter_Handler($Sender) {
        if (Gdn::Session()->CheckPermission(array('Garden.Users.Edit', 'Articles.Articles.Add'), false)) {
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
        $UserMeta = Gdn::UserModel()->GetMeta($Sender->User->UserID, 'Articles.%', 'Articles.');
        if (!is_array($UserMeta))
            return;

        // Display author display name.
        if (isset($UserMeta['AuthorDisplayName']) && ($UserMeta['AuthorDisplayName'] != '')
                && ($Sender->User->Name != $UserMeta['AuthorDisplayName'])) {
            echo ' <dt class="Articles Profile AuthorDisplayName">' . T('Author Display Name') . '</dt> ';
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
        $UserMeta = Gdn::UserModel()->GetMeta($Sender->User->UserID, 'Articles.%', 'Articles.');
        if (!is_array($UserMeta))
            return;

        // Display author display name.
        if (isset($UserMeta['AuthorBio']) && ($UserMeta['AuthorBio'] != '')) {
            echo '<dl id="BoxProfileAuthorBio" class="About">';
            echo ' <dt class="Articles Profile AuthorBio">' . T('Author Bio') . '</dt> ';
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
            $SQL->Update('ArticleCategory c')
                ->Join('Article a', 'a.ArticleID = c.LastArticleID')
                ->Where('a.InsertUserID', $UserID)
                ->Set('c.LastArticleID', null)
                ->Set('c.LastArticleCommentID', null)
                ->Put();

            $SQL->Update('ArticleCategory c')
                ->Join('ArticleComment ac', 'ac.ArticleCommentID = c.LastArticleCommentID')
                ->Where('ac.InsertUserID', $UserID)
                ->Set('c.LastArticleID', null)
                ->Set('c.LastArticleCommentID', null)
                ->Put();

            // Grab all of the articles that the user has engaged in.
            $ArticleIDs = $SQL
                ->Select('ArticleID')
                ->From('ArticleComment')
                ->Where('InsertUserID', $UserID)
                ->GroupBy('ArticleID')
                ->Get()->ResultArray();
            $ArticleIDs = ConsolidateArrayValuesByKey($ArticleIDs, 'ArticleID');

            Gdn::UserModel()->GetDelete('ArticleComment', array('InsertUserID' => $UserID), $Data);

            // Update the comment counts.
            $CommentCounts = $SQL
                ->Select('ArticleID')
                ->Select('ArticleCommentID', 'count', 'CountArticleComments')
                ->Select('ArticleCommentID', 'max', 'LastArticleCommentID')
                ->WhereIn('ArticleID', $ArticleIDs)
                ->GroupBy('ArticleID')
                ->Get('ArticleComment')->ResultArray();

            foreach ($CommentCounts as $Row) {
                $SQL->Put('Article',
                    array('CountArticleComments' => $Row['CountArticleComments'] + 1,
                        'LastArticleCommentID' => $Row['LastArticleCommentID']),
                    array('ArticleID' => $Row['ArticleID']));
            }

            // Update the last user IDs.
            $SQL->Update('Article a')
                ->Join('ArticleComment ac', 'a.LastArticleCommentID = ac.ArticleCommentID', 'left')
                ->Set('a.LastArticleCommentUserID', 'ac.InsertUserID', false, false)
                ->Set('a.DateLastArticleComment', 'ac.DateInserted', false, false)
                ->WhereIn('a.ArticleID', $ArticleIDs)
                ->Put();

            // Update the last posts.
            $Articles = $SQL
                ->WhereIn('ArticleID', $ArticleIDs)
                ->Where('LastArticleCommentUserID', $UserID)
                ->Get('Article');

            // Delete the user's articles
            Gdn::UserModel()->GetDelete('Article', array('AttributionUserID' => $UserID), $Data);

            // Update the appropriate recent posts in the categories.
            $ArticleCategoryModel = new ArticleCategoryModel();
            $Categories = $ArticleCategoryModel->GetWhere(array('LastArticleID' => null))->ResultArray();
            foreach ($Categories as $Category) {
                $ArticleCategoryModel->SetRecentPost($Category['ArticleCategoryID']);
            }
        } else if ($DeleteMethod == 'wipe') {
            // Erase the user's articles
            $SQL->Update('Article')
                ->Set('Status', 'Trash')
                ->Where('AuthorUserID', $UserID)
                ->Put();

            $SQL->Update('ArticleComment')
                ->Set('Body', T('The user and all related content has been deleted.'))
                ->Set('Format', 'Deleted')
                ->Where('InsertUserID', $UserID)
                ->Put();
        }

        // Remove the user's profile information related to this application
        $SQL->Update('User')
            ->Set(array(
                'CountArticles' => 0,
                'CountArticleComments' => 0))
            ->Where('UserID', $UserID)
            ->Put();
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
    //            $Comment = $ArticleCommentModel->GetByID($Row->PrimaryID);
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
        $Session = Gdn::Session();

        $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        if ($Session->CheckPermission($PermissionsAllowed, false)) {
            echo Wrap(Anchor(Sprite('SpMyDiscussions').' '.T('Articles Dashboard'), '/compose'), 'li');
        }
    }

    public function DiscussionsController_Render_Before($Sender) {
        $Sender->AddModule('ArticlesModule');
    }

    public function CategoriesController_Render_Before($Sender) {
        $Sender->AddModule('ArticlesModule');
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
