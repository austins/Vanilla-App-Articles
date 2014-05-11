<?php if(!defined('APPLICATION')) exit();

if(!function_exists('ShowArticlesDashboardMenu'))
    include($this->FetchViewLocation('helper_functions', 'compose', 'articles'));

ShowArticlesDashboardMenu($this->RequestMethod);
?>
The articles listing page.
