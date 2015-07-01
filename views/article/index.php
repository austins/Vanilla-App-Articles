<?php defined('APPLICATION') or exit();

if (!function_exists('ShowArticleOptions'))
    include($this->FetchViewLocation('helper_functions', 'article', 'articles'));

$Article = $this->Article;

$ArticleUrl = ArticleUrl($Article);
$Author = Gdn::UserModel()->GetID($Article->AttributionUserID);

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

    <?php
    $authorMeta = UserModel::getMeta($Author->UserID, 'Articles.%', 'Articles.');

    if (c('Articles.Articles.ShowAuthorInfo', false) && (count($authorMeta) > 0)) {
        $authorDisplayName = ($authorMeta['AuthorDisplayName'] !== '') ? $authorMeta['AuthorDisplayName'] : $Author->Name;
        ?>
        <footer>
            <div id="AuthorInfo" class="FormWrapper">
                <h2 class="H"><?php echo T('About the Author'); ?></h2>

                <div>
                    <?php
                    echo '<strong>' . $authorDisplayName;

                    if ($authorMeta['AuthorDisplayName'] !== '') {
                        echo ' (' . userAnchor($Author) . ')';
                    }

                    echo '</strong> &mdash; ' . $authorMeta['AuthorBio'];
                    ?>
                </div>
            </div>
        </footer>
    <?php
    }
    ?>
</article>

<?php $this->FireEvent('AfterArticle'); ?>

<section id="comments">
<?php
include $this->FetchViewLocation('comments', 'article', 'Articles');

ShowCommentForm();
?>
</section>
