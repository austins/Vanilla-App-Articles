<?php defined('APPLICATION') or exit();

echo '<h2 class="H">' . t('Article Comments') . '</h2>';

$comments = $this->data('Comments');

if (!$comments || count($comments) == 0) {
    echo wrap(t("This user has not posted any article comments yet."), 'div', array('Class' => 'Empty'));
} else {
    echo '<ul class="DataList SearchResults">';

    foreach ($comments as $comment) {
        $permalink = '/article/comment/' . $comment->ArticleCommentID . '/#Comment_' . $comment->ArticleCommentID;
        $user = userBuilder($comment, 'Insert');
        ?>
        <li id="<?php echo 'Comment_' . $comment->ArticleCommentID; ?>" class="Item">
            <?php $this->fireEvent('BeforeItemContent'); ?>
            <div class="ItemContent">
                <div class="Message"><?php
                    echo sliceString(Gdn_Format::text(Gdn_Format::to($comment->Body, $comment->Format), false), 250);
                    ?></div>
                <div class="Meta">
                    <span class="MItem"><?php echo t('Comment in', 'in') . ' '; ?>
                        <b><?php echo anchor(Gdn_Format::text($comment->ArticleName), $permalink); ?></b></span>
                    <span class="MItem"><?php printf(t('Comment by %s'), userAnchor($user)); ?></span>
                    <span class="MItem"><?php echo anchor(Gdn_Format::date($comment->DateInserted),
                            $permalink); ?></span>
                </div>
            </div>
        </li>
        <?php
    }

    echo '</ul>';
}