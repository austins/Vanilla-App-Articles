<?php defined('APPLICATION') or exit();

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
                    writeComment($comment, $currentOffset);
                }
                ?>
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