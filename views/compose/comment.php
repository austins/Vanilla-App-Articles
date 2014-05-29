<?php
if (!defined('APPLICATION'))
    exit();

$Session = Gdn::Session();
$Editing = isset($this->Comment);

$this->EventArguments['FormCssClass'] = 'MessageForm CommentForm FormTitleWrapper';
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
                    $this->FireEvent('BeforeBodyField');

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

                    if (!$Editing && $Session->IsValid()) {
                        echo ' ' . Anchor(T('Preview'), '#', 'Button PreviewButton') . "\n";
                        echo ' ' . Anchor(T('Edit'), '#', 'Button WriteButton Hidden') . "\n";
                    }
                    if ($Session->IsValid())
                        echo $this->Form->Button($Editing ? 'Save Comment' : 'Post Comment', $ButtonOptions);
                    else {
                        $AllowSigninPopup = C('Garden.SignIn.Popup');
                        $Attributes = array('tabindex' => '-1');
                        if (!$AllowSigninPopup)
                            $Attributes['target'] = '_parent';

                        $AuthenticationUrl = SignInUrl($this->Data('ForeignUrl', '/'));
                        $CssClass = 'Button Primary Stash';
                        if ($AllowSigninPopup)
                            $CssClass .= ' SignInPopup';

                        echo Anchor(T('Comment As ...'), $AuthenticationUrl, $CssClass, $Attributes);
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