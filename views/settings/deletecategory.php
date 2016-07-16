<?php defined('APPLICATION') or exit();

$category = $this->data('Category');
$otherCategories = $this->data('OtherCategories');

echo $this->Form->open();
echo $this->Form->errors();

if (is_object($otherCategories)) {
    ?>
    <h1><?php echo $this->title(); ?></h1>
    <ul>
    <?php
    if ($otherCategories->numRows() == 0) {
        ?>
        <li><p class="Warning"><?php echo t('Are you sure you want to delete this category?'); ?></p></li>
        <?php
    } else {
        // Only show the delete articles checkbox if we're deleting a non-parent category.
        ?>
        <li>
            <?php
            echo $this->Form->checkBox('DeleteArticles', "Move articles in this category to a replacement category.",
                array('value' => '1'));
            ?>
        </li>
        <li id="ReplacementWarning"><p
                class="Warning"><?php echo t('<strong>Heads Up!</strong> Moving articles into a replacement category can result in articles vanishing (or appearing) if the replacement category has different permissions than the category being deleted.'); ?></p>
        </li>
        <li id="ReplacementCategory">
            <?php
            echo $this->Form->label('Replacement Category', 'ReplacementArticleCategoryID');
            echo $this->Form->dropDown(
                'ReplacementArticleCategoryID',
                $otherCategories,
                array(
                    'ValueField' => 'ArticleCategoryID',
                    'TextField' => 'Name',
                    'IncludeNull' => true
                ));
            ?>
        </li>
        <li id="DeleteArticles">
            <p class="Warning"><?php echo t('All articles in this category will be permanently deleted.'); ?></p>
        </li>
        </ul>
        <?php
    }
    echo $this->Form->close('Proceed');
}