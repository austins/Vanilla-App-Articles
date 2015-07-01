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

// Register library classes and interfaces in the auto-loader.
$Map = Gdn_Autoloader::MAP_LIBRARY;
$Context = Gdn_Autoloader::CONTEXT_APPLICATION;
$Path = PATH_APPLICATIONS . DS . 'articles' . DS . 'library';
$Options = array('Extension' => 'articles');

Gdn_Autoloader::RegisterMap($Map, $Context, $Path, $Options);

// Include the functions.render.php file.
require_once(PATH_APPLICATIONS . DS . 'articles' . DS . 'library' . DS . 'functions.render.php');
