<?php if (!defined('APPLICATION')) exit();
$this->FireEvent('BeforePreviewFormat');
$this->Preview->Body = Gdn_Format::To($this->Preview->Body, GetValue('Format', $this->Preview, C('Garden.InputFormatter')));
$this->FireEvent('AfterPreviewFormat');
?>
<div class="Preview">
    <div class="Message"><?php echo $this->Preview->Body; ?></div>
</div>