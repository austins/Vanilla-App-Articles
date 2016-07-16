<?php defined('APPLICATION') or exit();

if (!function_exists('showArticleOptions')) {
    include($this->fetchViewLocation('helper_functions', 'article', 'articles'));
}

$articles = $this->data('Articles')->result();
$articleCount = $this->data('Articles')->numRows();

echo wrap(t('Article Posts'), 'h1', array('class' => 'H'));

if ($articleCount == 0) {
    echo wrap(t('No articles have been found.'), 'div');
} else {
    // Set up pager.
    $pagerOptions = array('Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>',
        'RecordCount' => $this->data('CountArticles'), 'CurrentRecords' => $this->data('Articles')->numRows());
    if ($this->data('_PagerUrl')) {
        $pagerOptions['Url'] = $this->data('_PagerUrl');
    }

    echo '<div class="PageControls Top">';
    PagerModule::write($pagerOptions);
    echo '</div>';

    echo '<ul class="DataList Articles">';
    foreach ($articles as $article):
        $articleUrl = articleUrl($article);
        $author = Gdn::userModel()->getID($article->InsertUserID);

        $category = $this->ArticleCategoryModel->getByID($article->ArticleCategoryID);

        $commentCountText = ($article->CountArticleComments == 0) ? '0 Comments'
            : plural($article->CountArticleComments, t('1 Comment'), t('%d Comments'));

        $cssClass = 'Item ItemArticle';

        if ($article->InsertUserID == Gdn::session()->UserID) {
            $cssClass .= ' Mine';
        }
        ?>
        <li id="Article_<?php echo $article->ArticleID; ?>" class="<?php echo $cssClass; ?>">
            <?php showArticleOptions($article); ?>

            <div class="ItemContent Article">
                <div class="Title"><?php echo anchor($article->Name, $articleUrl); ?></div>

                <div class="Meta Meta-Article">
                    <?php
                    // Article status tag.
                    $status = t('Draft');

                    switch ($article->Status) {
                        case ArticleModel::STATUS_PENDING:
                            $status = t('Pending Review');
                            break;
                        case ArticleModel::STATUS_PUBLISHED:
                            $status = t('Published');
                            break;
                    }

                    echo wrap($status, 'span', array('class' => 'Tag Tag-Status'));
                    ?>
                    <span
                        class="MItem MCount ArticleCategory"><?php echo anchor($category->Name,
                            articleCategoryUrl($category)); ?></span>
                    <span
                        class="MItem MCount ArticleDate"><?php echo Gdn_Format::date($article->DateInserted,
                            '%e %B %Y - %l:%M %p'); ?></span>
                    <span class="MItem MCount ArticleAuthor"><?php echo articleAuthorAnchor($author); ?></span>
                    <span
                        class="MItem MCount ArticleComments"><?php echo anchor($commentCountText,
                            $articleUrl . '#comments'); ?></span>
                </div>
            </div>
        </li>
        <?php
    endforeach;
    echo '</ul>';

    echo '<div class="PageControls Bottom">';
    PagerModule::write($pagerOptions);
    echo '</div>';
}
