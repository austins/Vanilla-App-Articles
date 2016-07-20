<?php defined('APPLICATION') or exit();

// TODO: RSS; update properties, view, etc.

echo wrap(Gdn_Format::text($this->Head->title()), 'description');
echo wrap(Gdn::config('Garden.Locale', 'en-US'), 'language');
echo '<atom:link href="' . htmlspecialchars(url($this->SelfUrl, true)) . '" rel="self" type="application/rss+xml" />';

foreach ($this->data('Articles') as $article) {
    $itemString = wrap(Gdn_Format::text($article->Name), 'title');
    $itemString .= wrap(articleUrl($article), 'link');
    $itemString .= wrap(date('r', Gdn_Format::toTimestamp($article->DateInserted)), 'pubDate');
    $itemString .= wrap(Gdn_Format::text($article->ArticleCategoryName), 'category');
    $itemString .= wrap(Gdn_Format::text($article->InsertName), 'dc:creator');
    $itemString .= wrap($article->ArticleID . '@' . url('/articles'), 'guid', array('isPermaLink' => 'false'));

    $description = ($article->Excerpt !== "" ? $article->Excerpt : $article->Body);
    $itemString .= wrap('<![CDATA[' . Gdn_Format::plainText($description, $article->Format) . ']]>', 'description');

    $itemString .= wrap('<![CDATA[' . Gdn_Format::rssHtml($article->Body, $article->Format) . ']]>', 'content:encoded');

    echo wrap($itemString, 'item');
}