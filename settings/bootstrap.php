<?php defined('APPLICATION') or exit();

// Register library classes and interfaces in the auto-loader.
$Map = Gdn_Autoloader::MAP_LIBRARY;
$Context = Gdn_Autoloader::CONTEXT_APPLICATION;
$Path = PATH_APPLICATIONS . DS . 'articles' . DS . 'library';
$Options = array('Extension' => 'articles');

Gdn_Autoloader::RegisterMap($Map, $Context, $Path, $Options);

// Include the functions.render.php file.
require_once(PATH_APPLICATIONS . DS . 'articles' . DS . 'library' . DS . 'functions.render.php');
