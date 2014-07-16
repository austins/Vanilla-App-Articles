<?php
if(!defined('APPLICATION'))
  exit();

if(!function_exists('ShowArticleOptions')) {
  include($this->FetchViewLocation('helper_functions', 'article', 'articles'));
}

$Articles = $this->Data('Articles');

$Category = isset($this->Category->CategoryID) ? $this->Category : false;

if($Category) {
  echo Wrap($Category->Name, 'h1', array('class' => 'H'));
}

if(count($Articles) == 0) {
  if($Category) {
    echo Wrap(T('No articles have been published in this category.'), 'div');
  }
  else {
    echo Wrap(T('No articles have been published yet.'), 'div');
  }
}
else {
  foreach($Articles as $Article):
    $ArticleUrl = ArticleUrl($Article);
    $Author = Gdn::UserModel()->GetID($Article->AuthorUserID);

    $ArticleCategory = $this->ArticleCategoryModel->GetByID($Article->CategoryID);

    $CommentCount = ($Article->CountComments == 0) ? 'Comments' : Plural($Article->CountComments, T('1 Comment'), T('%d Comments'));
    ?>
    <article id="Article_<?php echo $Article->ArticleID; ?>" class="Article">
    <?php ShowArticleOptions($Article); ?>

      <header>
        <h2 class="ArticleTitle"><?php echo Anchor($Article->Name, $ArticleUrl); ?></h2>

        <div class="Meta Meta-Article">
          <?php
          Gdn::Controller()->FireEvent('BeforeArticleMeta');

          echo ArticleTag($Article, 'Closed', 'Closed');

          Gdn::Controller()->FireEvent('AfterArticleLabels');
          ?>
          <span
            class="MItem MCount ArticleCategory"><?php echo Anchor($ArticleCategory->Name, ArticleCategoryUrl($ArticleCategory));
          ?></span>
          <span
            class="MItem MCount ArticleDate"><?php echo Gdn_Format::Date($Article->DateInserted, '%e %B %Y - %l:%M %p');
          ?></span>
          <span class="MItem MCount ArticleAuthor"><?php echo UserAnchor($Author); ?></span>
          <span class="MItem MCount ArticleComments"><?php echo Anchor($CommentCount, $ArticleUrl . '#comments');
        ?></span>
        </div>
      </header>

      <div class="ArticleBody">
        <?php
        $ArticleBody = ($Article->Excerpt != "") ? $Article->Excerpt : $Article->Body;
        echo FormatArticleBody($ArticleBody, $Article->Format);
        ?>
      </div>
    </article>
    <?php
  endforeach;

  // Set up pager.
  $PagerOptions = array(
      'Wrapper' => '<span class="PagerNub">&#160;</span><div %1$s>%2$s</div>',
      'RecordCount' => $this->Data('CountArticles'),
      'CurrentRecords' => count($Articles)
  );
  
  if($this->Data('_PagerUrl')) {
    $PagerOptions['Url'] = $this->Data('_PagerUrl');
  }

  echo '<div class="PageControls Bottom">';
  PagerModule::Write($PagerOptions);
  echo '</div>';
}
