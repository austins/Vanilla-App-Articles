<?php defined('APPLICATION') or exit();

$categories = $this->data('Categories')->result();
?>
<section class="padded">
    <?php
    echo heading(
        t('Manage Article Categories'),
        [
            ['text' => t('Add Category'), 'url' => 'settings/articles/addcategory'],
        ]
    );
    ?>

    <h2>Organize Categories</h2>

    <div class="padded clearfix">
        <?php
        foreach ($categories as $category) {
            $categoryUrl = articleCategoryUrl($category);

            echo '<div class="full-border"><div class="padded-bottom flex flex-wrap">';

            // Info
            echo '<div class="flex padded-top"><div>';
            echo '<div class="label">' . anchor(htmlspecialchars($category->Name), $categoryUrl) . '</div>';
            echo $category->Description;
            echo '</div></div>';

            // Options
            echo '<div class="options padded-top flex">';
            echo anchor(t('Edit'), '/settings/articles/editcategory/' . $category->ArticleCategoryID, 'btn btn-primary');
            echo anchor(t('Delete'), '/settings/articles/deletecategory/' . $category->ArticleCategoryID, 'btn btn-secondary js-modal');
            echo '</div>';

            echo '</div></div>';
        }
        ?>
    </div>
</section>
