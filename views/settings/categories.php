<?php defined('APPLICATION') or exit();

$Categories = $this->Data('Categories')->Result();
?>
<h1><?php echo $this->Title(); ?></h1>

<div class="Info">
    <?php echo T('Article categories are used to help organize articles.',
        'Categories are used to help organize articles.'); ?>
</div>

<div class="FilterMenu">
    <?php echo Anchor(T('Add Category'), '/settings/articles/addcategory/', 'SmallButton'); ?>
</div>

<h1><?php echo T('Organize Categories'); ?></h1>
<ol class="Sortable">
    <?php
    foreach ($Categories as $Category) {
        echo '<li id="Category_' . $Category->ArticleCategoryID . '">';
        echo '<table>
            <tr>
              <td>
                 <strong>' . htmlspecialchars($Category->Name) . '</strong>
                 ' . Anchor(htmlspecialchars(rawurldecode($CategoryUrl)), $CategoryUrl) . '
                 ' . Wrap($Category->Description, 'blockquote') . '
              </td>
              <td class="Buttons">'
            . Anchor(T('Edit'), '/settings/articles/editcategory/' . $Category->ArticleCategoryID, 'SmallButton')
            . Anchor(T('Delete'), '/settings/articles/deletecategory/' . $Category->ArticleCategoryID, 'SmallButton')
            . '</td>
            </tr>
         </table>';
        echo '</li>';
    }
    ?>
</ol>
