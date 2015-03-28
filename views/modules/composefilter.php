<?php defined('APPLICATION') or exit();

$Controller = Gdn::Controller();
$ControllerName = strtolower($Controller->ControllerName);
$RequestMethod = strtolower($Controller->RequestMethod);
?>
<div class="BoxFilter BoxComposeFilter">
    <ul class="FilterMenu">
        <li <?php if ($RequestMethod == 'index') echo 'class="Active"'; ?>>
            <?php echo Anchor(T('Dashboard'), '/compose'); ?>
        </li>

        <li <?php if ($RequestMethod == 'posts') echo 'class="Active"'; ?>>
            <?php echo Anchor(T('Posts'), '/compose/posts'); ?>
        </li>

        <li><?php echo Anchor(T('New Article'), '/compose/article'); ?></li>
    </ul>
</div>