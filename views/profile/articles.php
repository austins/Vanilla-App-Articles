<?php
if (!defined('APPLICATION'))
    exit();

echo '<h2 class="H">' . T('Articles') . '</h2>';

if (!is_object($this->ArticleData) || $this->ArticleData->NumRows() <= 0) {
    echo Wrap(T("This user has not published any articles yet."), 'div', array('Class' => 'Empty'));
} else {
    echo '<ul class="DataList Articles">';
    $ViewLocation = $this->FetchViewLocation('Index', 'Articles', 'Articles');
    include($ViewLocation);
    echo '</ul>';

    echo $this->Pager->ToString('more');
}
