<?php defined('APPLICATION') or exit();

$Controller = Gdn::controller();
$ControllerName = strtolower($Controller->ControllerName);
$RequestMethod = strtolower($Controller->RequestMethod);
?>
<div class="BoxButtons BoxNewArticle">
    <?php
    echo anchor(t('New Article'), '/compose/article',
        'Button Action Big Primary BigButton NewArticle');

    Gdn::controller()->fireEvent('AfterNewArticleButton');
    ?>
</div>

<div class="BoxFilter BoxComposeFilter">
    <ul class="FilterMenu">
        <li <?php if ($RequestMethod == 'index') echo 'class="Active"'; ?>>
            <?php echo anchor(sprite('SpArticlesDashboard', 'SpMyDiscussions Sprite') . ' ' . t('Articles Dashboard'), '/compose'); ?>
        </li>

        <li <?php if ($RequestMethod == 'posts') echo 'class="Active"'; ?>>
            <?php echo anchor(sprite('SpArticles', 'SpMyDrafts Sprite') . ' ' . t('Articles'), '/compose/posts'); ?>
        </li>
    </ul>
</div>