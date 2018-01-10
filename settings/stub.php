<?php
/**
 * Articles stub content for first installation.
 * Called by ArticlesHooks::Setup() to insert stub content upon enabling app.
 *
 * @copyright 2015-2018 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

// Only do this once, ever.
if (Gdn::config('Articles.Version') !== false) {
    return;
}

// Prep content meta data.
$systemUserID = Gdn::userModel()->getSystemUserID();
$now = Gdn_Format::toDateTime();

// Insert first article.
$articleModel = new ArticleModel();

if ($articleModel->getCount() == 0) {
    $firstArticleName = 'My First Article!';
    $firstArticleBody = "Lorem ipsum dolor sit amet, consectetur adipiscing elit."
        . " Duis luctus turpis nec lacus convallis consectetur. Maecenas ligula"
        . " enim, laoreet ac nisl eu, lacinia bibendum neque. Ut orci lacus,"
        . " mollis in aliquet eu, accumsan in enim. Cras vitae pharetra orci."
        . " Morbi ante ante, dapibus a purus et, volutpat posuere nisi. Sed"
        . " faucibus, lectus a sodales fermentum, diam risus malesuada magna,"
        . " vel commodo enim eros non metus. Donec pulvinar tempor volutpat."
        . " Aenean ut dolor eu purus egestas cursus. In aliquet magna"
        . " arcu, sed fermentum ipsum condimentum eget.";

    $articleModel->save(array(
        'ArticleCategoryID' => 1,
        'Name' => $firstArticleName,
        'UrlCode' => Gdn_Format::url($firstArticleName),
        'Body' => $firstArticleBody,
        'Excerpt' => "This is the first article's excerpt. Read on to see more.",
        'Format' => 'Html',
        'Status' => ArticleModel::STATUS_PUBLISHED,
        'DateInserted' => $now,
        'InsertUserID' => $systemUserID,
        'InsertIPAddress' => '0.0.0.0'
    ));
}
