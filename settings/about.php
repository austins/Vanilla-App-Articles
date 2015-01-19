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

// An associative array of information about this application.
$ApplicationInfo['Articles'] = array(
    'Description' => 'Provides a way to create articles.',
    'Version' => '0.0.1',
    'Author' => 'Austin S. (Shadowdare)',
    'AuthorUrl' => 'http://vanillaforums.org/profile/addons/16014/Shadowdare',
    'Url' => 'http://vanillaforums.org/addon/articles-application',
    'License' => 'GPLv3',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'RegisterPermissions' => false,
    'SetupController' => 'setup',
    'Settings' => '/settings/articles/'
);
