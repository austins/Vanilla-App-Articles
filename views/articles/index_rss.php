<?php if(!defined('APPLICATION')) exit();

echo Wrap(Gdn_Format::Text($this->Head->Title()), 'description');
echo Wrap(Gdn::Config('Garden.Locale', 'en-US'), 'language');
echo '<atom:link href="' . htmlspecialchars(Url($this->SelfUrl, TRUE)) . '" rel="self" type="application/rss+xml" />';

foreach($this->Data('Articles') as $Article) {
  $ItemString = Wrap(Gdn_Format::Text($Article->Name), 'title');
  $ItemString .= Wrap($Article->UrlCode, 'link');
  $ItemString .= Wrap(date('r', Gdn_Format::ToTimeStamp($Article->DateInserted)), 'pubDate');
  $ItemString .= Wrap(Gdn_Format::Text($Article->InsertName), 'dc:creator');
  $ItemString .= Wrap($Article->ArticleID . '@' . Url('/article'), 'guid', array('isPermaLink' => 'false'));
  $ItemString .= Wrap('<![CDATA[' . Gdn_Format::RssHtml($Article->Body, $Article->Format) . ']]>', 'description');

  echo Wrap($ItemString, 'item');
}