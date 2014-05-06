<?php if(!defined('APPLICATION')) exit();

/**
 * The controller for the composing of articles.
 */
class ComposeController extends Gdn_Controller {
    /**
     * Models to include.
     */
    public $Uses = array('ArticleModel', 'ArticleCategoryModel', 'Form');

    protected $Article = false;
    protected $Category = false;

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
        $this->AddJsFile('jquery.autogrow.js');
        $this->AddJsFile('jquery.autocomplete.js');
        $this->AddJsFile('global.js');
        $this->AddJsFile('articles.js');

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
        $this->Permission($PermissionsAllowed, false);

        // Get recently published articles.
        $RecentlyPublishedOffset = 0;
        $RecentlyPublishedLimit = 5;
        $RecentlyPublished = $this->ArticleModel->Get($RecentlyPublishedOffset, $RecentlyPublishedLimit);
        $this->SetData('RecentlyPublished', $RecentlyPublished);

        $this->View = 'index';
        $this->Render();
    }

    public function Article() {
        // If not editing...
        if(!$this->Article) {
            $this->Title(T('Add Article'));

            // Set allowed permission.
            $this->Permission('Articles.Articles.Add');
        }

        // Set the model on the form.
        $this->Form->SetModel($this->ArticleModel);

        // Get categories.
        $Categories = $this->ArticleCategoryModel->Get();
        $this->SetData('Categories', $Categories, true);

        $UserModel = new UserModel();

        // The form has not been submitted yet.
        if(!$this->Form->AuthenticatedPostBack()) {
            // If editing...
            if($this->Article) {
                $this->Form->SetData($this->Article);

                // Set author field.
                $Author = $UserModel->GetID($this->Article->AuthorUserID);

                // If the user with AuthorUserID doesn't exist.
                if(!$Author)
                    $Author = $UserModel->GetID($this->Article->InsertUserID);
            }

            // If the user with InsertUserID doesn't exist.
            if(!$Author)
                $Author = Gdn::Session()->User;

            $this->Form->SetValue('AuthorUserName', $Author->Name);
        } else { // The form has been submitted.
            // Manually validate certain fields.
            $FormValues = $this->Form->FormValues();

            // If editing, make sure the ArticleID is passed to the form save method.
            if($this->Article) {
                $this->Form->SetFormValue('ArticleID', (int)$this->Article->ArticleID);
            }

            // Retrieve author user ID.
            $Author = $UserModel->GetByUsername($FormValues['AuthorUserName']);

            // If the inputted author doesn't exist.
            if(!$Author)
                $this->Form->AddError('The user for the author field does not exist.', 'AuthorUserName');

            $this->Form->SetFormValue('AuthorUserID', (int)$Author->UserID);

            if($this->Form->ErrorCount() == 0)
                $this->Form->Save($FormValues);

            // TODO add/edit article validation and more fields.
        }

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

    public function EditArticle($ArticleID = FALSE) {
        $this->Title(T('Edit Article'));

        // Set allowed permission.
        $this->Permission('Articles.Articles.Edit');

        // Get article.
        if(is_numeric($ArticleID))
            $this->Article = $this->ArticleModel->GetByID($ArticleID);

        // If the article doesn't exist, then throw an exception.
        if(!$this->Article)
            throw NotFoundException('Article');

        // Get category.
        $this->Category = $this->ArticleCategoryModel->GetByID($this->Article->CategoryID);

        $this->View = 'article';
        $this->Article();
    }

    public function CloseArticle() {
        // TODO CloseArticle()
    }

    public function DeleteArticle() {
        // TODO DeleteArticle()
    }
}
