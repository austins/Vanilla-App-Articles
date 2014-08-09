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

    public function Counts($Column) {
        $Result = array('Complete' => true);

        switch ($Column) {
            case 'CountArticles':
                $this->Database->Query(DBAModel::GetCountSQL('count', 'ArticleCategory', 'Article'));
                break;
            case 'CountComments':
                $this->Database->Query(DBAModel::GetCountSQL('sum', 'ArticleCategory', 'Article', $Column,
                    'CountComments'));
                break;
            case 'LastArticleID':
                $this->Database->Query(DBAModel::GetCountSQL('max', 'ArticleCategory', 'Article'));
                break;
            case 'LastCommentID':
                $Data = $this->SQL
                    ->Select('a.CategoryID')
                    ->Select('ac.CommentID', 'max', 'LastCommentID')
                    ->Select('a.ArticleID', 'max', 'LastArticleID')
                    ->Select('ac.DateInserted', 'max', 'DateLastComment')
                    ->Select('a.DateInserted', 'max', 'DateLastArticle')
                    ->From('ArticleComment ac')
                    ->Join('Article a', 'a.ArticleID = ac.ArticleID')
                    ->GroupBy('a.CategoryID')
                    ->Get()->ResultArray();

                // Now we have to grab the discussions associated with these comments.
                $CommentIDs = ConsolidateArrayValuesByKey($Data, 'LastCommentID');

                // Grab the discussions for the comments.
                $this->SQL
                    ->Select('ac.CommentID, ac.ArticleID')
                    ->From('ArticleComment ac')
                    ->WhereIn('ac.CommentID', $CommentIDs);

                $Articles = $this->SQL->Get()->ResultArray();
                $Articles = Gdn_DataSet::Index($Articles, array('CommentID'));

                foreach ($Data as $Row) {
                    $CategoryID = (int)$Row['CategoryID'];
                    $Category = $this->GetByID($CategoryID);
                    $CommentID = $Row['LastCommentID'];
                    $ArticleID = GetValueR("$CommentID.ArticleID", $Articles, null);

                    $DateLastComment = Gdn_Format::ToTimestamp($Row['DateLastComment']);
                    $DateLastArticle = Gdn_Format::ToTimestamp($Row['DateLastArticle']);

                    $Set = array('LastCommentID' => $CommentID);

                    if ($ArticleID) {
                        $LastArticleID = GetValue('LastArticleID', $Category);

                        if ($DateLastComment >= $DateLastArticle) {
                            // The most recent article is from this comment.
                            $Set['LastArticleID'] = $ArticleID;
                        } else {
                            // The most recent discussion has no comments.
                            $Set['LastCommentID'] = null;
                        }
                    } else {
                        // Something went wrong.
                        $Set['LastCommentID'] = null;
                        $Set['LastArticleID'] = null;
                    }

                    $this->SetField($CategoryID, $Set);
                }

                break;
            case 'LastDateInserted':
                $Categories = $this->SQL
                    ->Select('ca.CategoryID')
                    ->Select('a.DateInserted', '', 'DateLastArticle')
                    ->Select('ac.DateInserted', '', 'DateLastComment')

                    ->From('ArticleCategory ca')
                    ->Join('Article a', 'a.ArticleID = ca.LastArticleID')
                    ->Join('ArticleComment ac', 'ac.CommentID = ca.LastCommentID')
                    ->Get()->ResultArray();

                foreach ($Categories as $Category) {
                    $DateLastArticle = GetValue('DateLastArticle', $Category);
                    $DateLastComment = GetValue('DateLastComment', $Category);

                    $MaxDate = $DateLastComment;
                    if (is_null($DateLastComment) || $DateLastArticle > $MaxDate)
                        $MaxDate = $DateLastArticle;

                    if (is_null($MaxDate))
                        continue;

                    $CategoryID = (int)$Category['CategoryID'];
                    $this->SetField($CategoryID, 'LastDateInserted', $MaxDate);
                }

                break;
        }

        return $Result;
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
