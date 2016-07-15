<?php defined('APPLICATION') or exit();

$Articles = $this->Data->result();
?>
<div class="Box BoxArticles">
    <h4><?php echo t('Recent Articles'); ?></h4>
    <ul class="PanelInfo PanelArticles DataList">
        <?php
        foreach ($Articles as $Article) :
            ?>
            <li id="Article_<?php echo $Article->ArticleID; ?>">
                <div class="Title"><?php echo anchor(Gdn_Format::Text($Article->Name, false), articleUrl($Article),
                        'ArticleLink'); ?></div>

                <div class="Meta">
                    <span class="MItem"><?php echo Gdn_Format::date($Article->DateInserted,
                                'html') . anchor($Article->ArticleCategoryName,
                                articleCategoryUrl($Article->ArticleCategoryUrlCode)); ?></span>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>