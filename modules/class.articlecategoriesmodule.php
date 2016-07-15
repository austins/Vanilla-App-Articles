<?php
/**
 * ArticleCategories module
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Renders the categories menu for the Articles controller.
 */
class ArticleCategoriesModule extends Gdn_Module {
    public function __construct($sender = '') {
        // Load categories.
        $articleCategoryModel = new ArticleCategoryModel();

        $categoriesWheres = array('ac.CountArticles >' => '0'); // Category must have at least one article.
        $categories = $articleCategoryModel->get($categoriesWheres);

        $this->Data = $categories;

        parent::__construct($sender);
    }

    /**
     * Returns the asset name that the panel will be displayed to.
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Returns the module as a string.
     *
     * @return string
     */
    public function toString() {
        $controller = Gdn::controller();
        $session = Gdn::session();

        $controller->EventArguments['ArticleCategoriesModule'] = &$this;
        $controller->fireEvent('BeforeArticleCategoriesModule');

        if (!$session->checkPermission('Articles.Articles.View', true, 'ArticleCategory', 'any')) {
            return '';
        }

        return parent::toString();
    }
}
