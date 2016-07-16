<?php defined('APPLICATION') or exit(); ?>
<div id="ArticlesDashboardWrap">
    <h1 class="H"><?php echo t('Articles Dashboard'); ?></h1>

    <div class="RecentlyPublished">
        <h2>Recently Published</h2>
        <ul class="DataList">
            <?php
            // Render the recently published block.
            $recentlyPublished = $this->data('RecentlyPublished')->result();

            if (count($recentlyPublished) == 0) {
                echo 'None.';
            } else {
                foreach ($recentlyPublished as $article) {
                    $author = Gdn::userModel()->getID($article->InsertUserID);

                    echo '<li class="Item RecentlyPublishedArticle">';
                    echo wrap(anchor($article->Name, articleUrl($article)), 'div');

                    echo '<div class="Meta Meta-Article">';
                    echo '<span class="MItem ArticleCategory">' . anchor($article->ArticleCategoryName,
                            articleCategoryUrl($article->ArticleCategoryUrlCode)) . '</span>';
                    echo '<span class="MItem ArticleDate">' . Gdn_Format::date($article->DateInserted,
                            '%e %B %Y - %l:%M %p') . '</span>';
                    echo '<span class="MItem ArticleAuthor">' . articleAuthorAnchor($author) . '</span>';
                    echo '</div>';
                    echo '</li>';
                }
            }
            ?>
        </ul>
    </div>

    <div class="RecentComments">
        <h2>Recent Comments</h2>
        <ul class="DataList">
            <?php
            // Render the recent comments block.
            $recentComments = $this->data('RecentComments')->result();

            if (count($recentComments) == 0) {
                echo 'None.';
            } else {
                foreach ($recentComments as $comment) {
                    $permalink = '/article/comment/' . $comment->ArticleCommentID . '/#Comment_' . $comment->ArticleCommentID;

                    $userName = $comment->GuestName;
                    if ($comment->InsertUserID > 0) {
                        $user = Gdn::userModel()->getID($comment->InsertUserID);
                        $userName = userAnchor($user);
                    }
                    ?>
                    <li id="<?php echo 'Comment_' . $comment->ArticleCommentID; ?>" class="Item">
                        <?php $this->fireEvent('BeforeItemContent'); ?>
                        <div class="ItemContent">
                            <div class="Message"><?php
                                echo sliceString(Gdn_Format::text(Gdn_Format::to($comment->Body, $comment->Format),
                                    false), 250);
                                ?></div>
                            <div class="Meta">
                    <span class="MItem"><?php echo t('Comment in', 'in') . ' '; ?>
                        <b><?php echo anchor(Gdn_Format::text($comment->ArticleName), $permalink); ?></b></span>
                                <span class="MItem"><?php printf(t('Comment by %s'), $userName); ?></span>
                                <span class="MItem"><?php echo anchor(Gdn_Format::date($comment->DateInserted),
                                        $permalink); ?></span>
                            </div>
                        </div>
                    </li>
                    <?php
                }
            }
            ?>
        </ul>
    </div>

    <?php if (Gdn::session()->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', 'any')): ?>
        <div class="PendingArticles">
            <h2>Pending Articles</h2>
            <ul class="DataList">
                <?php
                // Render the recently published block.
                $pendingArticles = $this->data('PendingArticles')->result();

                if (count($pendingArticles) == 0) {
                    echo 'None.';
                } else {
                    foreach ($pendingArticles as $article) {
                        $author = Gdn::userModel()->getID($article->InsertUserID);

                        echo '<li class="Item PendingArticle">';
                        echo wrap(anchor($article->Name, articleUrl($article)), 'div',
                            array('class' => 'ArticleTitle'));

                        echo '<div class="ArticleMeta">';
                        echo '<span class="ArticleDate">' . Gdn_Format::date($article->DateInserted,
                                '%e %B %Y - %l:%M %p') . '</span>';
                        echo '<span class="ArticleAuthor">' . articleAuthorAnchor($author) . '</span>';
                        echo '</div>';
                        echo '</li>';
                    }
                }
                ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
