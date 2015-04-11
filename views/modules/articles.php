<?php defined('APPLICATION') or exit();

$Articles = $this->Data->Result();
?>
<div class="Box BoxArticles">
    <h4><?php echo T('Recent Articles'); ?></h4>
    <ul class="PanelInfo PanelArticles DataList">
        <?php
        foreach ($Articles as $Article) :
            ?>
            <li id="Article_<?php echo $Article->ArticleID; ?>">
                <div class="Title"><?php echo Anchor(Gdn_Format::Text($Article->Name, false), ArticleUrl($Article),
                        'ArticleLink'); ?></div>

                <div class="Meta">
                    <span class="MItem"><?php echo Gdn_Format::Date($Article->DateInserted,
                                'html') . Anchor($Article->ArticleCategoryName,
                                ArticleCategoryUrl($Article->ArticleCategoryUrlCode)); ?></span>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>