<?php if(!defined('APPLICATION')) exit();

/**
 * The class.hooks.php file is essentially a giant plugin container for an app
 * that is automatically enabled when this app is.
 */
class ArticlesHooks extends Gdn_Plugin {
   /**
    * Add link to the articles controller in the main menu.
    *
    * @param $Sender
    */
   public function Base_Render_Before($Sender) {
      if($Sender->Menu)
         $Sender->Menu->AddLink('Articles', T('Articles'), '/articles/');
   }

   /**
    * Automatically executed when this application is enabled.
    */
   public function Setup() {
      // Initialize variables that are used for the structuring and stub inserts.
      $Database = Gdn::Database();
      $SQL = $Database->SQL();
      $Drop = Gdn::Config('Articles.Version') === FALSE ? TRUE : FALSE;
      $Explicit = TRUE;

      // Call structure.php to update database.
      include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'structure.php');
      include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'stub.php');

      // Save version number to config.
      $ApplicationInfo = array();
      include(PATH_APPLICATIONS . DS . 'articles' . DS . 'settings' . DS . 'about.php');
      $Version = ArrayValue('Version', $ApplicationInfo['Articles'], FALSE);
      if($Version) {
         $Save = array('Articles.Version' => $Version);
         SaveToConfig($Save);
      }
   }

   /**
    * Create the Articles settings page.
    * Runs the Dispatch method which handles methods for the page.
    */
   public function SettingsController_Articles_Create($Sender) {
      $this->Dispatch($Sender, $Sender->RequestArgs);
   }

   /**
    * The Index method of the Articles setting page.
    */
   public function Controller_Index($Sender) {
      $Sender->Title('Articles Settings');

      // Set required permission.
      $Sender->Permission('Garden.Settings.Manage');

      // Set up the configuration module.
      $ConfigModule = new ConfigurationModule($Sender);

      $ConfigModule->Initialize(array(
                //'Example.Example.Enabled' => array(
                //   'LabelCode' => 'Use Example',
                //   'Control'   => 'Checkbox'
                //)
            )
         );

      $Sender->ConfigurationModule = $ConfigModule;

      $Sender->AddSideMenu('/settings/articles/');
      $ConfigModule->RenderAll();
   }

   /**
    * The Categories method of the Articles setting page.
    */
   public function Controller_Categories($Sender) {
      $Sender->Title('Article Categories');

      // Set required permission.
      $Sender->Permission('Garden.Settings.Manage');

      // Add assets.
      $Sender->AddJsFile('js/library/nestedSortable.1.3.4/jquery-ui-1.8.11.custom.min.js');
      $Sender->AddJsFile('js/library/nestedSortable.1.3.4/jquery.ui.nestedSortable.js');

      // Set up the article category model.
      $ArticleCategoryModel = new ArticleCategoryModel();

      $Categories = $ArticleCategoryModel->Get();
      $Sender->SetData('Categories', $Categories, TRUE);

      $Sender->AddSideMenu('/settings/articles/categories/');
      $Sender->View = $Sender->FetchViewLocation('categories', 'settings', 'articles');
      $Sender->Render();
   }

   /**
    * Add links for the setting pages to the dashboard sidebar.
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $GroupName = 'Articles';
      $Menu = &$Sender->EventArguments['SideMenu'];

      $Menu->AddItem($GroupName, $GroupName, FALSE, array('class' => $GroupName));
      $Menu->AddLink($GroupName, T('Settings'), '/settings/articles/', 'Garden.Settings.Manage');
      $Menu->AddLink($GroupName, T('Categories'), '/settings/articles/categories/', 'Garden.Settings.Manage');
   }
}
