<?php if(!defined('APPLICATION')) exit();
/**
 * Articles stub content for first installation.
 *
 * Called by ArticlesHooks::Setup() to insert stub content upon enabling app.
 *
 * @package Articles
 */

// Only do this once, ever.
if(!$Drop)
    return;

// Prep content meta data.
$SystemUserID = Gdn::UserModel()->GetSystemUserID();
$Now = Gdn_Format::ToDateTime();

// Insert first article category.
$UncategorizedArticleCategoryID = $SQL->Insert('ArticleCategory', array(
    'Name' => 'Uncategorized',
    'UrlCode' => 'uncategorized',
    'Description' => 'Uncategorized articles.',
    'DateInserted' => $Now,
    'CountArticles' => 1,
    'LastArticleID' => 1,
    'InsertUserID' => $SystemUserID,
    'LastDateInserted' => $Now
));

// Insert first article.
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

$FirstArticleID = $SQL->Insert('Article', array(
    'CategoryID' => 1,
    'Name' => $FirstArticleName,
    'UrlCode' => Gdn_Format::Url($FirstArticleName),
    'Body' => $FirstArticleBody,
    'Excerpt' => "This is the first article's excerpt. Read on to see more.",
    'Format' => 'Html',
    'Status' => 'published',
    'DateInserted' => $Now,
    'AuthorUserID' => $SystemUserID,
    'InsertUserID' => $SystemUserID,
    'InsertIPAddress' => '0.0.0.0'
));
