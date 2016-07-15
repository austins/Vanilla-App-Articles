<?php defined('APPLICATION') or exit();

$session = Gdn::session();
?>
<div class="MessageForm EditCommentForm FormTitleWrapper">
    <div class="Form-BodyWrap">
        <div class="Form-Body">
            <div class="FormWrapper FormWrapper-Condensed">
                <?php
                echo $this->Form->Open();
                echo $this->Form->Errors();

                echo $this->Form->BodyBox('Body', array('Table' => 'ArticleComment', 'tabindex' => 1));

                echo "<div class=\"Buttons\">\n";
                echo wrap(anchor(t('Cancel'), '/'), 'span class="Cancel"');
                echo $this->Form->Button('Save Comment',
                    array('class' => 'Button Primary CommentButton', 'tabindex' => 2));
                echo "</div>\n";

                echo $this->Form->Close();
                ?>
            </div>
        </div>
    </div>
</div>
