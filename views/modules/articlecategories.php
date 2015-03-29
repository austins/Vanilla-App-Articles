<?php defined('APPLICATION') or exit();

$ControllerName = strtolower($this->_Sender->ControllerName);
$RequestMethod = strtolower($this->_Sender->RequestMethod);

$OnArticlesController = ($ControllerName === 'articlescontroller');

$Categories = $this->Data->Result();
$CategoryID = val('ArticleCategoryID', $this->_Sender->Data('ArticleCategory'), false);
?>
<div class="Box BoxArticleCategories">
    <h4><?php echo T('Article Categories'); ?></h4>
    <ul class="PanelInfo PanelArticleCategories">
        <?php
        // All Categories link
        $AllArticlesClass = ($OnArticlesController && ($RequestMethod === 'index')) ? array('class' => 'Active') : '';
        echo Wrap(Anchor(T('All Categories'), '/articles'), 'li', $AllArticlesClass);

        foreach ($Categories as $Category) {
            $CategoryClass = ($CategoryID === $Category->ArticleCategoryID) ? array('class' => 'Active') : '';
            echo Wrap(Anchor($Category->Name, ArticleCategoryUrl($Category)), 'li', $CategoryClass);
        }
        ?>
    </ul>
</div>