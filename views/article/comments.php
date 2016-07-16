<?php defined('APPLICATION') or exit();

$controller = Gdn::controller();
$session = Gdn::session();
?>
<section id="comments">
    <?php
    $comments = $this->ArticleComments->result();
    $article = $this->Article;
    $currentOffset = 0;
    $canGuestsComment = c('Articles.Comments.AllowGuests', false);

    if (($this->ArticleComments->numRows() > 0) || (!$session->isValid() && !$canGuestsComment)) {
        echo '<h2 class="CommentHeading">' . t('Comments') . '</h2>';
    }

    if ($this->ArticleComments->numRows() > 0):
        ?>
        <div class="DataBox DataBox-Comments">
            <?php
            // Pager
            $this->Pager->Wrapper = '<span %1$s>%2$s</span>';
            echo '<span class="BeforeCommentHeading">';
            $this->fireEvent('BeforeCommentHeading');
            echo $this->Pager->toString('less');
            echo '</span>';
            ?>

            <ul class="MessageList DataList Comments">
                <?php
                foreach ($comments as $comment) {
                    $cssClass = 'Item Alt ItemComment';

                    $user = false;
                    if (is_numeric($comment->InsertUserID)) {
                        $user = Gdn::userModel()->getID($comment->InsertUserID);
                    }

                    // Get user meta for articles app.
                    $userMeta = Gdn::userModel()->getMeta($user->UserID, 'Articles.%', 'Articles.');
                    $authorDisplayName = false;
                    if (isset($userMeta['AuthorDisplayName'])) {
                        $authorDisplayName = $userMeta['AuthorDisplayName'];
                    }

                    $parentArticleCommentID = is_numeric($comment->ParentArticleCommentID) ?
                        $comment->ParentArticleCommentID : false;
                    if ($parentArticleCommentID) {
                        $cssClass .= ' ItemCommentReply';
                    }
                    ?>
                    <li class="<?php echo $cssClass; ?>" id="Comment_<?php echo $comment->ArticleCommentID; ?>">
                        <div class="Comment">
                            <?php writeCommentOptions($comment); ?>

                            <div class="Item-Header CommentHeader">
                                <div class="AuthorWrap">
                                <span class="Author">
                                    <?php
                                    if ($user) {
                                        echo userPhoto($user);
                                        echo userAnchor($user, 'Username');

                                        if (($authorDisplayName != '') && ($authorDisplayName != $user->Name)) {
                                            echo ' (' . $authorDisplayName . ')';
                                        }

                                        $this->fireEvent('AuthorPhoto');
                                    } else {
                                        echo wrap($comment->GuestName, 'span', array('class' => 'Username GuestName'));
                                    }
                                    ?>
                                </span>
                                    <span class="AuthorInfo">
                                    <?php
                                    echo ' ' . wrapIf(htmlspecialchars(val('Title', $user)), 'span',
                                            array('class' => 'MItem AuthorTitle'));
                                    echo ' ' . wrapIf(htmlspecialchars(val('Location', $user)), 'span',
                                            array('class' => 'MItem AuthorLocation'));

                                    $this->fireEvent('AuthorInfo');
                                    ?>
                                </span>
                                </div>

                                <div class="Meta CommentMeta CommentInfo">
                                <span
                                    class="MItem DateCreated"><?php echo anchor(Gdn_Format::date($comment->DateInserted,
                                        'html'), articleCommentUrl($comment->ArticleCommentID), 'Permalink',
                                        array('name' => 'Item_' . ($currentOffset), 'rel' => 'nofollow')); ?></span>
                                    <?php
                                    echo dateUpdated($comment, array('<span class="MItem">', '</span>'));

                                    // Include IP Address if we have permission
                                    if ($session->checkPermission('Garden.Moderation.Manage')) {
                                        echo wrap(ipAnchor($comment->InsertIPAddress), 'span',
                                            array('class' => 'MItem IPAddress'));
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="Item-BodyWrap">
                                <div class="Item-Body">
                                    <div class="Message">
                                        <?php
                                        // DEPRECATED ARGUMENTS (as of 2.1)
                                        // $Comment->FormatBody, Object, and Type event args
                                        // added on 2014-09-12 for Emotify support.
                                        $comment->FormatBody = Gdn_Format::to($comment->Body, $comment->Format);
                                        $controller->EventArguments['Object'] = &$comment;
                                        $controller->EventArguments['Type'] = 'ArticleComment';

                                        $controller->fireEvent('BeforeCommentBody');
                                        echo $comment->FormatBody;
                                        $controller->fireEvent('AfterCommentFormat');
                                        ?>
                                    </div>
                                    <?php
                                    $this->fireEvent('AfterCommentBody');
                                    writeArticleReactions($comment);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php
                    $currentOffset++;
                } ?>
            </ul>
            <?php
            // Pager
            $this->fireEvent('AfterComments');
            if ($this->Pager->lastPage()) {
                $lastCommentID = $this->addDefinition('LastCommentID');
                if (!$lastCommentID || $this->ArticleCommentModel->LastArticleCommentID > $lastCommentID) {
                    $this->addDefinition('LastCommentID', (int)$this->ArticleCommentModel->LastArticleCommentID);
                }
            }

            echo '<div class="P PagerWrap">';
            $this->Pager->Wrapper = '<div %1$s>%2$s</div>';
            echo $this->Pager->toString('more');
            echo '</div>';
            ?>
        </div>
        <?php
    endif;

    showCommentForm();
    ?>
</section>