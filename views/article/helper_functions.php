<?php if(!defined('APPLICATION')) exit();

if(!function_exists('ShowArticleOptions')) {
   function ShowArticleOptions($Article) {
      // If $Article type is an array, then cast it to an object.
      if(is_array($Article))
         $Article = (object)$Article;

      //$Sender = Gdn::Controller();
      $Session = Gdn::Session();
      $Options = array();

      // Can the user edit?
      if($Session->CheckPermission('Articles.Articles.Edit'))
         $Options['EditArticle'] = array(
            'Label' => T('Edit'),
            'Url' => '/compose/editarticle/' . $Article->ArticleID . '/');

      // Can the user close?
      if($Session->CheckPermission('Articles.Articles.Close')) {
         $NewClosed = (int)!$Article->Closed;
         $Options['CloseArticle'] = array(
            'Label' => T($Article->Closed ? 'Reopen' : 'Close'),
            'Url' => "/compose/closearticle?articleid={$Article->ArticleID}&close={$NewClosed}",
            'Class' => 'Hijack');
      }

      // Can the user delete?
      if($Session->CheckPermission('Articles.Articles.Delete'))
         $Options['DeleteArticle'] = array(
            'Label' => T('Delete'),
            'Url' => '/compose/deletearticle/' . $Article->ArticleID . '/',
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
