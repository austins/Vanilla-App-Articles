<?php if(!defined('APPLICATION')) exit();

/**
 * The controller for the composing of articles.
 */
class ComposeController extends Gdn_Controller {
   /**
    * Models to include.
    */
   public $Uses = array('ArticleModel', 'ArticleCategoryModel', 'Form');

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
    * This handles the articles dashboard.
    * Only visible to users that have permission.
    */
   public function Index() {
      $this->Title(T('Articles Dashboard'));

      // Set allowed permissions.
      // The user only needs one of the specified permissions.
      $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
      $this->Permission($PermissionsAllowed, FALSE);

      // Get recently published articles.
      $RecentlyPublishedOffset = 0;
      $RecentlyPublishedLimit = 5;
      $RecentlyPublished = $this->ArticleModel->Get($RecentlyPublishedOffset, $RecentlyPublishedLimit)->Result();
      $this->SetData('RecentlyPublished', $RecentlyPublished);

      $this->View = 'index';
      $this->Render();
   }

   public function Article() {
      $this->Title(T('Post Article'));

      // Set allowed permissions.
      // The user only needs one of the specified permissions.
      $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
      $this->Permission($PermissionsAllowed, FALSE);

      // Set the model on the form.
      $this->Form->SetModel($this->ArticleModel);

      $this->View = 'article';
      $this->Render();
   }

   public function Comment() {
      $this->Title(T('Post Article Comment'));

      // Set required permission.
      $this->Permission('Articles.Comments.Add');

      // Set the model on the form.
      $this->Form->SetModel($this->ArticleModel);

      $this->View = 'comment';
      $this->Render();
   }
}
