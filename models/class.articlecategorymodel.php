<?php
if (!defined('APPLICATION'))
    exit();

/**
 * Handles data for articles.
 */
class ArticleCategoryModel extends Gdn_Model {
    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('ArticleCategory');
    }

    /**
     * Gets the data for multiple articles based on given criteria.
     *
     * @param array $Wheres SQL conditions.
     *
     * @return Gdn_DataSet SQL result.
     */
    public function Get($Wheres = null) {
        // Set up selection query.
        $this->SQL->Select('ac.*')->From('ArticleCategory ac');

        // Handle SQL conditions for wheres.
        $this->EventArguments['Wheres'] = & $Wheres;
        $this->FireEvent('BeforeGet');

        if (is_array($Wheres))
            $this->SQL->Where($Wheres);

        // Set order of data.
        $this->SQL->OrderBy('ac.Name', 'asc');

        // Fetch data.
        $Categories = $this->SQL->Get();

        // Prepare and fire event.
        $this->EventArguments['Data'] = $Categories;
        $this->FireEvent('AfterGet');

        return $Categories;
    }

    public function GetByID($CategoryID) {
        // Set up the query.
        $this->SQL->Select('ac.*')
            ->From('ArticleCategory ac')
            ->Where('ac.CategoryID', $CategoryID);

        // Fetch data.
        $Category = $this->SQL->Get()->FirstRow();

        return $Category;
    }

    public function GetByUrlCode($CategoryUrlCode) {
        // Set up the query.
        $this->SQL->Select('ac.*')
            ->From('ArticleCategory ac')
            ->Where('ac.UrlCode', $CategoryUrlCode);

        // Fetch data.
        $Category = $this->SQL->Get()->FirstRow();

        return $Category;
    }

    /**
     * Delete a single category and assign its articles to another.
     *
     * @return void
     * @throws Exception // TODO update comment.
     * @param object $Category
     * @param int $ReplacementCategoryID Unique ID of category all discussion are being move to.
     */
    public function Delete($Category, $ReplacementCategoryID) {
        // Don't do anything if the required category object & properties are not defined.
        if (!is_object($Category)
            || !property_exists($Category, 'CategoryID')
            || !property_exists($Category, 'Name')
            || $Category->CategoryID <= 0
        ) {
            throw new Exception(T('Invalid category for deletion.'));
        } else {
            // If there is a replacement category...
            if ($ReplacementCategoryID > 0) {
                // Update articles.
                $this->SQL
                    ->Update('Article')
                    ->Set('CategoryID', $ReplacementCategoryID)
                    ->Where('CategoryID', $Category->CategoryID)
                    ->Put();

                // Update the article count.
                $Count = $this->SQL
                    ->Select('ArticleID', 'count', 'ArticleCount')
                    ->From('Article')
                    ->Where('CategoryID', $ReplacementCategoryID)
                    ->Get()
                    ->FirstRow()
                    ->ArticleCount;

                if (!is_numeric($Count))
                    $Count = 0;

                $this->SQL
                    ->Update('ArticleCategory')->Set('CountArticles', $Count)
                    ->Where('CategoryID', $ReplacementCategoryID)
                    ->Put();
            } else {
                // Delete comments in this category.
                /* TODO: uncomment this code after adding comments feature.
                $this->SQL
                   ->From('ArticleComment ac')
                   ->Join('Article a', 'ac.ArticleID = a.ArticleID')
                   ->Where('a.ArticleID', $Category->CategoryID)
                   ->Delete();
                */

                // Delete articles in this category
                $this->SQL->Delete('Article', array('CategoryID' => $Category->CategoryID));
            }

            // Delete the category
            $this->SQL->Delete('ArticleCategory', array('CategoryID' => $Category->CategoryID));
        }
    }

    public function SetRecentPost($CategoryID) {
        $Row = $this->SQL
            ->GetWhere('Article', array('CategoryID' => $CategoryID), 'DateLastComment', 'desc', 1)
            ->FirstRow(DATASET_TYPE_ARRAY);

        $Fields = array('LastCommentID' => NULL, 'LastArticleID' => NULL);

        if ($Row) {
            $Fields['LastCommentID'] = $Row['LastCommentID'];
            $Fields['LastArticleID'] = $Row['ArticleID'];
        }

        $this->SetField($CategoryID, $Fields);
    }
}
