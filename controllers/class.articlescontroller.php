<?php
/**
 * Articles controller
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Handles displaying an article in most contexts via /articles endpoint.
 */
class ArticlesController extends Gdn_Controller {
    /**
     * Models to include.
     */
    public $Uses = array('ArticleModel', 'ArticleCategoryModel', 'ArticleMediaModel');

    /** @var ArticleModel */
    public $ArticleModel;

    /** @var ArticleCategoryModel */
    public $ArticleCategoryModel;

    protected $ArticleCategory = false;

    /**
     * Include JS, CSS, and modules used by all methods.
     * Extended by all other controllers in this application.
     * Always called by dispatcher before controller's requested method.
     */
    public function initialize() {
        // Set up head.
        $this->Head = new HeadModule($this);

        // Add JS files.
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery-ui-1.8.17.custom.min.js');
        $this->addJsFile('jquery.livequery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('global.js');
        $this->addJsFile('articles.js');

        // Add CSS files.
        $this->addCssFile('style.css');
        $this->addCssFile('articles.css');

        // Add modules.
        $this->addModule('GuestModule');
        $this->addModule('SignedInModule');
        $this->addModule('ArticlesDashboardModule');
        $this->addModule('ArticleCategoriesModule');
        $this->addModule('DiscussionsModule');
        $this->addModule('RecentActivityModule');

        parent::initialize();
    }

    /**
     * Main listing of articles.
     *
     * @param bool|object $page entity
     * @throws NotFoundException if article not found
     */
    public function index($page = false) {
        if (Gdn::router()->getDestination('DefaultController') !== 'articles') {
            $this->title(t('Articles'));
        }

        if ($this->Head) {
            $this->Head->addRss(url('/articles/feed.rss', true), $this->Head->title());
        }

        // TODO: Set title appropriately if not first page of index.

        // Set required permission.
        $this->permission('Articles.Articles.View', true, 'ArticleCategory', 'any');

        // Get total article count.
        $countArticles = $this->ArticleModel->getCount();
        $this->setData('CountArticles', $countArticles);

        // Determine offset from $Page.
        list($offset, $limit) = offsetLimit($page, c('Articles.Articles.PerPage', 12));
        $page = pageNumber($offset, $limit);
        $this->canonicalUrl(url(concatSep('/', 'articles', pageNumber($offset, $limit, true, false)), true));

        // Have a way to limit the number of pages on large databases
        // because requesting a super-high page can kill the db.
        $maxPages = c('Articles.Articles.MaxPages', false);
        if ($maxPages && $page > $maxPages) {
            throw notFoundException();
        }

        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        $this->Pager = $pagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure($offset, $limit, $countArticles, 'articles/%1$s');
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'articles/{Page}');
        }
        $this->setData('_Page', $page);
        $this->setData('_Limit', $limit);
        $this->fireEvent('AfterBuildPager');

        // Get published articles.
        $wheres = array('a.Status' => ArticleModel::STATUS_PUBLISHED);
        $this->setData('Articles', $this->ArticleModel->get($offset, $limit, $wheres)->result());

        Gdn_Theme::section('ArticleList');
        $this->View = 'index';
        $this->render();
    }

    /**
     * Category filtered view of index.
     *
     * @param string $urlCode
     * @throws NotFoundException if article category not found
     */
    public function category($urlCode = '', $page = false) {
        // Set required permission.
        $this->permission('Articles.Articles.View', true, 'ArticleCategory', 'any');

        list($offset, $limit) = offsetLimit($page, c('Articles.Articles.PerPage', 12));
        $page = pageNumber($offset, $limit);

        // Get the category.
        if ($urlCode != '') {
            $this->ArticleCategory = $this->ArticleCategoryModel->getByUrlCode($urlCode);
        }

        if (!$this->ArticleCategory) {
            throw notFoundException('Article category');
        }

        $this->setData('ArticleCategory', $this->ArticleCategory);

        // Set the title.
        $this->title($this->ArticleCategory->Name);

        if ($this->Head) {
            $this->Head->addRss(url('/articles/category/' . $urlCode . '/feed.rss', true), $this->Head->title());
        }

        // Get published articles.
        $wheres = array(
            'Status' => ArticleModel::STATUS_PUBLISHED,
            'ArticleCategoryID' => $this->ArticleCategory->ArticleCategoryID
        );
        $this->setData('Articles', $this->ArticleModel->get($offset, $limit, $wheres)->result());
        // Get total article count.
        $countArticles = $this->ArticleModel->getCount($wheres);
        $this->setData('CountArticles', $countArticles);
        // Build a pager
        $pagerFactory = new Gdn_PagerFactory();
        $this->EventArguments['PagerType'] = 'Pager';
        $this->fireEvent('BeforeBuildPager');
        $this->Pager = $pagerFactory->getPager($this->EventArguments['PagerType'], $this);
        $this->Pager->ClientID = 'Pager';
        $this->Pager->configure($offset, $limit, $countArticles, 'articles/category/' . $urlCode . '/%1$s');
        if (!$this->data('_PagerUrl')) {
            $this->setData('_PagerUrl', 'articles/category/' . $urlCode . '/{Page}');
        }
        $this->setData('_Page', $page);
        $this->setData('_Limit', $limit);
        $this->fireEvent('AfterBuildPager');

        Gdn_Theme::section('CategoryArticleList');
        $this->View = 'index';
        $this->render();
    }

    public function categories() {
        $this->permission('Articles.Articles.View', true, 'ArticleCategory', 'any'); // Set required permission.

        $this->title(t('Article Categories'));

        // Load categories.
        $categories = ArticleCategoryModel::categories();

        // Filter out the categories the current user doesn't have permission to view
        // along with those with zero article count.
        foreach ($categories as $i => $category) {
            if (!$category['PermsArticlesView'] || $category['CountArticles'] === 0) {
                unset($categories[$i]);
            }
        }

        $this->setData('ArticleCategories', $categories, true);

        $this->render();
    }
}
