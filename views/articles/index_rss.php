<?php if(!defined('APPLICATION')) exit();

// TODO: RSS; update properties, view, etc.

echo wrap(Gdn_Format::Text($this->Head->title()), 'description');
echo wrap(Gdn::config('Garden.Locale', 'en-US'), 'language');
echo '<atom:link href="' . htmlspecialchars(url($this->SelfUrl, TRUE)) . '" rel="self" type="application/rss+xml" />';

foreach($this->data('Articles') as $Article) {
  $ItemString = wrap(Gdn_Format::Text($Article->Name), 'title');
  $ItemString .= wrap(articleUrl($Article), 'link');
  $ItemString .= wrap(date('r', Gdn_Format::ToTimeStamp($Article->DateInserted)), 'pubDate');
  $ItemString .= wrap(Gdn_Format::Text($Article->InsertName), 'dc:creator');
  $ItemString .= wrap($Article->ArticleID . '@' . url('/article'), 'guid', array('isPermaLink' => 'false'));
  $ItemString .= wrap('<![CDATA[' . Gdn_Format::RssHtml($Article->Body, $Article->Format) . ']]>', 'description');

  echo wrap($ItemString, 'item');
}