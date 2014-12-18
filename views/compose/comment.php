<?php defined('APPLICATION') or exit();

$Session = Gdn::Session();
$GuestCommenting = (C('Articles.Comments.AllowGuests', false) && !$Session->IsValid());
$Editing = isset($this->Comment);

$this->EventArguments['FormCssClass'] = 'MessageForm CommentForm FormTitleWrapper';

if ($GuestCommenting)
    $this->EventArguments['FormCssClass'] .= ' Guest';

$this->FireEvent('BeforeCommentForm');
?>
<div id="CommentBox" class="<?php echo $this->EventArguments['FormCssClass']; ?>">
    <h2 class="H"><?php echo T($Editing ? 'Edit Comment' : 'Leave a Comment'); ?></h2>

    <div class="CommentFormWrap">
        <?php if (Gdn::Session()->IsValid()): ?>
            <div class="Form-HeaderWrap">
                <div class="Form-Header">
            <span class="Author">
               <?php
               if (C('Articles.Comment.UserPhotoFirst', true)) {
                   echo UserPhoto($Session->User);
                   echo UserAnchor($Session->User, 'Username');
               } else {
                   echo UserAnchor($Session->User, 'Username');
                   echo UserPhoto($Session->User);
               }
               ?>
            </span>
                </div>
            </div>
        <?php endif; ?>
        <div class="Form-BodyWrap">
            <div class="Form-Body">
                <div class="FormWrapper FormWrapper-Condensed">
                    <?php
                    echo $this->Form->Open(array('id' => 'Form_Comment'));
                    echo $this->Form->Errors();

                    // Guest fields.
                    if ($GuestCommenting) {
                        echo $this->Form->Label('Your Name', 'GuestName');
                        echo $this->Form->TextBox('GuestName');
                        echo '<br />';
                        echo $this->Form->Label('Your Email', 'GuestEmail');
                        echo $this->Form->TextBox('GuestEmail');
                    }

                    $this->FireEvent('BeforeBodyField');

                    if ($GuestCommenting) {
                        echo '<br />';
                        echo $this->Form->Label('Message', 'Body');
                    }

                    echo $this->Form->BodyBox('Body', array('Table' => 'ArticleComment', 'tabindex' => 1));

                    echo '<div class="CommentOptions List Inline">';
                    $this->FireEvent('AfterBodyField');
                    echo '</div>';

                    echo "<div class=\"Buttons\">\n";
                    $this->FireEvent('BeforeFormButtons');
                    $CancelText = T('Home');
                    $CancelClass = 'Back';
                    if ($Editing) {
                        $CancelText = T('Cancel');
                        $CancelClass = 'Cancel';
                    }

                    echo '<span class="' . $CancelClass . '">';
                    echo Anchor($CancelText, '/');
                    echo '</span>';

                    $ButtonOptions = array('class' => 'Button Primary CommentButton');
                    $ButtonOptions['tabindex'] = 2;

                    if ((!$Editing && $Session->IsValid()) || (!$Editing && $GuestCommenting)) {
                        echo ' ' . Anchor(T('Preview'), '#', 'Button PreviewButton') . "\n";
                        echo ' ' . Anchor(T('Edit'), '#', 'Button WriteButton Hidden') . "\n";
                    }

                    if ($Session->IsValid()) {
                        echo $this->Form->Button($Editing ? 'Save Comment' : 'Post Comment', $ButtonOptions);
                    } else if($GuestCommenting) {
                        echo ' ' . $this->Form->Button($Editing ? 'Save Comment' : 'Comment As Guest', $ButtonOptions);
                    } else {
                        $AllowSigninPopup = C('Garden.SignIn.Popup');
                        $Attributes = array('tabindex' => '-1');
                        if (!$AllowSigninPopup)
                            $Attributes['target'] = '_parent';

                        $AuthenticationUrl = SignInUrl(Gdn::Controller()->SelfUrl);
                        $CssClass = 'Button Primary Stash';
                        if ($AllowSigninPopup)
                            $CssClass .= ' SignInPopup';

                        echo Anchor(T('Sign In'), $AuthenticationUrl, $CssClass, $Attributes);
                    }

                    $this->FireEvent('AfterFormButtons');
                    echo "</div>\n";
                    echo $this->Form->Close();
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>