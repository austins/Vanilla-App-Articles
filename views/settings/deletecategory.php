<?php defined('APPLICATION') or exit(); ?>
    <h1><?php echo t('Delete Article Category'); ?></h1>

<?php
/** @var Gdn_Form $form */
$form = $this->Form;
$articlesCount = $this->data('Category')->CountArticles;

echo $form->open();
echo $form->errors();
if (is_object($this->data('OtherCategories'))) {
    ?>
    <?php
    if ($this->data('OtherCategories')->numRows() == 0) {
        ?>
        <div class="padded"><?php echo t('Are you sure you want to delete this article category?'); ?></div>
        <?php
    } else { ?>
        <ul>
            <li class="form-group">
                <div class="input-wrap">
                    <?php echo $form->radio('ContentAction', 'Move content from this category to a replacement category.', ['value' => 'move']); ?>
                </div>
                <div class="input-wrap">
                    <?php echo $form->radio('ContentAction', 'Permanently delete all content in this category.', ['value' => 'delete']); ?>
                </div>
            </li>
            <li id="ReplacementCategory" class="form-group">
                <div class="label-wrap">
                    <?php
                    echo $form->label('Replacement Category', 'ReplacementCategoryID');
                    ?>
                    <div id="ReplacementWarning" class="info">
                        <div
                                class="text-danger"><?php echo t('<strong>Heads Up!</strong> Moving articles into a replacement category can result in articles vanishing (or appearing) if the replacement category has different permissions than the category being deleted.'); ?></div>
                    </div>
                </div>
                <div class="input-wrap">
                    <?php echo $form->dropDown(
                        'ReplacementCategoryID',
                        $this->data('OtherCategories'),
                        [
                            'ValueField' => 'ArticleCategoryID',
                            'TextField' => 'Name',
                            'IncludeNull' => true
                        ]);
                    ?>
                </div>
            </li>
            <li id="DeleteCategory">
                <?php if ($articlesCount) { ?>
                    <div class="alert alert-danger padded">
                        <?php if ($articlesCount) { ?>
                            <p>
                                <?php printf(
                                    t(plural(
                                        $articlesCount,
                                        '<strong>%s</strong> article will be deleted. There is no undo and it will not be logged.',
                                        '<strong>%s</strong> articles will be deleted. There is no undo and they will not be logged.'
                                    )),
                                    $articlesCount
                                );
                                ?>
                            </p>
                        <?php } ?>
                    </div>
                <?php } ?>
                <div class="form-group">
                    <div class="input-wrap">
                        <?php echo $form->checkBox('ConfirmDelete', 'Yes, permanently delete it all.'); ?>
                    </div>
                </div>
            </li>
        </ul>

    <?php } ?>
    <?php echo $form->close('Proceed'); ?>
    <?php
}

