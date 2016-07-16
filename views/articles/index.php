<?php defined('APPLICATION') or exit();

if (!function_exists('showArticleOptions')) {
    include($this->fetchViewLocation('helper_functions', 'article', 'articles'));
}

$articles = $this->data('Articles');

$category = isset($this->ArticleCategory->ArticleCategoryID) ? $this->ArticleCategory : false;

if ($category) {
    echo wrap($category->Name, 'h1', array('class' => 'H'));
}

if (count($articles) == 0) {
    if ($category) {
        echo wrap(t('No articles have been published in this category yet.'), 'div', array('class' => 'Empty'));
    } else {
        echo wrap(t('No articles have been published yet.'), 'div', array('class' => 'Empty'));
    }
} else {
    foreach ($articles as $article):
        $articleUrl = articleUrl($article);
        $author = Gdn::userModel()->getID($article->InsertUserID);

        $commentCountText = ($article->CountArticleComments == 0) ? 'Comments' :
            plural($article->CountArticleComments, t('1 Comment'), t('%d Comments'));

        $thumbnail = $this->ArticleMediaModel->getThumbnailByArticleID($article->ArticleID);
        ?>
        <article id="Article_<?php echo $article->ArticleID; ?>" class="Article ClearFix">
            <?php showArticleOptions($article); ?>

            <?php
            if (is_object($thumbnail) && ($article->Excerpt != "")) {
                $thumbnailPath = '/uploads' . $thumbnail->Path;

                echo '<div class="ArticleThumbnail">';
                echo anchor(img($thumbnailPath, array('title' => $article->Name)), $articleUrl);
                echo '</div>';
            }
            ?>
            <div class="ArticleInner">
                <header>
                    <h2 class="ArticleTitle"><?php echo anchor($article->Name, $articleUrl); ?></h2>

                    <div class="Meta Meta-Article">
                        <?php
                        Gdn::controller()->fireEvent('BeforeArticleMeta');

                        echo articleTag($article, 'Closed', 'Closed');

                        Gdn::controller()->fireEvent('AfterArticleLabels');
                        ?>
                        <span
                            class="MItem ArticleCategory"><?php echo anchor($article->ArticleCategoryName,
                                articleCategoryUrl($article->ArticleCategoryUrlCode));
                            ?></span>
                        <span class="MItem ArticleDate"><?php echo Gdn_Format::date($article->DateInserted,
                                '%e %B %Y - %l:%M %p'); ?></span>
                        <span class="MItem ArticleAuthor"><?php echo articleAuthorAnchor($author); ?></span>
                        <span class="MItem MCount ArticleComments"><?php echo anchor($commentCountText,
                                $articleUrl . '#comments'); ?></span>
                    </div>
                </header>

                <div class="ArticleBody">
                    <?php
                    $articleBody = ($article->Excerpt != "") ? $article->Excerpt : $article->Body;
                    echo formatArticleBody($articleBody, $article->Format);
                    ?>
                </div>
            </div>
        </article>
        <?php
    endforeach;

    // Set up pager.
    $pagerOptions = array(
        'Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>',
        'RecordCount' => $this->data('CountArticles'),
        'CurrentRecords' => count($articles)
    );

    if ($this->data('_PagerUrl')) {
        $pagerOptions['Url'] = $this->data('_PagerUrl');
    }

    echo '<div class="PageControls Bottom">';
    PagerModule::write($pagerOptions);
    echo '</div>';
}
