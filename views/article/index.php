<?php if (!defined('APPLICATION'))
    exit();

if (!function_exists('ShowArticleOptions'))
    include($this->FetchViewLocation('helper_functions', 'article', 'articles'));

$Article = $this->Data('Article');

$ArticleUrl = ArticleUrl($Article);
$Author = Gdn::UserModel()->GetID($Article->AuthorUserID);

$Category = $this->Data('Category');

if ($Article->CountComments == 0)
    $CommentCount = 'Comments';
else
    $CommentCount = Plural($Article->CountComments, T('1 Comment'), T('%d Comments'));
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
                class="MItem MCount ArticleCategory"><?php echo Anchor($Category->Name,
                    ArticleCategoryUrl($Category)); ?></span>
                    <span
                        class="MItem MCount ArticleDate"><?php echo Gdn_Format::Date($Article->DateInserted,
                            '%e %B %Y - %l:%M %p'); ?></span>
            <span class="MItem MCount ArticleAuthor"><?php echo UserAnchor($Author); ?></span>
            <span class="MItem MCount ArticleComments"><?php echo Anchor($CommentCount,
                    $ArticleUrl . '#comments'); ?></span>
        </div>
    </header>

    <div class="ArticleBody"><?php echo FormatArticleBody($Article->Body, $Article->Format); ?></div>
</article>
