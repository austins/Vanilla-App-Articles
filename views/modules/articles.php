<?php defined('APPLICATION') or exit();

$articles = $this->Data->result();
?>
<div class="Box BoxArticles">
    <h4><?php echo t('Recent Articles'); ?></h4>
    <ul class="PanelInfo PanelArticles DataList">
        <?php
        foreach ($articles as $article) :
            ?>
            <li id="Article_<?php echo $article->ArticleID; ?>">
                <div class="Title"><?php echo anchor(Gdn_Format::text($article->Name, false), articleUrl($article),
                        'ArticleLink'); ?></div>

                <div class="Meta">
                    <span class="MItem"><?php echo Gdn_Format::date($article->DateInserted,
                                'html') . anchor($article->ArticleCategoryName,
                                articleCategoryUrl($article->ArticleCategoryUrlCode)); ?></span>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>