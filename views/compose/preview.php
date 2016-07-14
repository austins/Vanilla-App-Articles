<?php if (!defined('APPLICATION')) exit();
$this->fireEvent('BeforePreviewFormat');
$this->Preview->Body = Gdn_Format::To($this->Preview->Body, GetValue('Format', $this->Preview, c('Garden.InputFormatter')));
$this->fireEvent('AfterPreviewFormat');
?>
<div class="Preview">
    <div class="Message"><?php echo $this->Preview->Body; ?></div>
</div>