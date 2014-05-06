<?php if(!defined('APPLICATION')) exit();

if(!function_exists('ShowArticlesDashboardMenu'))
    include($this->FetchViewLocation('helper_functions', 'compose', 'articles'));

ShowArticlesDashboardMenu($this->RequestMethod);
?>
<div id="ArticlesDashboardWrap">
    <div id="FirstColumn" class="ArticlesDashboardColumn">
        <h2>Recently Published</h2>
        <ul>
            <?php
            // Render the recently published column.
            $RecentlyPublished = $this->Data('RecentlyPublished')->Result();

            foreach($RecentlyPublished as $Article) {
                $Author = Gdn::UserModel()->GetID($Article->InsertUserID);

                echo '<li class="RecentlyPublishedArticle">';
                    echo Wrap(Anchor($Article->Name, ArticleUrl($Article)), 'div', array('class' => 'ArticleTitle'));

                    echo '<div class="ArticleMeta">';
                        echo '<span class="ArticleDate">' . Gdn_Format::Date($Article->DateInserted, '%e %B %Y - %l:%M %p') . '</span>';
                        echo '<span class="ArticleAuthor">' . UserAnchor($Author) . '</span>';
                    echo '</div>';
                echo '</li>';
            }
            ?>
        </ul>
    </div>

    <div id="SecondColumn" class="ArticlesDashboardColumn">
        <h2>Recent Comments</h2>
        <div>
            None.
        </div>
    </div>

    <div class="ClearFix"></div>
</div>
