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

    public function GetByID($ArticleMediaID) {
        // Set up the query.
        $this->SQL->Select('am.*')
            ->From('ArticleMedia am')
            ->Where('am.ArticleMediaID', $ArticleMediaID);

        // Fetch data.
        $Media = $this->SQL->Get()->FirstRow();

        return $Media;
    }

    public function GetByArticleID($ArticleID) {
        // Set up the query.
        $this->SQL->Select('am.*')
            ->From('ArticleMedia am')
            ->Where('am.ArticleID', $ArticleID)
            ->Where('am.IsThumbnail', 0);

        // Fetch data.
        $Media = $this->SQL->Get();

        return $Media;
    }

    public function GetThumbnailByArticleID($ArticleID) {
        // Set up the query.
        $this->SQL->Select('am.*')
            ->From('ArticleMedia am')
            ->Where('am.ArticleID', $ArticleID)
            ->Where('am.IsThumbnail', 1);

        // Fetch data.
        $Media = $this->SQL->Get()->FirstRow();

        return $Media;
    }
}
