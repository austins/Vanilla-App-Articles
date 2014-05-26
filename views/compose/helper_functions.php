<?php
if (!defined('APPLICATION'))
    exit();

if (!function_exists('ShowArticlesDashboardMenu')) {
    function ShowArticlesDashboardMenu($RequestMethod = '') {
        $MenuLinks = array(
            'index' => array('Text' => 'Dashboard', 'Destination' => '/compose/'),
            'posts' => array('Text' => 'Posts', 'Destination' => '/compose/posts/'),
            'article' => array('Text' => 'New Article', 'Destination' => '/compose/article/')
        );

        echo '<div id="ArticlesDashboardMenu">';
        echo '<ul>';
        foreach ($MenuLinks as $MethodName => $Link) {
            $LinkCssClass = $Link['Text'];

            $WrapAttributes = array();
            if (strtolower($RequestMethod) == $MethodName)
                $WrapAttributes['class'] = 'Active';

            echo Wrap(Anchor($Link['Text'], $Link['Destination'], $LinkCssClass), 'li', $WrapAttributes);
        }
        echo '</ul>';
        echo '</div>';
    }
}
