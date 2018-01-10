<?php
/**
 * ArticleSearch model
 *
 * @copyright 2015-2018 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Manages searches for Articles and associated comments.
 */
class ArticleSearchModel extends Gdn_Model {
    /**
     * @var object ArticleModel
     */
    protected $_ArticleModel = false;

    /**
     * Makes an article model available.
     *
     * @param object $value ArticleModel.
     * @return object ArticleModel.
     */
    public function articleModel($value = false) {
        if ($value !== false) {
            $this->_ArticleModel = $value;
        }
        if ($this->_ArticleModel === false) {
            require_once(dirname(__FILE__) . DS . 'class.articlemodel.php');
            $this->_ArticleModel = new ArticleModel();
        }

        return $this->_ArticleModel;
    }

    /**
     * Execute Article search query
     *
     * @param object $searchModel SearchModel (Dashboard)
     * @return object SQL result.
     */
    public function articleSql($searchModel) {
        // Build search part of query
        $searchModel->addMatchSql($this->SQL, 'a.Name, a.Body', 'a.DateInserted');

        // Build base query
        $this->SQL
            ->select('a.ArticleID as PrimaryID, a.Name as Title, a.Excerpt as Summary, a.Format, '
                . 'a.ArticleCategoryID, a.Closed')
            ->select('a.UrlCode', "concat('/article/', year(a.DateInserted), '/', %s)", 'Url')
            ->select('a.DateInserted')
            ->select('a.InsertUserID as UserID')
            ->select("'Article'", '', 'RecordType')
            ->from('Article a');

        // Execute query
        $result = $this->SQL->getSelect();

        // Unset SQL
        $this->SQL->reset();

        return $result;
    }

    /**
     * Execute ArticleComment search query
     *
     * @param object $searchModel SearchModel (Dashboard)
     * @return object SQL result.
     */
    public function articleCommentSql($searchModel) {
        // Build search part of query
        $searchModel->addMatchSql($this->SQL, 'ac.Body', 'ac.DateInserted');

        // Build base query
        $this->SQL
            ->select('ac.ArticleCommentID as PrimaryID, a.Name as Title, ac.Body as Summary, ac.Format, '
                . 'ac.GuestName, a.ArticleCategoryID')
            ->select("'/article/comment/', ac.ArticleCommentID, '/#Comment_', ac.ArticleCommentID", "concat", 'Url')
            ->select('ac.DateInserted')
            ->select('ac.InsertUserID as UserID')
            ->select("'ArticleComment'", '', 'RecordType')
            ->from('ArticleComment ac')
            ->join('Article a', 'a.ArticleID = ac.ArticleID');

        // Execute query
        $result = $this->SQL->getSelect();

        // Unset SQL
        $this->SQL->reset();

        return $result;
    }

    /**
     * Add the searches for Articles to the search model.
     *
     * @param object $searchModel SearchModel (Dashboard)
     */
    public function search($searchModel) {
        $searchModel->addSearch($this->articleSql($searchModel));
        $searchModel->addSearch($this->articleCommentSql($searchModel));
    }
}