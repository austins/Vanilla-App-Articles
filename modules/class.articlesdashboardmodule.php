<?php defined('APPLICATION') or exit();
/**
 * Copyright (C) 2015  Austin S. (Shadowdare)
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
 * Renders the "Articles Dashboard" button.
 */
class ArticlesDashboardModule extends Gdn_Module {
    public function __construct($Sender = '') {
        parent::__construct($Sender);
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
        $Session = Gdn::Session();

        $Controller->EventArguments['ArticlesDashboardModule'] = &$this;
        $Controller->FireEvent('BeforeArticlesDashboardModuleButton');

        $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        if (!$Session->CheckPermission($PermissionsAllowed, false))
            return '';

        return parent::ToString();
    }
}
