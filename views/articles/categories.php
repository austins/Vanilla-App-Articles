<?php defined('APPLICATION') or exit(); ?>
<h1 class="H HomepageTitle"><?php echo $this->data('Title'); ?></h1>
<ul class="DataList ArticleCategoryList">
    <?php
    $categories = $this->data('ArticleCategories');

    foreach ($categories as $category) :
        ?>
        <li id="ArticleCategory_<?php echo $category->ArticleCategoryID; ?>"
            class="Item Item-ArticleCategory-<?php echo $category->UrlCode; ?>">
            <div class="ItemContent ArticleCategory">
                <div class="TitleWrap"><?php echo anchor($category->Name, articleCategoryUrl($category)); ?></div>
                <div class="ArticleCategoryDescription"><?php echo $category->Description; ?></div>
                <div class="Meta">
                    <span class="MItem ArticleCount"><?php echo plural(number_format($category->CountArticles),
                            '%s article', '%s articles'); ?></span>
                    <span
                        class="MItem ArticleCommentCount"><?php echo plural(number_format($category->CountArticleComments),
                            '%s comment', '%s comments'); ?></span>
                </div>
            </div>
        </li>
    <?php endforeach; ?>
</ul>
