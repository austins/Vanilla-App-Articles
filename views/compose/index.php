<?php defined('APPLICATION') or exit(); ?>
<div id="ArticlesDashboardWrap">
    <h1 class="H"><?php echo T('Articles Dashboard'); ?></h1>

    <div class="RecentlyPublished">
        <h2>Recently Published</h2>
        <ul class="DataList">
            <?php
            // Render the recently published block.
            $RecentlyPublished = $this->data('RecentlyPublished')->result();

            if (count($RecentlyPublished) == 0)
                echo 'None.';
            else {
                foreach ($RecentlyPublished as $Article) {
                    $Author = Gdn::userModel()->getID($Article->InsertUserID);

                    echo '<li class="Item RecentlyPublishedArticle">';
                    echo Wrap(Anchor($Article->Name, articleUrl($Article)), 'div');

                    echo '<div class="Meta Meta-Article">';
                    echo '<span class="MItem ArticleCategory">' . Anchor($Article->ArticleCategoryName,
                            articleCategoryUrl($Article->ArticleCategoryUrlCode)) . '</span>';
                    echo '<span class="MItem ArticleDate">' . Gdn_Format::date($Article->DateInserted,
                            '%e %B %Y - %l:%M %p') . '</span>';
                    echo '<span class="MItem ArticleAuthor">' . articleAuthorAnchor($Author) . '</span>';
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
            $RecentComments = $this->data('RecentComments')->result();

            if (count($RecentComments) == 0)
                echo 'None.';
            else {
                foreach ($RecentComments as $Comment) {
                    $Permalink = '/article/comment/' . $Comment->ArticleCommentID . '/#Comment_' . $Comment->ArticleCommentID;

                    $UserName = $Comment->GuestName;
                    if ($Comment->InsertUserID > 0) {
                        $User = Gdn::userModel()->getID($Comment->InsertUserID);
                        $UserName = UserAnchor($User);
                    }
                    ?>
                    <li id="<?php echo 'Comment_' . $Comment->ArticleCommentID; ?>" class="Item">
                        <?php $this->fireEvent('BeforeItemContent'); ?>
                        <div class="ItemContent">
                            <div class="Message"><?php
                                echo SliceString(Gdn_Format::Text(Gdn_Format::To($Comment->Body, $Comment->Format),
                                    false), 250);
                                ?></div>
                            <div class="Meta">
                    <span class="MItem"><?php echo T('Comment in', 'in') . ' '; ?>
                        <b><?php echo Anchor(Gdn_Format::Text($Comment->ArticleName), $Permalink); ?></b></span>
                                <span class="MItem"><?php printf(T('Comment by %s'), $UserName); ?></span>
                                <span class="MItem"><?php echo Anchor(Gdn_Format::date($Comment->DateInserted),
                                        $Permalink); ?></span>
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
                $PendingArticles = $this->data('PendingArticles')->result();

                if (count($PendingArticles) == 0)
                    echo 'None.';
                else {
                    foreach ($PendingArticles as $Article) {
                        $Author = Gdn::userModel()->getID($Article->InsertUserID);

                        echo '<li class="Item PendingArticle">';
                        echo Wrap(Anchor($Article->Name, articleUrl($Article)), 'div',
                            array('class' => 'ArticleTitle'));

                        echo '<div class="ArticleMeta">';
                        echo '<span class="ArticleDate">' . Gdn_Format::date($Article->DateInserted,
                                '%e %B %Y - %l:%M %p') . '</span>';
                        echo '<span class="ArticleAuthor">' . articleAuthorAnchor($Author) . '</span>';
                        echo '</div>';
                        echo '</li>';
                    }
                }
                ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
