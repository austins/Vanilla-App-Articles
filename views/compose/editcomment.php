<?php defined('APPLICATION') or exit();

$session = Gdn::session();
?>
<div class="MessageForm EditCommentForm FormTitleWrapper">
    <div class="Form-BodyWrap">
        <div class="Form-Body">
            <div class="FormWrapper FormWrapper-Condensed">
                <?php
                echo $this->Form->open();
                echo $this->Form->errors();

                echo $this->Form->bodyBox('Body', array('Table' => 'ArticleComment', 'tabindex' => 1));

                echo "<div class=\"Buttons\">\n";
                echo wrap(anchor(t('Cancel'), '/'), 'span class="Cancel"') . ' ';
                echo $this->Form->button('Save Comment',
                    array('class' => 'Button Primary CommentButton', 'tabindex' => 2));
                echo "</div>\n";

                echo $this->Form->close();
                ?>
            </div>
        </div>
    </div>
</div>
