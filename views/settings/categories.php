<?php defined('APPLICATION') or exit();

$categories = $this->data('Categories')->result();
?>
<h1><?php echo $this->title(); ?></h1>

<div class="Info">
    <?php echo t('Article categories are used to help organize articles.',
        'Categories are used to help organize articles.'); ?>
</div>

<div class="FilterMenu">
    <?php echo anchor(t('Add Category'), '/settings/articles/addcategory/', 'SmallButton'); ?>
</div>

<h1><?php echo t('Organize Categories'); ?></h1>
<ol class="Sortable">
    <?php
    foreach ($categories as $category) {
        $categoryUrl = articleCategoryUrl($category);

        echo '<li id="Category_' . $category->ArticleCategoryID . '">';
        echo '<table>
            <tr>
              <td>
                 <strong>' . htmlspecialchars($category->Name) . '</strong>
                 ' . anchor($categoryUrl, $categoryUrl) . '
                 ' . wrap($category->Description, 'blockquote') . '
              </td>
              <td class="Buttons">'
            . anchor(t('Edit'), '/settings/articles/editcategory/' . $category->ArticleCategoryID, 'SmallButton')
            . anchor(t('Delete'), '/settings/articles/deletecategory/' . $category->ArticleCategoryID, 'SmallButton')
            . '</td>
            </tr>
         </table>';
        echo '</li>';
    }
    ?>
</ol>
