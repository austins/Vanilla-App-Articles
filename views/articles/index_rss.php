<?php if(!defined('APPLICATION')) exit();

// TODO: RSS; update properties, view, etc.

echo Wrap(Gdn_Format::Text($this->Head->title()), 'description');
echo Wrap(Gdn::config('Garden.Locale', 'en-US'), 'language');
echo '<atom:link href="' . htmlspecialchars(url($this->SelfUrl, TRUE)) . '" rel="self" type="application/rss+xml" />';

foreach($this->data('Articles') as $Article) {
  $ItemString = Wrap(Gdn_Format::Text($Article->Name), 'title');
  $ItemString .= Wrap(articleUrl($Article), 'link');
  $ItemString .= Wrap(date('r', Gdn_Format::ToTimeStamp($Article->DateInserted)), 'pubDate');
  $ItemString .= Wrap(Gdn_Format::Text($Article->InsertName), 'dc:creator');
  $ItemString .= Wrap($Article->ArticleID . '@' . url('/article'), 'guid', array('isPermaLink' => 'false'));
  $ItemString .= Wrap('<![CDATA[' . Gdn_Format::RssHtml($Article->Body, $Article->Format) . ']]>', 'description');

  echo Wrap($ItemString, 'item');
}