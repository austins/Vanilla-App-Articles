<?php if(!defined('APPLICATION')) exit();

echo '<div class="BoxButtons BoxArticlesDashboard">';
    echo Anchor(T('Articles Dashboard'), '/articles/dashboard/',
        'Button Action Big Primary BigButton ArticlesDashboard');

    Gdn::Controller()->FireEvent('AfterArticlesDashboardButton');
echo '</div>';
