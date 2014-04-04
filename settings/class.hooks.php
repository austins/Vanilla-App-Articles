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
    * The Categories method of the Articles setting page.
    */
   public function Controller_AddCategory($Sender) {
      // Set required permission.
      $Sender->Permission('Garden.Settings.Manage');

      // Add asset.
      $Sender->AddJsFile('articles.js', 'articles');

      // Set up the article category model.
      $Sender->Form = new Gdn_Form();
      $ArticleCategoryModel = new ArticleCategoryModel();
      $Sender->Form->SetModel($ArticleCategoryModel);

      // If editing a category, then set the data in the form.
      $Category = FALSE;
      if($Sender->RequestArgs[0] === 'editcategory') {
         $CategoryID = (int)$Sender->RequestArgs[1];

         if(is_numeric($CategoryID)) {
            $Category = $ArticleCategoryModel->GetByID($CategoryID);

            if($Category)
               $Sender->Form->SetData($Category);
            else
               throw NotFoundException(T('Article category'));
         } else {
            throw NotFoundException(T('Article category'));
         }
      }

      // Set the title of the page.
      if(!$Category)
         $Sender->Title(T('Add Article Category'));
      else
         $Sender->Title(T('Edit Article Category'));

      // Handle the form.
      if(($Sender->Form->AuthenticatedPostBack() == FALSE)) {
         if(!$Category)
            $Sender->Form->AddHidden('CodeIsDefined', '0');
      } else { // The form was saved.
         // Define some validation rules for the fields being saved.
         $Sender->Form->ValidateRule('Name', 'function:ValidateRequired');
         $Sender->Form->ValidateRule('UrlCode', 'function:ValidateRequired', T('URL code is required.'));

         // Manually validate certain fields.
         $FormValues = $Sender->Form->FormValues();

         if($Category)
            $FormValues['CategoryID'] = $CategoryID;

         // If there are no errors, then save the category.
         if($Sender->Form->ErrorCount() == 0) {
            if($ArticleCategoryModel->Save($FormValues))
               $Sender->InformMessage(T('The article category has been saved successfully.'));
         }
      }

      $Sender->AddSideMenu('/settings/articles/addcategory/');
      $Sender->View = $Sender->FetchViewLocation('addcategory', 'settings', 'articles');
      $Sender->Render();
   }

   public function Controller_EditCategory($Sender) {
      $this->Controller_AddCategory($Sender);
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
