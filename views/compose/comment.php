<?php defined('APPLICATION') or exit();

$session = Gdn::session();
$guestCommenting = (c('Articles.Comments.AllowGuests', false) && !$session->isValid());
$editing = isset($this->Comment);

$this->EventArguments['FormCssClass'] = 'MessageForm CommentForm FormTitleWrapper';

if ($guestCommenting) {
    $this->EventArguments['FormCssClass'] .= ' Guest';
}

$this->fireEvent('BeforeCommentForm');
?>
<div id="CommentBox" class="<?php echo $this->EventArguments['FormCssClass']; ?>">
    <h2 class="H"><?php echo t($editing ? 'Edit Comment' : 'Leave a Comment'); ?></h2>

    <div class="CommentFormWrap">
        <?php if (Gdn::session()->isValid()): ?>
            <div class="Form-HeaderWrap">
                <div class="Form-Header">
            <span class="Author">
               <?php
               if (c('Articles.Comment.UserPhotoFirst', true)) {
                   echo userPhoto($session->User);
                   echo userAnchor($session->User, 'Username');
               } else {
                   echo userAnchor($session->User, 'Username');
                   echo userPhoto($session->User);
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
                    echo $this->Form->open(array('id' => 'Form_Comment'));
                    echo $this->Form->errors();

                    // Guest fields.
                    if ($guestCommenting) {
                        echo $this->Form->label('Your Name', 'GuestName');
                        echo $this->Form->textBox('GuestName');
                        echo '<br />';
                        echo $this->Form->label('Your Email', 'GuestEmail');
                        echo $this->Form->textBox('GuestEmail');
                    }

                    $this->fireEvent('BeforeBodyField');

                    if ($guestCommenting) {
                        echo '<br />';
                        echo $this->Form->label('Message', 'Body');
                    }

                    echo $this->Form->bodyBox('Body', array('Table' => 'ArticleComment', 'tabindex' => 1));

                    echo '<div class="CommentOptions List Inline">';
                    $this->fireEvent('AfterBodyField');
                    echo '</div>';

                    echo "<div class=\"Buttons\">\n";
                    $this->fireEvent('BeforeFormButtons');
                    $cancelText = t('Home');
                    $cancelClass = 'Back';
                    if ($editing) {
                        $cancelText = t('Cancel');
                        $cancelClass = 'Cancel';
                    }

                    echo '<span class="' . $cancelClass . '">';
                    echo anchor($cancelText, '/');
                    echo '</span>';

                    $buttonOptions = array('class' => 'Button Primary CommentButton');
                    $buttonOptions['tabindex'] = 2;

                    if ((!$editing && $session->isValid()) || (!$editing && $guestCommenting)) {
                        echo ' ' . anchor(t('Preview'), '#', 'Button PreviewButton') . "\n";
                        echo ' ' . anchor(t('Edit'), '#', 'Button WriteButton Hidden') . "\n";
                    }

                    if ($session->isValid()) {
                        echo $this->Form->button($editing ? 'Save Comment' : 'Post Comment', $buttonOptions);
                    } else if ($guestCommenting) {
                        echo ' ' . $this->Form->button($editing ? 'Save Comment' : 'Comment As Guest', $buttonOptions);
                    } else {
                        $allowSigninPopup = c('Garden.SignIn.Popup');
                        $attributes = array('tabindex' => '-1');
                        if (!$allowSigninPopup) {
                            $attributes['target'] = '_parent';
                        }

                        $authenticationUrl = SignInUrl(Gdn::controller()->SelfUrl);
                        $cssClass = 'Button Primary Stash';
                        if ($allowSigninPopup) {
                            $cssClass .= ' SignInPopup';
                        }

                        echo anchor(t('Sign In'), $authenticationUrl, $cssClass, $attributes);
                    }

                    $this->fireEvent('AfterFormButtons');
                    echo "</div>\n";
                    echo $this->Form->close();
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>