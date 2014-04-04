<?php if(!defined('APPLICATION')) exit();

/**
 * Master application controller for Articles.
 */
class ArticlesController extends Gdn_Controller {
   /**
    * Models to include.
    */
   public $Uses = array('ArticleModel', 'ArticleCategoryModel');

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
   public function Index() {
      $this->Title(T('Articles'));

      // Set required permission.
      $this->Permission('Articles.Articles.View');

      // Add module.
      $this->AddModule('ArticlesDashboardModule');

      // Get published articles.
      $Offset = 0;
      $Limit = FALSE;
      $Wheres = array('a.Status' => 'published');
      $Articles = $this->ArticleModel->Get($Offset, $Limit, $Wheres)->Result();
      $this->SetData('Articles', $Articles);

      $this->View = 'index';
      $this->Render();
   }

   /**
    * The category method of this controller.
    */
   public function Category($UrlCode = '') {
      // Set required permission.
      $this->Permission('Articles.Articles.View');

      // Add module.
      $this->AddModule('ArticlesDashboardModule');

      // Get the category.
      $Category = NULL;

      if($UrlCode != '')
         $Category = $this->ArticleCategoryModel->GetByUrlCode($UrlCode);

      if(!$Category)
         throw NotFoundException('Article category');

      $this->SetData('Category', $Category);

      // Get published articles.
      $Offset = 0;
      $Limit = FALSE;
      $Wheres = array(
         'a.Status' => 'published',
         'a.CategoryID' => $Category->CategoryID
      );
      $Articles = $this->ArticleModel->Get($Offset, $Limit, $Wheres)->Result();
      $this->SetData('Articles', $Articles);

      $this->View = 'index';
      $this->Render();
   }
}
