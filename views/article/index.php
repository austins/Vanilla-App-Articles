<?php defined('APPLICATION') or exit();

if (!function_exists('showArticleOptions'))
    include($this->fetchViewLocation('helper_functions', 'article', 'articles'));

$Article = $this->Article;

$ArticleUrl = articleUrl($Article);
$Author = Gdn::userModel()->getID($Article->InsertUserID);

$Category = $this->ArticleCategory;

if ($Article->CountArticleComments == 0)
    $CommentCountText = 'Comments';
else
    $CommentCountText = Plural($Article->CountArticleComments, t('1 Comment'), t('%d Comments'));
?>
<article id="Article_<?php echo $Article->ArticleID; ?>" class="Article">
    <?php showArticleOptions($Article); ?>

    <header>
        <h1 class="ArticleTitle"><?php echo $Article->Name; ?></h1>

        <div class="Meta Meta-Article">
            <?php
            Gdn::controller()->fireEvent('BeforeArticleMeta');

            echo articleTag($Article, 'Closed', 'Closed');

            Gdn::controller()->fireEvent('AfterArticleLabels');
            ?>
            <span
                class="MItem ArticleCategory"><?php echo anchor($Category->Name,
                    articleCategoryUrl($Category)); ?></span>
                    <span
                        class="MItem ArticleDate"><?php echo Gdn_Format::date($Article->DateInserted,
                            '%e %B %Y - %l:%M %p'); ?></span>
            <span class="MItem ArticleAuthor"><?php echo articleAuthorAnchor($Author); ?></span>
            <span class="MItem MCount ArticleComments"><?php echo anchor($CommentCountText,
                    $ArticleUrl . '#comments'); ?></span>
        </div>
    </header>

    <div class="ArticleBody"><?php echo formatArticleBody($Article->Body, $Article->Format); ?></div>
</article>

<?php $this->fireEvent('AfterArticle'); ?>

<?php
$authorMeta = UserModel::getMeta($Author->UserID, 'Articles.%', 'Articles.');

if (c('Articles.Articles.ShowAuthorInfo', false) && (count($authorMeta) > 0) && ($authorMeta['AuthorBio'] !== '')) :
    ?>
    <div id="AuthorInfo" class="FormWrapper FormWrapper-Condensed BoxAfterArticle">
        <div id="AuthorPhoto">
            <?php echo userPhoto($Author, array('Size' => 'Medium')); ?>
        </div>

        <div id="AboutTheAuthor">
            <?php echo t('About the Author'); ?>
        </div>

        <h2 class="H"><?php
            if ($authorMeta['AuthorDisplayName'] === '') {
                echo userAnchor($Author);
            } else {
                echo $authorMeta['AuthorDisplayName'] . ' (' . userAnchor($Author) . ')';
            }
            ?></h2>

        <div id="AuthorBio">
            <?php echo $authorMeta['AuthorBio']; ?>
        </div>
    </div>
<?php
endif;

if (c('Articles.Articles.ShowSimilarArticles')) {
    $SimilarArticles = $this->data('SimilarArticles');

    if ($SimilarArticles->numRows() > 0) {
        echo '<div id="SimilarArticles">
            <h2 class="H">' . t('You may be interested in...') . '</h2>';

        echo '<ul class="DataList">';
        foreach ($SimilarArticles as $SimilarArticle) {
            $SimilarArticleCategory = $this->ArticleCategoryModel->getByID($SimilarArticle->ArticleCategoryID);
            $SimilarArticleAuthor = Gdn::userModel()->getID($SimilarArticle->InsertUserID);

            echo '<li class="SimilarArticle">
                ' . anchor($SimilarArticle->Name, articleUrl($SimilarArticle)) . '

                <div class="Meta Meta-Article">
                    <span class="MItem ArticleCategory">' . anchor($SimilarArticleCategory->Name, articleCategoryUrl($SimilarArticleCategory->UrlCode)) . '</span>
                    <span class="MItem ArticleDate">' . Gdn_Format::date($SimilarArticle->DateInserted, '%e %B %Y - %l:%M %p') . '</span>
                    <span class="MItem ArticleAuthor">' . articleAuthorAnchor($SimilarArticleAuthor) . '</span>
                </div>
            </li>';
        }
        echo '</ul>';

        echo '</div>';
    }
}

include $this->fetchViewLocation('comments', 'article', 'Articles');
?>
