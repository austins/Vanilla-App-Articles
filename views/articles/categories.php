<?php defined('APPLICATION') or exit(); ?>
<h1 class="H HomepageTitle"><?php echo $this->Data('Title'); ?></h1>
<ul class="DataList ArticleCategoryList">
    <?php
    $Categories = $this->Data('Categories');

    foreach ($Categories as $Category) :
        ?>
        <li id="ArticleCategory_<?php echo $Category->ArticleCategoryID; ?>"
            class="Item Item-ArticleCategory-<?php echo $Category->UrlCode; ?>">
            <div class="ItemContent ArticleCategory">
                <div class="TitleWrap"><?php echo Anchor($Category->Name, ArticleCategoryUrl($Category)); ?></div>
                <div class="ArticleCategoryDescription"><?php echo $Category->Description; ?></div>
                <div class="Meta">
                    <span class="MItem ArticleCount"><?php echo Plural(number_format($Category->CountArticles),
                            '%s article', '%s articles'); ?></span>
                    <span
                        class="MItem ArticleCommentCount"><?php echo Plural(number_format($Category->CountArticleComments),
                            '%s comment', '%s comments'); ?></span>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
