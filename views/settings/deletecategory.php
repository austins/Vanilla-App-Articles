<?php if(!defined('APPLICATION')) exit();

$Category = $this->Data('Category');
$OtherCategories = $this->Data('OtherCategories');

echo $this->Form->Open();
echo $this->Form->Errors();

if (is_object($OtherCategories)) {
   ?>
   <h1><?php echo T('Delete Category'); ?></h1>
   <ul>
   <?php
   if($OtherCategories->NumRows() == 0) {
      ?>
      <li><p class="Warning"><?php echo T('Are you sure you want to delete this category?'); ?></p></li>
   <?php
   } else {
      // Only show the delete articles checkbox if we're deleting a non-parent category.
      if ($Category->AllowArticles == '1') {
         ?>
         <li>
            <?php
            echo $this->Form->CheckBox('DeleteArticles', "Move articles in this category to a replacement category.", array('value' => '1'));
            ?>
         </li>
      <?php }
      if ($Category->AllowArticles == '1') {
         ?>
         <li id="ReplacementWarning"><p class="Warning"><?php echo T('<strong>Heads Up!</strong> Moving articles into a replacement category can result in articles vanishing (or appearing) if the replacement category has different permissions than the category being deleted.'); ?></p></li>
      <?php
      }
      ?>
      <li id="ReplacementCategory">
         <?php
         echo $this->Form->Label('Replacement Category', 'ReplacementCategoryID');
         echo $this->Form->DropDown(
            'ReplacementCategoryID',
            $OtherCategories,
            array(
               'ValueField' => 'CategoryID',
               'TextField' => 'Name',
               'IncludeNull' => TRUE
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