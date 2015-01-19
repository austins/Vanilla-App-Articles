<?php defined('APPLICATION') or exit();
/**
 * Copyright (C) 2015  Austin S. (Shadowdare)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
