<?php defined('APPLICATION') or exit();

if (!function_exists('ShowArticleOptions'))
    include($this->FetchViewLocation('helper_functions', 'article', 'articles'));

$Article = $this->Article;

$ArticleUrl = ArticleUrl($Article);
$Author = Gdn::UserModel()->GetID($Article->InsertUserID);

$Category = $this->ArticleCategory;

if ($Article->CountArticleComments == 0)
    $CommentCountText = 'Comments';
else
    $CommentCountText = Plural($Article->CountArticleComments, T('1 Comment'), T('%d Comments'));
?>
<article id="Article_<?php echo $Article->ArticleID; ?>" class="Article">
    <?php ShowArticleOptions($Article); ?>

    <header>
        <h1 class="ArticleTitle"><?php echo $Article->Name; ?></h1>

        <div class="Meta Meta-Article">
            <?php
            Gdn::Controller()->FireEvent('BeforeArticleMeta');

            echo ArticleTag($Article, 'Closed', 'Closed');

            Gdn::Controller()->FireEvent('AfterArticleLabels');
            ?>
            <span
                class="MItem ArticleCategory"><?php echo Anchor($Category->Name,
                    ArticleCategoryUrl($Category)); ?></span>
                    <span
                        class="MItem ArticleDate"><?php echo Gdn_Format::Date($Article->DateInserted,
                            '%e %B %Y - %l:%M %p'); ?></span>
            <span class="MItem ArticleAuthor"><?php echo ArticleAuthorAnchor($Author); ?></span>
            <span class="MItem MCount ArticleComments"><?php echo Anchor($CommentCountText,
                    $ArticleUrl . '#comments'); ?></span>
        </div>
    </header>

    <div class="ArticleBody"><?php echo FormatArticleBody($Article->Body, $Article->Format); ?></div>
</article>

<?php $this->FireEvent('AfterArticle'); ?>

<?php
$authorMeta = UserModel::getMeta($Author->UserID, 'Articles.%', 'Articles.');

if (c('Articles.Articles.ShowAuthorInfo', false) && (count($authorMeta) > 0) && ($authorMeta['AuthorBio'] !== '')) :
    ?>
    <div id="AuthorInfo" class="FormWrapper FormWrapper-Condensed BoxAfterArticle">
        <div id="AuthorPhoto">
            <?php echo userPhoto($Author, array('Size' => 'Medium')); ?>
        </div>

        <div id="AboutTheAuthor">
            <?php echo T('About the Author'); ?>
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

if (C('Articles.Articles.ShowSimilarArticles')) {
    $SimilarArticles = $this->Data('SimilarArticles');

    if ($SimilarArticles->NumRows() > 0) {
        echo '<div id="SimilarArticles">
            <h2 class="H">' . T('You may be interested in...') . '</h2>';

        echo '<ul class="DataList">';
        foreach ($SimilarArticles as $SimilarArticle) {
            $SimilarArticleCategory = $this->ArticleCategoryModel->GetByID($SimilarArticle->ArticleCategoryID);
            $SimilarArticleAuthor = Gdn::UserModel()->GetID($SimilarArticle->InsertUserID);

            echo '<li class="SimilarArticle">
                ' . Anchor($SimilarArticle->Name, ArticleUrl($SimilarArticle)) . '

                <div class="Meta Meta-Article">
                    <span class="MItem ArticleCategory">' . Anchor($SimilarArticleCategory->Name, ArticleCategoryUrl($SimilarArticleCategory->UrlCode)) . '</span>
                    <span class="MItem ArticleDate">' . Gdn_Format::Date($SimilarArticle->DateInserted, '%e %B %Y - %l:%M %p') . '</span>
                    <span class="MItem ArticleAuthor">' . ArticleAuthorAnchor($SimilarArticleAuthor) . '</span>
                </div>
            </li>';
        }
        echo '</ul>';

        echo '</div>';
    }
}

include $this->FetchViewLocation('comments', 'article', 'Articles');
?>
