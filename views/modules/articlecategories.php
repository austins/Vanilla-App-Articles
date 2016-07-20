<?php defined('APPLICATION') or exit();

$controllerName = strtolower($this->_Sender->ControllerName);
$requestMethod = strtolower($this->_Sender->RequestMethod);

$onArticlesController = ($controllerName === 'articlescontroller');
$onAllCategoriesMethod = ($onArticlesController && ($requestMethod === 'categories'));

$categories = &$this->Data;
$currentCategoryID = val('ArticleCategoryID', $this->_Sender->data('ArticleCategory'), false);
?>
<div class="Box BoxArticleCategories">
    <h4><?php echo t('Article Categories'); ?></h4>

    <?php if (!c('Articles.Modules.ShowCategoriesAsDropDown', false)): ?>
        <ul class="PanelInfo PanelArticleCategories">
            <?php
            // All Categories link
            $allArticlesClass = $onAllCategoriesMethod ? array('class' => 'Active') : '';
            echo wrap(anchor(t('All Categories'), '/articles/categories'), 'li', $allArticlesClass);

            $articleModel = new ArticleModel();
            $articleOffset = 0;
            $articleLimit = 1;
            $articleWheres = array('a.Status' => ArticleModel::STATUS_PUBLISHED);
            foreach ($categories as $category) {
                // Category must have at least one published article.
                $articleWheres['a.ArticleCategoryID'] = $category['ArticleCategoryID'];
                $article = $articleModel->get($articleOffset, $articleLimit, $articleWheres)->firstRow();
                $publishedArticleExists = isset($article->ArticleID);

                if (!$publishedArticleExists) {
                    continue;
                }

                // Output category link
                $categoryClass = ($currentCategoryID === $category['ArticleCategoryID']) ? array('class' => 'Active') :
                    '';
                echo wrap(anchor($category['Name'], articleCategoryUrl($category)), 'li', $categoryClass);
            }
            ?>
        </ul>
    <?php else: ?>
        <select id="ArticleCategoriesDropDown">
            <option disabled<?php echo(!$currentCategoryID ? ' selected' :
                ''); ?>><?php echo t('Select a category...'); ?></option>
            <option id="ArticleCategoriesDropDown_AllCategories" value="all"<?php echo($onAllCategoriesMethod ?
                ' selected ' : ''); ?>><?php echo t('All Categories'); ?></option>
            <?php foreach ($categories as $category): ?>
                <option id="ArticleCategoriesDropDown_ArticleCategory_<?php echo $category['ArticleCategoryID']; ?>"
                        value="<?php echo $category['UrlCode']; ?>"<?php echo(($currentCategoryID === $category['ArticleCategoryID']) ?
                    ' selected' : ''); ?>><?php echo $category['Name']; ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>
</div>