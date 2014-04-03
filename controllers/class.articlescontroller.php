<?php if(!defined('APPLICATION')) exit();

/**
 * Master application controller for Articles.
 */
class ArticlesController extends Gdn_Controller {
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

    /**
     * Handles the dashboard method of this controller.
     * Only visible to users that have permission.
     */
    public function Dashboard() {
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

        $this->View = 'dashboard';
        $this->Render();
    }

    /**
     * Handles request methods for the method that calls it.
     * Based on the Dispatch method from the Gdn_Plugin class of 2.1b2.
     */
    private function Dispatch($ControllerName = 'Controller') {
        $this->Form = new Gdn_Form();

        $ControllerMethod = $ControllerName . '_Index';

        if(!empty($this->RequestArgs)) {
            list($MethodName) = $this->RequestArgs;

            // Account for suffix.
            $MethodName = array_shift($Trash = explode('.', $MethodName));

            $TestControllerMethod = $ControllerName . '_' . $MethodName;
            if(method_exists($this, $TestControllerMethod))
                $ControllerMethod = $TestControllerMethod;
        }

        if(method_exists($this, $ControllerMethod)) {
            return call_user_func(array($this, $ControllerMethod), $this);
        } else {
            $ClassName = get_class($this);

            throw NotFoundException("@{$ClassName}->{$ControllerMethod}()");
        }
    }

    public function Post() {
        $this->Dispatch('DispatchPost');
    }

    private function DispatchPost_Index() {
        $this->DispatchPost_Article();
    }

    private function DispatchPost_Article() {
        $this->Title(T('Post Article'));

        // Set allowed permissions.
        // The user only needs one of the specified permissions.
        $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        $this->Permission($PermissionsAllowed, FALSE);

        // Set the model on the form.
        $this->Form->SetModel($this->ArticleModel);

        $this->View = '/post/index';
        $this->Render();
    }

    private function DispatchPost_Comment() {
        $this->Title(T('Post Article Comment'));

        // Set required permission.
        $this->Permission('Articles.Comments.Add');

        // Set the model on the form.
        $this->Form->SetModel($this->ArticleModel);

        $this->View = '/post/comment';
        $this->Render();
    }
}
