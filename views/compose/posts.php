<?php
if (!defined('APPLICATION'))
    exit();

if (!function_exists('ShowArticlesDashboardMenu'))
    include($this->FetchViewLocation('helper_functions', 'compose', 'articles'));

ShowArticlesDashboardMenu($this->RequestMethod);

if (!function_exists('ShowArticleOptions'))
    include($this->FetchViewLocation('helper_functions', 'article', 'articles'));

$Articles = $this->Data('Articles')->Result();
$ArticleCount = $this->Data('Articles')->NumRows();

if (count($Articles) == 0)
    echo Wrap(T('No articles have been found.'), 'div');
else {
    // Set up pager.
    $PagerOptions = array('Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>',
        'RecordCount' => $this->Data('CountArticles'), 'CurrentRecords' => $this->Data('Articles')->NumRows());
    if ($this->Data('_PagerUrl'))
        $PagerOptions['Url'] = $this->Data('_PagerUrl');

    echo '<div class="PageControls Top">';
    PagerModule::Write($PagerOptions);
    echo '</div>';

    echo '<ul class="DataList Articles">';
    foreach ($Articles as $Article):
        $ArticleUrl = ArticleUrl($Article);
        $Author = Gdn::UserModel()->GetID($Article->AttributionUserID);

        $Category = $this->ArticleCategoryModel->GetByID($Article->CategoryID);

        $CommentCount = ($Article->CountComments == 0) ? '0 Comments'
            : Plural($Article->CountComments, T('1 Comment'), T('%d Comments'));

        $CssClass = 'Item ItemArticle';

        if ($Article->AttributionUserID == Gdn::Session()->UserID)
            $CssClass .= ' Mine';
        ?>
        <li id="Article_<?php echo $Article->ArticleID; ?>" class="<?php echo $CssClass; ?>">
            <?php ShowArticleOptions($Article); ?>

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
                            ArticleCategoryUrl($Category)); ?></span>
                    <span
                        class="MItem MCount ArticleDate"><?php echo Gdn_Format::Date($Article->DateInserted,
                            '%e %B %Y - %l:%M %p'); ?></span>
                    <span class="MItem MCount ArticleAuthor"><?php echo UserAnchor($Author); ?></span>
                    <span
                        class="MItem MCount ArticleComments"><?php echo Anchor($CommentCount,
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
