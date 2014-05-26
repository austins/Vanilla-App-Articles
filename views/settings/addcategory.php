<?php
if (!defined('APPLICATION'))
    exit();
?>
    <h1><?php echo $this->Title(); ?></h1>
<?php
echo $this->Form->Open();
echo $this->Form->Errors();
?>
    <ul>
        <li>
            <?php
            echo $this->Form->Label('Category Name', 'Name');
            echo $this->Form->TextBox('Name');
            ?>
        </li>
        <li id="UrlCode">
            <?php
            echo Wrap(T('Category URL:'), 'strong');
            echo ' ' . Gdn::Request()->Url('/articles/category/', true);
            echo Wrap(htmlspecialchars($this->Form->GetValue('UrlCode')));
            echo $this->Form->TextBox('UrlCode') . '/';
            echo Anchor(T('edit'), '#', 'Edit');
            echo Anchor(T('OK'), '#', 'Save SmallButton');
            ?>
        </li>
        <li>
            <?php
            echo $this->Form->Label('Description', 'Description');
            echo $this->Form->TextBox('Description', array('MultiLine' => true));
            ?>
        </li>
    </ul>
<?php
echo $this->Form->Close('Save');
