<?php if(!defined('APPLICATION')) exit();

if(!function_exists('ShowArticlesDashboardMenu'))
   include($this->FetchViewLocation('helper_functions', 'compose', 'articles'));

ShowArticlesDashboardMenu($this->RequestMethod);

// Declare variables.
$Categories = $this->Data('Categories');

// Open the form.
echo $this->Form->Open();
echo $this->Form->Errors();
?>
<ul>
   <?php
   if($Categories->NumRows() > 0) {
      echo '<li>';
         echo $this->Form->Label('Category', 'CategoryID'), ' ';
         echo $this->Form->DropDown('CategoryID', $Categories, array(
            'IncludeNull' => TRUE,
            'ValueField' => 'CategoryID',
            'TextField' => 'Name',
            'Value' => GetValue('CategoryID', $this->Category)
         ));
      echo '</li>';
   }
   ?>
   <li>
      <?php
      echo $this->Form->Label('Article Title', 'Name');
      echo Wrap($this->Form->TextBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div', array('class' => 'TextBoxWrapper'));
      ?>
   </li>
   <li>
      <?php echo $this->Form->BodyBox('Body', array('Table' => 'Article')); ?>
   </li>
</ul>
<div class="Buttons">
   <?php
   echo $this->Form->Button((property_exists($this, 'Article')) ? 'Save' : 'Post Article', array('class' => 'Button Primary ArticleButton'));
   ?>
</div>
<?php
echo $this->Form->Close();
