<?php if(!defined('APPLICATION')) exit();

if(!function_exists('ShowArticleOptions'))
    include($this->FetchViewLocation('helper_functions', 'article', 'articles'));

$Articles = $this->Data('Articles');

$Category = isset($this->Data('Category')->CategoryID) ? $this->Data('Category') : NULL;

if($Category)
    echo Wrap($Category->Name, 'h1', array('class' => 'H'));

if(count($Articles) == 0)
    echo Wrap(T('No articles have been published in this category.'), 'div');

foreach($Articles as $Article):
    $ArticleUrl = ArticleUrl($Article);
    $Author = Gdn::UserModel()->GetID($Article->InsertUserID);

    $Category = $this->ArticleCategoryModel->GetByID($Article->CategoryID);

    if($Article->CountComments == 0)
        $CommentCount = 'Comments';
    else
        $CommentCount = Plural($Article->CountComments, T('1 Comment'), T('%d Comments'));
?>
    <article id="Article_<?php echo $Article->ArticleID; ?>" class="Article">
        <?php ShowArticleOptions($Article); ?>

        <header>
            <h2 class="ArticleTitle"><?php echo Anchor($Article->Name, $ArticleUrl); ?></h2>

            <div class="ArticleMeta">
                <span class="ArticleCategory"><?php echo Anchor($Category->Name, ArticleCategoryUrl($Category)); ?></span>
                <span class="ArticleDate"><?php echo Gdn_Format::Date($Article->DateInserted, '%e %B %Y - %l:%M %p'); ?></span>
                <span class="ArticleAuthor"><?php echo UserAnchor($Author); ?></span>
                <span class="ArticleComments"><?php echo Anchor($CommentCount, $ArticleUrl . '#comments'); ?></span>
            </div>
        </header>

        <div class="ArticleBody">
            <?php
            $ArticleBody = ($Article->Excerpt != "") ? $Article->Excerpt : $Article->Body;
            echo FormatArticleBody($ArticleBody, $Article->Format);
            ?>
        </div>
    </article>
<?php endforeach; ?>