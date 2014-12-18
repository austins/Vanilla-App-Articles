<?php defined('APPLICATION') or exit();

echo '<h2 class="H">' . T('Articles') . '</h2>';

if (!$this->Data('Articles') || count($this->Data('Articles')) == 0) {
    echo Wrap(T("This user has not published any articles yet."), 'div', array('Class' => 'Empty'));
} else {
    echo '<ul class="DataList Articles">';
    include($this->FetchViewLocation('index', 'articles', 'Articles'));
    echo '</ul>';

    echo $this->Pager->ToString('more');
}
