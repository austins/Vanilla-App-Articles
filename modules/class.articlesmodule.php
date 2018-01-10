<?php
/**
 * ArticlesModule module
 *
 * @copyright 2015-2018 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Renders recently published articles
 */
class ArticlesModule extends Gdn_Module {
    public function __construct($sender = '') {
        // Load articles.
        $articleModel = new ArticleModel();

        $limit = 5;
        $articleWheres = array('a.Status' => ArticleModel::STATUS_PUBLISHED); // Category must have at least one article.
        $articles = $articleModel->get(0, $limit, $articleWheres);

        $this->Data = $articles;

        parent::__construct($sender);

        $this->_ApplicationFolder = 'articles';
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

        $controller->EventArguments['ArticlesModule'] = &$this;
        $controller->fireEvent('BeforeArticlesModule');

        if (!$session->checkPermission('Articles.Articles.View', true, 'ArticleCategory', 'any'))
            return '';

        return parent::toString();
    }
}
