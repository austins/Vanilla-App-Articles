<?php
if (!defined('APPLICATION'))
    exit();

/**
 * Handles data for articles.
 */
class ArticleMediaModel extends Gdn_Model {
    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('ArticleMedia');
    }

    public function GetByArticle($ArticleID) {
        // Set up the query.
        $this->SQL->Select('am.*')
            ->From('ArticleMedia am')
            ->Where('am.ArticleID', $ArticleID);

        // Fetch data.
        $Media = $this->SQL->Get();

        return $Media;
    }
}
