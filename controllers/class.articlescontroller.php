<?php if (!defined('APPLICATION'))
    exit();

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
    public function Index($Page = false) {
        if (Gdn::Router()->GetDestination('DefaultController') !== 'articles')
            $this->Title(T('Articles'));

        // TODO: Set title appropriately if not first page of index.

        // Set required permission.
        $this->Permission('Articles.Articles.View');

        // Add module.
        $this->AddModule('ArticlesDashboardModule');

        // Get total article count.
        $CountArticles = $this->ArticleModel->GetCount();
        $this->SetData('CountArticles', $CountArticles);

        // Determine offset from $Page.
        list($Offset, $Limit) = OffsetLimit($Page, C('Articles.Articles.PerPage', 12));
        $Page = PageNumber($Offset, $Limit);
        $this->CanonicalUrl(Url(ConcatSep('/', 'articles', PageNumber($Offset, $Limit, true, false)), true));

        // Have a way to limit the number of pages on large databases
        // because requesting a super-high page can kill the db.
        $MaxPages = C('Articles.Articles.MaxPages', false);
        if ($MaxPages && $Page > $MaxPages) {
            throw NotFoundException();
        }

        // Build a pager
        $PagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->FireEvent('BeforeBuildPager');
        $this->Pager = $PagerFactory->GetPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->Configure($Offset, $Limit, $CountArticles, 'articles/%1$s');
        if (!$this->Data('_PagerUrl'))
            $this->SetData('_PagerUrl', 'articles/{Page}');
        $this->SetData('_Page', $Page);
        $this->SetData('_Limit', $Limit);
        $this->FireEvent('AfterBuildPager');

        // Get published articles.
        $Wheres = array('a.Status' => ArticleModel::STATUS_PUBLISHED);
        $Articles = $this->ArticleModel->Get($Offset, $Limit, $Wheres);

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
        $Category = null;

        if ($UrlCode != '')
            $Category = $this->ArticleCategoryModel->GetByUrlCode($UrlCode);

        if (!$Category)
            throw NotFoundException('Article category');

        $this->SetData('Category', $Category);

        // Set the title.
        $this->Title($Category->Name);

        // Get published articles.
        $Offset = 0;
        $Limit = false;
        $Wheres = array(
            'a.Status' => ArticleModel::STATUS_PUBLISHED,
            'a.CategoryID' => $Category->CategoryID
        );
        $Articles = $this->ArticleModel->Get($Offset, $Limit, $Wheres)->Result();
        $this->SetData('Articles', $Articles);

        $this->View = 'index';
        $this->Render();
    }
}
