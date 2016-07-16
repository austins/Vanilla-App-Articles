<?php defined('APPLICATION') or exit();

$this->fireEvent('BeforePreviewFormat');
$this->Preview->Body = Gdn_Format::to($this->Preview->Body,
    val('Format', $this->Preview, c('Garden.InputFormatter')));
$this->fireEvent('AfterPreviewFormat');
?>
<div class="Preview">
    <div class="Message"><?php echo $this->Preview->Body; ?></div>
</div>