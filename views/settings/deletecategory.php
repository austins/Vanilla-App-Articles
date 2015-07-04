<?php defined('APPLICATION') or exit();

$Category = $this->Data('Category');
$OtherCategories = $this->Data('OtherCategories');

echo $this->Form->Open();
echo $this->Form->Errors();

if (is_object($OtherCategories)) {
    ?>
    <h1><?php echo $this->Title(); ?></h1>
    <ul>
    <?php
    if ($OtherCategories->NumRows() == 0) {
        ?>
        <li><p class="Warning"><?php echo T('Are you sure you want to delete this category?'); ?></p></li>
    <?php
    } else {
        // Only show the delete articles checkbox if we're deleting a non-parent category.
        ?>
        <li>
            <?php
            echo $this->Form->CheckBox('DeleteArticles', "Move articles in this category to a replacement category.",
                array('value' => '1'));
            ?>
        </li>
        <li id="ReplacementWarning"><p
                class="Warning"><?php echo T('<strong>Heads Up!</strong> Moving articles into a replacement category can result in articles vanishing (or appearing) if the replacement category has different permissions than the category being deleted.'); ?></p>
        </li>
        <li id="ReplacementCategory">
            <?php
            echo $this->Form->Label('Replacement Category', 'ReplacementArticleCategoryID');
            echo $this->Form->DropDown(
                'ReplacementArticleCategoryID',
                $OtherCategories,
                array(
                    'ValueField' => 'ArticleCategoryID',
                    'TextField' => 'Name',
                    'IncludeNull' => true
                ));
            ?>
        </li>
        <li id="DeleteArticles">
            <p class="Warning"><?php echo T('All articles in this category will be permanently deleted.'); ?></p>
        </li>
        </ul>
    <?php
    }
    echo $this->Form->Close('Proceed');
}