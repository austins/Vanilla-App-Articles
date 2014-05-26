<?php
if (!defined('APPLICATION'))
    exit();

$Session = Gdn::Session();

$Comments = $this->Comments->Result();

if ($this->Comments->NumRows() > 0):
    ?>
    <section id="Comments" class="DataBox DataBox-Comments">
        <h2 class="CommentHeading">Comments</h2>
        <ul class="MessageList DataList Comments">
            <?php
            foreach ($Comments as $Comment) {
                $User = Gdn::UserModel()->GetID($Comment->InsertUserID);
                ?>
                <li class="Item Alt ItemComment" id="Comment_<?php echo $Comment->CommentID; ?>">
                    <div class="Comment">
                        <!-- Comment options to go here. -->

                        <div class="Item-Header CommentHeader">
                            <div class="AuthorWrap">
                            <span class="Author">
                                <?php
                                echo UserPhoto($Author);
                                echo UserAnchor($Author, 'Username');
                                $this->FireEvent('AuthorPhoto');
                                ?>
                            </span>
                            <span class="AuthorInfo">
                                <?php
                                echo ' ' . WrapIf(htmlspecialchars(GetValue('Title', $Author)), 'span',
                                        array('class' => 'MItem AuthorTitle'));
                                echo ' ' . WrapIf(htmlspecialchars(GetValue('Location', $Author)), 'span',
                                        array('class' => 'MItem AuthorLocation'));

                                $this->FireEvent('AuthorInfo');
                                ?>
                            </span>
                            </div>

                            <div class="Meta CommentMeta CommentInfo">
                            <span class="MItem DateCreated"><?php echo Anchor(Gdn_Format::Date($Comment->DateInserted,
                                        'html'), $Permalink, 'Permalink',
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
                            </div>
                        </div>
                    </div>
                </li>
            <?php } ?>
        </ul>
    </section>
<?php
endif;
