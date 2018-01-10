<?php
/**
 * ArticleMedia model
 *
 * @copyright 2015-2018 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Handles data for article media.
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
     * @param int $articleMediaID
     * @return bool|object
     */
    public function getByID($articleMediaID) {
        // Set up the query.
        $this->SQL->select('am.*')
            ->from('ArticleMedia am')
            ->where('am.ArticleMediaID', $articleMediaID);

        // Fetch data.
        $media = $this->SQL->get()->firstRow();

        return $media;
    }

    /**
     * Get multiple article media by article ID.
     *
     * @param int $articleID
     * @return bool|object
     */
    public function getByArticleID($articleID) {
        // Set up the query.
        $this->SQL->select('am.*')
            ->from('ArticleMedia am')
            ->where('am.ArticleID', $articleID)
            ->where('am.IsThumbnail', 0);

        // Fetch data.
        $media = $this->SQL->get();

        return $media;
    }

    /**
     * Get thumbnail by article ID.
     *
     * @param int $articleID
     * @return bool|object
     */
    public function getThumbnailByArticleID($articleID) {
        // Set up the query.
        $this->SQL->select('am.*')
            ->from('ArticleMedia am')
            ->where('am.ArticleID', $articleID)
            ->where('am.IsThumbnail', 1);

        // Fetch data.
        $media = $this->SQL->get()->firstRow();

        return $media;
    }
}
