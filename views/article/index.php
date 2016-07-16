<?php defined('APPLICATION') or exit();

if (!function_exists('showArticleOptions')) {
    include($this->fetchViewLocation('helper_functions', 'article', 'articles'));
}

$article = $this->Article;

$articleUrl = articleUrl($article);
$author = Gdn::userModel()->getID($article->InsertUserID);

$category = $this->ArticleCategory;

if ($article->CountArticleComments == 0) {
    $commentCountText = 'Comments';
} else {
    $commentCountText = plural($article->CountArticleComments, t('1 Comment'), t('%d Comments'));
}
?>
<article id="Article_<?php echo $article->ArticleID; ?>" class="Article">
    <?php showArticleOptions($article); ?>

    <header>
        <h1 class="ArticleTitle"><?php echo $article->Name; ?></h1>

        <div class="Meta Meta-Article">
            <?php
            Gdn::controller()->fireEvent('BeforeArticleMeta');

            echo articleTag($article, 'Closed', 'Closed');

            Gdn::controller()->fireEvent('AfterArticleLabels');
            ?>
            <span
                class="MItem ArticleCategory"><?php echo anchor($category->Name,
                    articleCategoryUrl($category)); ?></span>
            <span
                class="MItem ArticleDate"><?php echo Gdn_Format::date($article->DateInserted,
                    '%e %B %Y - %l:%M %p'); ?></span>
            <span class="MItem ArticleAuthor"><?php echo articleAuthorAnchor($author); ?></span>
            <span class="MItem MCount ArticleComments"><?php echo anchor($commentCountText,
                    $articleUrl . '#comments'); ?></span>
        </div>
    </header>

    <div class="ArticleBody"><?php echo formatArticleBody($article->Body, $article->Format); ?></div>
</article>

<?php $this->fireEvent('AfterArticle'); ?>

<?php
$authorMeta = UserModel::getMeta($author->UserID, 'Articles.%', 'Articles.');

if (c('Articles.Articles.ShowAuthorInfo', false) && (count($authorMeta) > 0) && ($authorMeta['AuthorBio'] !== '')) :
    ?>
    <div id="AuthorInfo" class="FormWrapper FormWrapper-Condensed BoxAfterArticle">
        <div id="AuthorPhoto">
            <?php echo userPhoto($author, array('Size' => 'Medium')); ?>
        </div>

        <div id="AboutTheAuthor">
            <?php echo t('About the Author'); ?>
        </div>

        <h2 class="H"><?php
            if ($authorMeta['AuthorDisplayName'] === '') {
                echo userAnchor($author);
            } else {
                echo $authorMeta['AuthorDisplayName'] . ' (' . userAnchor($author) . ')';
            }
            ?></h2>

        <div id="AuthorBio">
            <?php echo $authorMeta['AuthorBio']; ?>
        </div>
    </div>
    <?php
endif;

if (c('Articles.Articles.ShowSimilarArticles')) {
    $similarArticles = $this->data('SimilarArticles');

    if ($similarArticles->numRows() > 0) {
        echo '<div id="SimilarArticles">
            <h2 class="H">' . t('You may be interested in...') . '</h2>';

        echo '<ul class="DataList">';
        foreach ($similarArticles as $similarArticle) {
            $similarArticleCategory = $this->ArticleCategoryModel->getByID($similarArticle->ArticleCategoryID);
            $similarArticleAuthor = Gdn::userModel()->getID($similarArticle->InsertUserID);

            echo '<li class="SimilarArticle">
                ' . anchor($similarArticle->Name, articleUrl($similarArticle)) . '

                <div class="Meta Meta-Article">
                    <span class="MItem ArticleCategory">' . anchor($similarArticleCategory->Name,
                    articleCategoryUrl($similarArticleCategory->UrlCode)) . '</span>
                    <span class="MItem ArticleDate">' . Gdn_Format::date($similarArticle->DateInserted,
                    '%e %B %Y - %l:%M %p') . '</span>
                    <span class="MItem ArticleAuthor">' . articleAuthorAnchor($similarArticleAuthor) . '</span>
                </div>
            </li>';
        }
        echo '</ul>';

        echo '</div>';
    }
}

include $this->fetchViewLocation('comments', 'article', 'Articles');
?>
