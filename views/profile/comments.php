<?php defined('APPLICATION') or exit();

echo '<h2 class="H">' . T('Article Comments') . '</h2>';

$Comments = $this->Data('Comments');

if (!$Comments || count($Comments) == 0) {
    echo Wrap(T("This user has not posted any article comments yet."), 'div', array('Class' => 'Empty'));
} else {
    echo '<ul class="DataList SearchResults">';

    foreach ($Comments as $Comment) {
        $Permalink = '/article/comment/' . $Comment->ArticleCommentID . '/#Comment_' . $Comment->ArticleCommentID;
        $User = UserBuilder($Comment, 'Insert');
        ?>
        <li id="<?php echo 'Comment_' . $Comment->ArticleCommentID; ?>" class="Item">
            <?php $this->FireEvent('BeforeItemContent'); ?>
            <div class="ItemContent">
                <div class="Message"><?php
                    echo SliceString(Gdn_Format::Text(Gdn_Format::To($Comment->Body, $Comment->Format), false), 250);
                    ?></div>
                <div class="Meta">
                    <span class="MItem"><?php echo T('Comment in', 'in') . ' '; ?>
                        <b><?php echo Anchor(Gdn_Format::Text($Comment->ArticleName), $Permalink); ?></b></span>
                    <span class="MItem"><?php printf(T('Comment by %s'), UserAnchor($User)); ?></span>
                    <span class="MItem"><?php echo Anchor(Gdn_Format::Date($Comment->DateInserted), $Permalink); ?></span>
                </div>
            </div>
        </li>
    <?php
    }

    echo '</ul>';
}