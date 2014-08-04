<?php
if (!defined('APPLICATION'))
    exit();

$Session = Gdn::Session();

$Comments = $this->Comments->Result();
$Article = $this->Article;
$CurrentOffset = 0;
if ($this->Comments->NumRows() > 0):
    ?>
    <section id="Comments" class="DataBox DataBox-Comments">
        <h2 class="CommentHeading">Comments</h2>
        <ul class="MessageList DataList Comments">
            <?php
            foreach ($Comments as $Comment) {
                $CssClass = 'Item Alt ItemComment';

                $User = false;
                if (is_numeric($Comment->InsertUserID))
                    $User = Gdn::UserModel()->GetID($Comment->InsertUserID);

                $ParentCommentID = is_numeric($Comment->ParentCommentID) ? $Comment->ParentCommentID : false;
                if($ParentCommentID)
                    $CssClass .= ' ItemCommentReply';
                ?>
                <li class="<?php echo $CssClass; ?>" id="Comment_<?php echo $Comment->CommentID; ?>">
                    <div class="Comment">
                        <!-- Comment options to go here. -->

                        <div class="Item-Header CommentHeader">
                            <div class="AuthorWrap">
                            <span class="Author">
                                <?php
                                if ($User) {
                                    echo UserPhoto($User);
                                    echo UserAnchor($User, 'Username');
                                    $this->FireEvent('AuthorPhoto');
                                } else {
                                    echo Wrap($Comment->GuestName, 'span', array('class' => 'Username GuestName'));
                                }
                                ?>
                            </span>
                            <span class="AuthorInfo">
                                <?php
                                echo ' ' . WrapIf(htmlspecialchars(GetValue('Title', $User)), 'span',
                                        array('class' => 'MItem AuthorTitle'));
                                echo ' ' . WrapIf(htmlspecialchars(GetValue('Location', $User)), 'span',
                                        array('class' => 'MItem AuthorLocation'));

                                $this->FireEvent('AuthorInfo');
                                ?>
                            </span>
                            </div>

                            <div class="Meta CommentMeta CommentInfo">
                            <span class="MItem DateCreated"><?php echo Anchor(Gdn_Format::Date($Comment->DateInserted,
                                        'html'), ArticleCommentUrl($Article, $Comment->CommentID), 'Permalink',
                                    array('name' => 'Item_' . ($CurrentOffset), 'rel' => 'nofollow')); ?></span>
                                <?php
                                echo DateUpdated($Comment, array('<span class="MItem">', '</span>'));

                                // Include IP Address if we have permission
                                if ($Session->CheckPermission('Garden.Moderation.Manage'))
                                    echo Wrap(IPAnchor($Comment->InsertIPAddress), 'span',
                                        array('class' => 'MItem IPAddress'));
                                ?>
                            </div>
                        </div>
                        <div class="Item-BodyWrap">
                            <div class="Item-Body">
                                <div class="Message">
                                    <?php
                                    Gdn::Controller()->FireEvent('BeforeCommentBody');
                                    echo Gdn_Format::To($Comment->Body, $Comment->Format);
                                    Gdn::Controller()->FireEvent('AfterCommentFormat');
                                    ?>
                                </div>
                                <?php
                                $this->FireEvent('AfterCommentBody');
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
    </section>
<?php
endif;
