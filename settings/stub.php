<?php defined('APPLICATION') or exit();
/**
 * Copyright (C) 2015  Austin S.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

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
        'ArticleCategoryID' => 1,
        'Name' => $FirstArticleName,
        'UrlCode' => Gdn_Format::Url($FirstArticleName),
        'Body' => $FirstArticleBody,
        'Excerpt' => "This is the first article's excerpt. Read on to see more.",
        'Format' => 'Html',
        'Status' => ArticleModel::STATUS_PUBLISHED,
        'DateInserted' => $Now,
        'AttributionUserID' => $SystemUserID,
        'InsertUserID' => $SystemUserID,
        'InsertIPAddress' => '0.0.0.0'
    ));
}
