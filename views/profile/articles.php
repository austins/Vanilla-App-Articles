<?php defined('APPLICATION') or exit();

echo '<h2 class="H">' . T('Articles') . '</h2>';

if (!$this->data('Articles') || count($this->data('Articles')) == 0) {
    echo Wrap(T("This user has not published any articles yet."), 'div', array('Class' => 'Empty'));
} else {
    echo '<section class="Articles">';
    include($this->fetchViewLocation('index', 'articles', 'Articles'));
    echo '</section>';

    echo $this->Pager->ToString('more');
}
