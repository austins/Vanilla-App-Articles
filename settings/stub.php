<?php
if (!defined('APPLICATION'))
    exit();
/**
 * Articles stub content for first installation.
 *
 * Called by ArticlesHooks::Setup() to insert stub content upon enabling app.
 *
 * @package Articles
 */

// Only do this once, ever.
if (Gdn::Config('Articles.Version') !== false)
    return;

// Prep content meta data.
$SystemUserID = Gdn::UserModel()->GetSystemUserID();
$Now = Gdn_Format::ToDateTime();

// Insert first article category.
$ArticleCategoryModel = new ArticleCategoryModel();
if ($ArticleCategoryModel->GetCount() == 0) {
    $FirstCategoryID = $ArticleCategoryModel->Save(array(
        'Name' => 'Uncategorized',
        'UrlCode' => 'uncategorized',
        'Description' => 'Uncategorized articles.',
        'DateInserted' => $Now,
        'InsertUserID' => $SystemUserID,
        'LastDateInserted' => $Now,
        'CountArticles' => 1,
        'LastArticleID' => 1
    ));
}

// Insert first article.
$ArticleModel = new ArticleModel();

if ($ArticleModel->GetCount() == 0) {
    $FirstArticleName = 'My First Article!';
    $FirstArticleBody = "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
        . " Duis luctus turpis nec lacus convallis consectetur. Maecenas ligula"
        . " enim, laoreet ac nisl eu, lacinia bibendum neque. Ut orci lacus,"
        . " mollis in aliquet eu, accumsan in enim. Cras vitae pharetra orci."
        . " Morbi ante ante, dapibus a purus et, volutpat posuere nisi. Sed"
        . " faucibus, lectus a sodales fermentum, diam risus malesuada magna,"
        . " vel commodo enim eros non metus. Donec pulvinar tempor volutpat."
        . " Aenean ut dolor eu purus egestas cursus. In aliquet magna"
        . " arcu, sed fermentum ipsum condimentum eget.";

    $ArticleModel->Save(array(
        'CategoryID' => 1,
        'Name' => $FirstArticleName,
        'UrlCode' => Gdn_Format::Url($FirstArticleName),
        'Body' => $FirstArticleBody,
        'Excerpt' => "This is the first article's excerpt. Read on to see more.",
        'Format' => 'Html',
        'Status' => 'published',
        'DateInserted' => $Now,
        'AttributionUserID' => $SystemUserID,
        'InsertUserID' => $SystemUserID,
        'InsertIPAddress' => '0.0.0.0'
    ));
}
