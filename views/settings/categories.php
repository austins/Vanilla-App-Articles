<?php if(!defined('APPLICATION'))
    exit();

$Categories = $this->Data('Categories')->Result();
?>
<h1><?php echo T('Manage Article Categories'); ?></h1>

<div class="Info">
    <?php echo T('Article categories are used to help organize articles.',
        'Article categories are used to help organize articles. Drag &amp; drop the categories to sort and nest them.'); ?>
</div>

<div class="FilterMenu">
    <?php echo Anchor(T('Add Category'), '/settings/articles/addcategory/', 'SmallButton'); ?>
</div>

<h1><?php echo T('Organize Article Categories'); ?></h1>
<ol class="Sortable">
    <?php
    foreach($Categories as $Category) {
        echo '<li id="Category_' . $Category->CategoryID . '">';
        echo Wrap('<table>
               <tr>
                  <td>
                     <strong>' . htmlspecialchars($Category->Name) . '</strong>
                     ' . Anchor(htmlspecialchars(rawurldecode($CategoryUrl)), $CategoryUrl) . '
                     ' . Wrap($Category->Description, 'blockquote') . '
                  </td>
                  <td class="Buttons">'
            . Anchor(T('Edit'), '/settings/articles/editcategory/' . $Category->CategoryID .'/', 'SmallButton')
            . Anchor(T('Delete'), '/settings/articles/deletecategory/' . $Category->CategoryID . '/', 'SmallButton')
            . '</td>
               </tr>
            </table>', 'div');
        echo '</li>';
    }
    ?>
</ol>
