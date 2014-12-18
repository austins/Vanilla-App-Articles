<?php defined('APPLICATION') or exit();

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

    /**
     * Get article media by ID.
     *
     * @param int $ArticleMediaID
     * @return bool|object
     */
    public function GetByID($ArticleMediaID) {
        // Set up the query.
        $this->SQL->Select('am.*')
            ->From('ArticleMedia am')
            ->Where('am.ArticleMediaID', $ArticleMediaID);

        // Fetch data.
        $Media = $this->SQL->Get()->FirstRow();

        return $Media;
    }

    /**
     * Get multiple article media by article ID.
     *
     * @param int $ArticleID
     * @return bool|object
     */
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

    /**
     * Get thumbnail by article ID.
     *
     * @param int $ArticleID
     * @return bool|object
     */
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
