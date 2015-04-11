<?php defined('APPLICATION') or exit(); ?>
<div id="ArticlesDashboardWrap">
    <h1 class="H"><?php echo T('Articles Dashboard'); ?></h1>

    <div class="RecentlyPublished">
        <h2>Recently Published</h2>
        <ul class="DataList">
            <?php
            // Render the recently published block.
            $RecentlyPublished = $this->Data('RecentlyPublished')->Result();

            if (count($RecentlyPublished) == 0)
                echo 'None.';
            else {
                foreach ($RecentlyPublished as $Article) {
                    $Author = Gdn::UserModel()->GetID($Article->InsertUserID);

                    echo '<li class="Item RecentlyPublishedArticle">';
                    echo Wrap(Anchor($Article->Name, ArticleUrl($Article)), 'div');

                    echo '<div class="Meta Meta-Article">';
                    echo '<span class="MItem ArticleCategory">' . Anchor($Article->ArticleCategoryName,
                            ArticleCategoryUrl($Article->ArticleCategoryUrlCode)) . '</span>';
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

    <div class="RecentComments">
        <h2>Recent Comments</h2>
        <ul class="DataList">
            <?php
            // Render the recent comments block.
            $RecentComments = $this->Data('RecentComments')->Result();

            if (count($RecentComments) == 0)
                echo 'None.';
            else {
                foreach ($RecentComments as $Comment) {
                    $Permalink = '/article/comment/' . $Comment->ArticleCommentID . '/#Comment_' . $Comment->ArticleCommentID;
                    $Article = $this->ArticleModel->GetByID($Comment->ArticleID);

                    $UserName = $Comment->GuestName;
                    if ($Comment->InsertUserID > 0) {
                        $User = Gdn::UserModel()->GetID($Comment->InsertUserID);
                        $UserName = UserAnchor($User);
                    }
                    ?>
                    <li id="<?php echo 'Comment_' . $Comment->ArticleCommentID; ?>" class="Item">
                        <?php $this->FireEvent('BeforeItemContent'); ?>
                        <div class="ItemContent">
                            <div class="Message"><?php
                                echo SliceString(Gdn_Format::Text(Gdn_Format::To($Comment->Body, $Comment->Format),
                                    false), 250);
                                ?></div>
                            <div class="Meta">
                    <span class="MItem"><?php echo T('Comment in', 'in') . ' '; ?>
                        <b><?php echo Anchor(Gdn_Format::Text($Article->Name), $Permalink); ?></b></span>
                                <span class="MItem"><?php printf(T('Comment by %s'), $UserName); ?></span>
                                <span class="MItem"><?php echo Anchor(Gdn_Format::Date($Comment->DateInserted),
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

    <?php if (Gdn::Session()->CheckPermission('Articles.Articles.Edit')): ?>
        <div class="PendingArticles">
            <h2>Pending Articles</h2>
            <ul class="DataList">
                <?php
                // Render the recently published block.
                $PendingArticles = $this->Data('PendingArticles')->Result();

                if (count($PendingArticles) == 0)
                    echo 'None.';
                else {
                    foreach ($PendingArticles as $Article) {
                        $Author = Gdn::UserModel()->GetID($Article->InsertUserID);

                        echo '<li class="Item PendingArticle">';
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
</div>
