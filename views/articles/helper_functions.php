<?php if(!defined('APPLICATION')) exit();

if(!function_exists('ShowArticlesDashboardMenu')) {
    function ShowArticlesDashboardMenu($RequestMethod = '') {
        $MenuLinks = array(
                'dashboard' => array('Text' => 'Dashboard', 'Destination' => '/articles/dashboard/'),
                'post' => array('Text' => 'New Article', 'Destination' => '/articles/post/article/')
            );

        echo '<div id="ArticlesDashboardMenu">';
            echo '<ul>';
            foreach($MenuLinks as $MethodName => $Link) {
                $LinkCssClass = $Link['Text'];

                $WrapAttributes = array();
                if(strtolower($RequestMethod) == $MethodName)
                    $WrapAttributes['class'] = 'Active';

                echo Wrap(Anchor($Link['Text'], $Link['Destination'], $LinkCssClass), 'li', $WrapAttributes);
            }
            echo '</ul>';
        echo '</div>';
    }
}
