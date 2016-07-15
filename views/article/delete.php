<?php defined('APPLICATION') or exit();
?>

<h1><?php echo $this->data('Title'); ?></h1>

<?php
echo $this->Form->Open();
echo $this->Form->Errors();

echo '<div class="P">' . sprintf(t('Are you sure you want to delete this %s?'), t('article')) . '</div>';

echo '<div class="Buttons Buttons-Confirm">';
echo $this->Form->Button('OK', array('class' => 'Button Primary'));
echo $this->Form->Button('Cancel', array('type' => 'button', 'class' => 'Button Close'));
echo '<div>';
echo $this->Form->Close();
?>
