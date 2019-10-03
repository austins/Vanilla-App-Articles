<?php defined('APPLICATION') or exit();

echo heading($this->data('Title'), '', '', [], '/settings/articles/categories');

echo $this->Form->open();
echo $this->Form->errors();
?>
    <ul>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Name'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Name'); ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo wrap(t('Category URL:'), 'strong'); ?>
            </div>
            <div id="UrlCode" class="input-wrap category-url-code">
                <?php
                echo '<div class="category-url">';
                echo Gdn::request()->url('/articles/category', true);
                echo '/';
                echo wrap(htmlspecialchars($this->Form->getValue('UrlCode')));
                echo '</div>';
                echo $this->Form->textBox('UrlCode');
                echo ($this->Form->getValue('UrlCode')) ? '/' : '';
                echo anchor(t('edit'), '#', 'Edit btn btn-link');
                echo anchor(t('OK'), '#', 'Save btn btn-primary');
                ?>
            </div>
        </li>
        <li class="form-group">
            <div class="label-wrap">
                <?php echo $this->Form->label('Description', 'Description'); ?>
            </div>
            <div class="input-wrap">
                <?php echo $this->Form->textBox('Description', ['MultiLine' => true]); ?>
            </div>
        </li>


        <?php if (count($this->PermissionData) > 0) { ?>
            <li id="Permissions" class="form-group">
                <?php echo $this->Form->toggle('CustomPermissions', 'This category has custom permissions.'); ?>
            </li>

            <li id="Permissions">
                <?php
                echo '<div class="CategoryPermissions">';
                echo $this->Form->simple(
                    $this->data('_PermissionFields', []),
                    ['Wrap' => ['<div class="form-group">', '</div>'], 'ItemWrap' => ['<div class="input-wrap">', '</div>']]);

                echo '<div class="padded">' . t('Check all permissions that apply for each role') . '</div>';

                echo $this->Form->checkBoxGridGroups($this->PermissionData, 'Permission');
                echo '</div>';
                ?>
            </li>
        <?php } ?>
    </ul>
<?php
echo $this->Form->close('Save');
