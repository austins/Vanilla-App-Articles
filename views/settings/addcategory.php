<?php defined('APPLICATION') or exit();
?>
    <h1><?php echo $this->title(); ?></h1>
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
            echo $this->Form->TextBox('UrlCode');
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
        <?php if (count($this->PermissionData) > 0) { ?>
            <li id="Permissions">
                <?php
                echo $this->Form->CheckBox('CustomPermissions', 'This category has custom permissions.');

                echo '<div class="CategoryPermissions">';

                echo $this->Form->Simple(
                    $this->data('_PermissionFields', array()),
                    array('Wrap' => array('', ''), 'ItemWrap' => array('<div class="P">', '</div>')));

                echo t('Check all permissions that apply for each role');
                echo $this->Form->CheckBoxGridGroups($this->PermissionData, 'Permission');
                echo '</div>';
                ?>
            </li>
        <?php } ?>
    </ul>
<?php
echo $this->Form->Close('Save');
