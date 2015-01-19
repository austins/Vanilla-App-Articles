<?php defined('APPLICATION') or exit();

if (!function_exists('ShowArticlesDashboardMenu'))
    include($this->FetchViewLocation('helper_functions', 'compose', 'articles'));

ShowArticlesDashboardMenu($this->RequestMethod);
?>
<div id="ArticlesDashboardWrap">
    <div class="ArticlesDashboardColumn FirstColumn">
        <h2>Recently Published</h2>
        <ul>
            <?php
            // Render the recently published column.
            $RecentlyPublished = $this->Data('RecentlyPublished')->Result();

            if (count($RecentlyPublished) == 0)
                echo 'None.';
            else {
                foreach ($RecentlyPublished as $Article) {
                    $Author = Gdn::UserModel()->GetID($Article->InsertUserID);

                    echo '<li class="RecentlyPublishedArticle">';
                    echo Wrap(Anchor($Article->Name, ArticleUrl($Article)), 'div', array('class' => 'ArticleTitle'));

                    echo '<div class="Meta Meta-Article">';
                    echo '<span class="MItem ArticleDate">' . Gdn_Format::Date($Article->DateInserted,
                            '%e %B %Y - %l:%M %p') . '</span>';
                    echo '<span class="MItem ArticleAuthor">' . ArticleAuthorAnchor($Author) . '</span>';
                    echo '</div>';
                    echo '</li>';
                }
            }
            ?>
        </ul>
    </div>

    <div class="ArticlesDashboardColumn SecondColumn">
        <h2>Recent Comments</h2>

        <div>
            None.
        </div>
    </div>

    <div class="ClearFix"></div>

    <?php if (Gdn::Session()->CheckPermission('Articles.Articles.Edit')): ?>
        <div class="ArticlesDashboardColumn FirstColumn">
            <h2>Pending Articles</h2>
            <ul>
                <?php
                // Render the recently published column.
                $PendingArticles = $this->Data('PendingArticles')->Result();

                if (count($PendingArticles) == 0)
                    echo 'None.';
                else {
                    foreach ($PendingArticles as $Article) {
                        $Author = Gdn::UserModel()->GetID($Article->InsertUserID);

                        echo '<li class="PendingArticle">';
                        echo Wrap(Anchor($Article->Name, ArticleUrl($Article)), 'div',
                            array('class' => 'ArticleTitle'));

                        echo '<div class="ArticleMeta">';
                        echo '<span class="ArticleDate">' . Gdn_Format::Date($Article->DateInserted,
                                '%e %B %Y - %l:%M %p') . '</span>';
                        echo '<span class="ArticleAuthor">' . ArticleAuthorAnchor($Author) . '</span>';
                        echo '</div>';
                        echo '</li>';
                    }
                }
                ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="ClearFix"></div>
</div>
