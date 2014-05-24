<?php if (!defined('APPLICATION'))
    exit();

/**
 * The controller for an article.
 */
class ArticleController extends Gdn_Controller {
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
    public function Index($ArticleYear, $ArticleUrlCode) {
        // Add module.
        $this->AddModule('ArticlesDashboardModule');

        // Get the article.
        $Article = $this->ArticleModel->GetByUrlCode($ArticleUrlCode);
        $this->SetData('Article', $Article);

        // Set required permission.
        $UserModel = new UserModel();
        if ($Article->Status != ArticleModel::STATUS_PUBLISHED)
            if (($Article->AuthorUserID == Gdn::Session()->UserID)
                && !$UserModel->CheckPermission($Article->AuthorUserID, 'Articles.Articles.Edit')
            )
                $this->Permission('Articles.Articles.View');
            else
                $this->Permission('Articles.Articles.Edit');
        else
            $this->Permission('Articles.Articles.View');

        // Get the category.
        $Category = $this->ArticleCategoryModel->GetByID($Article->CategoryID);
        $this->SetData('Category', $Category);

        $DateInsertedYear = Gdn_Format::Date($Article->DateInserted, '%Y');
        if ((count($this->RequestArgs) < 2) || !is_numeric($ArticleYear)
            || ($ArticleUrlCode == '') || !$Article
            || ($ArticleYear != $DateInsertedYear)
        )
            throw NotFoundException('Article');

        // Set the title.
        $this->Title($Article->Name);

        $this->View = 'index';
        $this->Render();
    }
}
