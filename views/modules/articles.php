<?php defined('APPLICATION') or exit();

$Articles = $this->Data->Result();
?>
<div class="Box BoxArticles">
    <h4><?php echo T('Recent Articles'); ?></h4>
    <ul class="PanelInfo PanelArticles DataList">
        <?php
        $ArticleCategoryModel = new ArticleCategoryModel();
        foreach ($Articles as $Article) {
            $Category = $ArticleCategoryModel->GetByID($Article->ArticleCategoryID);
            ?>
            <li id="Article_<?php echo $Article->ArticleID; ?>">
                <div class="Title"><?php echo Anchor(Gdn_Format::Text($Article->Name, false), ArticleUrl($Article),
                        'ArticleLink'); ?></div>

                <div class="Meta">
                    <span class="MItem"><?php echo Gdn_Format::Date($Article->DateInserted,
                                'html') . Anchor($Category->Name, ArticleCategoryUrl($Category)); ?></span>
                </div>
            </li>
        <?php } ?>
    </ul>
</div>