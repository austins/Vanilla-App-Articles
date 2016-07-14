<?php defined('APPLICATION') or exit();

if (!function_exists('showArticleOptions')) {
    include($this->fetchViewLocation('helper_functions', 'article', 'articles'));
}

$Articles = $this->data('Articles');

$Category = isset($this->ArticleCategory->ArticleCategoryID) ? $this->ArticleCategory : false;

if ($Category) {
    echo Wrap($Category->Name, 'h1', array('class' => 'H'));
}

if (count($Articles) == 0) {
    if ($Category) {
        echo Wrap(T('No articles have been published in this category yet.'), 'div', array('class' => 'Empty'));
    } else {
        echo Wrap(T('No articles have been published yet.'), 'div', array('class' => 'Empty'));
    }
} else {
    foreach ($Articles as $Article):
        $ArticleUrl = articleUrl($Article);
        $Author = Gdn::userModel()->getID($Article->InsertUserID);

        $CommentCountText = ($Article->CountArticleComments == 0) ? 'Comments' :
            Plural($Article->CountArticleComments, T('1 Comment'), T('%d Comments'));

        $Thumbnail = $this->ArticleMediaModel->getThumbnailByArticleID($Article->ArticleID);
        ?>
        <article id="Article_<?php echo $Article->ArticleID; ?>" class="Article ClearFix">
            <?php showArticleOptions($Article); ?>

            <?php
            if (is_object($Thumbnail) && ($Article->Excerpt != "")) {
                $ThumbnailPath = '/uploads' . $Thumbnail->Path;

                echo '<div class="ArticleThumbnail">';
                echo Anchor(Img($ThumbnailPath, array('title' => $Article->Name)), $ArticleUrl);
                echo '</div>';
            }
            ?>
            <div class="ArticleInner">
                <header>
                    <h2 class="ArticleTitle"><?php echo Anchor($Article->Name, $ArticleUrl); ?></h2>

                    <div class="Meta Meta-Article">
                        <?php
                        Gdn::Controller()->fireEvent('BeforeArticleMeta');

                        echo articleTag($Article, 'Closed', 'Closed');

                        Gdn::Controller()->fireEvent('AfterArticleLabels');
                        ?>
                        <span
                            class="MItem ArticleCategory"><?php echo Anchor($Article->ArticleCategoryName,
                                articleCategoryUrl($Article->ArticleCategoryUrlCode));
                            ?></span>
                        <span class="MItem ArticleDate"><?php echo Gdn_Format::date($Article->DateInserted, '%e %B %Y - %l:%M %p'); ?></span>
                        <span class="MItem ArticleAuthor"><?php echo articleAuthorAnchor($Author); ?></span>
                        <span class="MItem MCount ArticleComments"><?php echo Anchor($CommentCountText, $ArticleUrl . '#comments'); ?></span>
                    </div>
                </header>

                <div class="ArticleBody">
                    <?php
                    $ArticleBody = ($Article->Excerpt != "") ? $Article->Excerpt : $Article->Body;
                    echo formatArticleBody($ArticleBody, $Article->Format);
                    ?>
                </div>
            </div>
        </article>
    <?php
    endforeach;

    // Set up pager.
    $PagerOptions = array(
        'Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>',
        'RecordCount' => $this->data('CountArticles'),
        'CurrentRecords' => count($Articles)
    );

    if ($this->data('_PagerUrl')) {
        $PagerOptions['Url'] = $this->data('_PagerUrl');
    }

    echo '<div class="PageControls Bottom">';
    PagerModule::Write($PagerOptions);
    echo '</div>';
}
