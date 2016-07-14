<?php defined('APPLICATION') or exit();
/**
 * Copyright (C) 2015  Austin S.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
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
 * Renders recently published articles
 */
class ArticlesModule extends Gdn_Module {
    public function __construct($Sender = '') {
        // Load articles.
        $ArticleModel = new ArticleModel();

        $Limit = 5;
        $ArticleWheres = array('a.Status' => ArticleModel::STATUS_PUBLISHED); // Category must have at least one article.
        $Articles = $ArticleModel->get(0, $Limit, $ArticleWheres);

        $this->Data = $Articles;

        parent::__construct($Sender);

        $this->_ApplicationFolder = 'articles';
    }

    /**
     * Returns the asset name that the panel will be displayed to.
     *
     * @return string
     */
    public function AssetTarget() {
        return 'Panel';
    }

    /**
     * Returns the module as a string.
     *
     * @return string
     */
    public function ToString() {
        $Controller = Gdn::Controller();
        $session = Gdn::session();

        $Controller->EventArguments['ArticlesModule'] = &$this;
        $Controller->fireEvent('BeforeArticlesModule');

        if (!$session->checkPermission('Articles.Articles.View', true, 'ArticleCategory', 'any'))
            return '';

        return parent::ToString();
    }
}
