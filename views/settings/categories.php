<?php defined('APPLICATION') or exit();

$Categories = $this->data('Categories')->result();
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
    foreach ($Categories as $Category) {
        echo '<li id="Category_' . $Category->ArticleCategoryID . '">';
        echo '<table>
            <tr>
              <td>
                 <strong>' . htmlspecialchars($Category->Name) . '</strong>
                 ' . anchor(htmlspecialchars(rawurldecode($CategoryUrl)), $CategoryUrl) . '
                 ' . wrap($Category->Description, 'blockquote') . '
              </td>
              <td class="Buttons">'
            . anchor(t('Edit'), '/settings/articles/editcategory/' . $Category->ArticleCategoryID, 'SmallButton')
            . anchor(t('Delete'), '/settings/articles/deletecategory/' . $Category->ArticleCategoryID, 'SmallButton')
            . '</td>
            </tr>
         </table>';
        echo '</li>';
    }
    ?>
</ol>
