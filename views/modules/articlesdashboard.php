<?php defined('APPLICATION') or exit();

echo '<div class="BoxButtons BoxArticlesDashboard">';
echo Anchor(T('Articles Dashboard'), '/compose',
    'Button Action Big Primary BigButton ArticlesDashboard');

Gdn::Controller()->fireEvent('AfterArticlesDashboardButton');
echo '</div>';
