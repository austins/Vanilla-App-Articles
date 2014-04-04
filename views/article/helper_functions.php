<?php if(!defined('APPLICATION')) exit();

if(!function_exists('ShowArticleOptions')) {
   function ShowArticleOptions($Article) {
      // If $Article type is an array, then cast it to an object.
      if(is_array($Article))
         $Article = (object)$Article;

      //$Sender = Gdn::Controller();
      $Session = Gdn::Session();
      $Options = array();

      // Can the user delete?
      if($Session->CheckPermission('Articles.Articles.Delete'))
         $Options['DeleteArticle'] = array(
            'Label' => T('Delete'),
            'Url' => '/article/delete/' . $Article->ArticleID . '/',
            'Class' => 'Popup');

      // Render the article options menu.
      if(!empty($Options))
      {
         echo '<div class="Options">';
            echo '<span class="ToggleFlyout OptionsMenu">';
            echo '<span class="OptionsTitle" title="' . T('Options') . '">' . T('Options') . '</span>';
            echo Sprite('SpFlyoutHandle', 'Arrow');
            echo '<ul class="Flyout MenuItems" style="display: none;">';
                foreach($Options as $Code => $Option) {
                  echo Wrap(Anchor($Option['Label'], $Option['Url'], GetValue('Class', $Option, $Code)), 'li');
                }
            echo '</ul>';
            echo '</span>';
         echo '</div>';
      }
   }
}
