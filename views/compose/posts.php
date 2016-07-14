<?php defined('APPLICATION') or exit();

if (!function_exists('showArticleOptions'))
    include($this->fetchViewLocation('helper_functions', 'article', 'articles'));

$Articles = $this->data('Articles')->result();
$ArticleCount = $this->data('Articles')->numRows();

echo Wrap(T('Article Posts'), 'h1', array('class' => 'H'));

if (count($Articles) == 0)
    echo Wrap(T('No articles have been found.'), 'div');
else {
    // Set up pager.
    $PagerOptions = array('Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>',
        'RecordCount' => $this->data('CountArticles'), 'CurrentRecords' => $this->data('Articles')->numRows());
    if ($this->data('_PagerUrl'))
        $PagerOptions['Url'] = $this->data('_PagerUrl');

    echo '<div class="PageControls Top">';
    PagerModule::Write($PagerOptions);
    echo '</div>';

    echo '<ul class="DataList Articles">';
    foreach ($Articles as $Article):
        $ArticleUrl = articleUrl($Article);
        $Author = Gdn::userModel()->getID($Article->InsertUserID);

        $Category = $this->ArticleCategoryModel->getByID($Article->ArticleCategoryID);

        $CommentCountText = ($Article->CountArticleComments == 0) ? '0 Comments'
            : Plural($Article->CountArticleComments, T('1 Comment'), T('%d Comments'));

        $CssClass = 'Item ItemArticle';

        if ($Article->InsertUserID == Gdn::session()->UserID)
            $CssClass .= ' Mine';
        ?>
        <li id="Article_<?php echo $Article->ArticleID; ?>" class="<?php echo $CssClass; ?>">
            <?php showArticleOptions($Article); ?>

            <div class="ItemContent Article">
                <div class="Title"><?php echo Anchor($Article->Name, $ArticleUrl); ?></div>

                <div class="Meta Meta-Article">
                    <?php
                    // Article status tag.
                    $Status = T('Draft');

                    switch ($Article->Status) {
                        case ArticleModel::STATUS_PENDING:
                            $Status = T('Pending Review');
                            break;
                        case ArticleModel::STATUS_PUBLISHED:
                            $Status = T('Published');
                            break;
                    }

                    echo Wrap($Status, 'span', array('class' => 'Tag Tag-Status'));
                    ?>
                    <span
                        class="MItem MCount ArticleCategory"><?php echo Anchor($Category->Name,
                            articleCategoryUrl($Category)); ?></span>
                    <span
                        class="MItem MCount ArticleDate"><?php echo Gdn_Format::date($Article->DateInserted,
                            '%e %B %Y - %l:%M %p'); ?></span>
                    <span class="MItem MCount ArticleAuthor"><?php echo articleAuthorAnchor($Author); ?></span>
                    <span
                        class="MItem MCount ArticleComments"><?php echo Anchor($CommentCountText,
                            $ArticleUrl . '#comments'); ?></span>
                </div>
            </div>
        </li>
    <?php
    endforeach;
    echo '</ul>';

    echo '<div class="PageControls Bottom">';
    PagerModule::Write($PagerOptions);
    echo '</div>';
}
