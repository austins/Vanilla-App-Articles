<?php if(!defined('APPLICATION')) exit();

if(!function_exists('ShowArticlesDashboardMenu'))
    include($this->FetchViewLocation('helper_functions', 'articles', 'articles'));

ShowArticlesDashboardMenu($this->RequestMethod);
?>
The post view.