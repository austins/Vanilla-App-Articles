<?php
/**
 * Register library classes and interfaces in the auto-loader.
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$Map = Gdn_Autoloader::MAP_LIBRARY;
$Context = Gdn_Autoloader::CONTEXT_APPLICATION;
$Path = PATH_APPLICATIONS . DS . 'articles' . DS . 'library';
$Options = array('Extension' => 'articles');

Gdn_Autoloader::registerMap($Map, $Context, $Path, $Options);

// Include the functions.render.php file.
require_once(PATH_APPLICATIONS . DS . 'articles' . DS . 'library' . DS . 'functions.render.php');
