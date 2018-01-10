<?php
/**
 * ArticleCategories module
 *
 * @copyright 2015-2018 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Renders the categories menu for the Articles controller.
 */
class ArticleCategoriesModule extends Gdn_Module {
    public function __construct($sender = '') {
        // Load categories.
        $categories = ArticleCategoryModel::categories();

        // Filter out the categories the current user doesn't have permission to view
        // along with those with zero article count.
        foreach ($categories as $i => $category) {
            if (!$category['PermsArticlesView'] || $category['CountArticles'] === 0) {
                unset($categories[$i]);
            }
        }

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
