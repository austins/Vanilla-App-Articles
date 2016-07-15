<?php defined('APPLICATION') or exit();

echo '<div class="BoxButtons BoxArticlesDashboard">';
echo anchor(t('Articles Dashboard'), '/compose',
    'Button Action Big Primary BigButton ArticlesDashboard');

Gdn::controller()->fireEvent('AfterArticlesDashboardButton');
echo '</div>';
