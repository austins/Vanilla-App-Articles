<?php defined('APPLICATION') or exit();

$Controller = Gdn::Controller();
$session = Gdn::session();
?>
<section id="comments">
    <?php
    $Comments = $this->ArticleComments->result();
    $Article = $this->Article;
    $CurrentOffset = 0;
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
            echo $this->Pager->ToString('less');
            echo '</span>';
            ?>

            <ul class="MessageList DataList Comments">
                <?php
                foreach ($Comments as $Comment) {
                    $CssClass = 'Item Alt ItemComment';

                    $User = false;
                    if (is_numeric($Comment->InsertUserID))
                        $User = Gdn::userModel()->getID($Comment->InsertUserID);

                    // Get user meta for articles app.
                    $UserMeta = Gdn::userModel()->GetMeta($User->UserID, 'Articles.%', 'Articles.');
                    $AuthorDisplayName = false;
                    if (isset($UserMeta['AuthorDisplayName'])) {
                        $AuthorDisplayName = $UserMeta['AuthorDisplayName'];
                    }

                    $ParentArticleCommentID = is_numeric($Comment->ParentArticleCommentID) ?
                        $Comment->ParentArticleCommentID : false;
                    if ($ParentArticleCommentID)
                        $CssClass .= ' ItemCommentReply';
                    ?>
                    <li class="<?php echo $CssClass; ?>" id="Comment_<?php echo $Comment->ArticleCommentID; ?>">
                        <div class="Comment">
                            <?php WriteCommentOptions($Comment); ?>

                            <div class="Item-Header CommentHeader">
                                <div class="AuthorWrap">
                                <span class="Author">
                                    <?php
                                    if ($User) {
                                        echo UserPhoto($User);
                                        echo UserAnchor($User, 'Username');

                                        if (($AuthorDisplayName != '') && ($AuthorDisplayName != $User->Name))
                                            echo ' (' . $AuthorDisplayName . ')';

                                        $this->fireEvent('AuthorPhoto');
                                    } else {
                                        echo Wrap($Comment->GuestName, 'span', array('class' => 'Username GuestName'));
                                    }
                                    ?>
                                </span>
                                <span class="AuthorInfo">
                                    <?php
                                    echo ' ' . WrapIf(htmlspecialchars(val('Title', $User)), 'span',
                                            array('class' => 'MItem AuthorTitle'));
                                    echo ' ' . WrapIf(htmlspecialchars(val('Location', $User)), 'span',
                                            array('class' => 'MItem AuthorLocation'));

                                    $this->fireEvent('AuthorInfo');
                                    ?>
                                </span>
                                </div>

                                <div class="Meta CommentMeta CommentInfo">
                                <span class="MItem DateCreated"><?php echo Anchor(Gdn_Format::date($Comment->DateInserted,
                                            'html'), articleCommentUrl($Comment->ArticleCommentID), 'Permalink',
                                        array('name' => 'Item_' . ($CurrentOffset), 'rel' => 'nofollow')); ?></span>
                                    <?php
                                    echo DateUpdated($Comment, array('<span class="MItem">', '</span>'));

                                    // Include IP Address if we have permission
                                    if ($session->checkPermission('Garden.Moderation.Manage'))
                                        echo Wrap(IPAnchor($Comment->InsertIPAddress), 'span',
                                            array('class' => 'MItem IPAddress'));
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
                                        $Comment->FormatBody = Gdn_Format::To($Comment->Body, $Comment->Format);
                                        $Controller->EventArguments['Object'] = &$Comment;
                                        $Controller->EventArguments['Type'] = 'ArticleComment';

                                        $Controller->fireEvent('BeforeCommentBody');
                                        echo $Comment->FormatBody;
                                        $Controller->fireEvent('AfterCommentFormat');
                                        ?>
                                    </div>
                                    <?php
                                    $this->fireEvent('AfterCommentBody');
                                    WriteArticleReactions($Comment);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </li>
                    <?php
                    $CurrentOffset++;
                } ?>
            </ul>
            <?php
            // Pager
            $this->fireEvent('AfterComments');
            if ($this->Pager->LastPage()) {
                $LastCommentID = $this->addDefinition('LastCommentID');
                if (!$LastCommentID || $this->ArticleCommentModel->LastArticleCommentID > $LastCommentID)
                    $this->addDefinition('LastCommentID', (int)$this->ArticleCommentModel->LastArticleCommentID);
            }

            echo '<div class="P PagerWrap">';
            $this->Pager->Wrapper = '<div %1$s>%2$s</div>';
            echo $this->Pager->ToString('more');
            echo '</div>';
            ?>
        </div>
    <?php
    endif;

    ShowCommentForm();
    ?>
</section>