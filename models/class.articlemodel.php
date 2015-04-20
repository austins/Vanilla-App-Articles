<?php defined('APPLICATION') or exit();
/**
 * Copyright (C) 2015  Austin S. (Shadowdare)
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Handles data for articles.
 */
class ArticleModel extends Gdn_Model {
    const STATUS_DRAFT = 'Draft';
    const STATUS_PENDING = 'Pending';
    const STATUS_PUBLISHED = 'Published';

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('Article');
    }

    /**
     * Count recalculation. Called by DBAModel->Counts().
     *
     * @param string $Column
     * @param int $From ID range begin inclusive.
     * @param int $To ID range end inclusive.
     * @param int $Max ID range max inclusive.
     * @return array
     */
    public function Counts($Column, $From = false, $To = false, $Max = false) {
        $Result = array('Complete' => true);

        switch ($Column) {
            case 'CountArticleComments':
                $this->Database->Query(DBAModel::GetCountSQL('count', 'Article', 'ArticleComment'));
                break;
            case 'FirstArticleCommentID':
                $this->Database->Query(DBAModel::GetCountSQL('min', 'Article', 'ArticleComment', $Column));
                break;
            case 'LastArticleCommentID':
                $this->Database->Query(DBAModel::GetCountSQL('max', 'Article', 'ArticleComment', $Column));
                break;
            case 'DateLastArticleComment':
                $this->Database->Query(DBAModel::GetCountSQL('max', 'Article', 'ArticleComment', $Column, 'DateInserted'));
                $this->SQL
                    ->Update('Article')
                    ->Set('DateLastArticleComment', 'DateInserted', false, false)
                    ->Where('DateLastArticleComment', null)
                    ->Put();

                break;
            case 'LastArticleCommentUserID':
                if (!$Max) {
                    // Get the range for this update.
                    $DBAModel = new DBAModel();
                    list($Min, $Max) = $DBAModel->PrimaryKeyRange('Article');

                    if (!$From) {
                        $From = $Min;
                        $To = $Min + DBAModel::$ChunkSize - 1;
                    }
                }

                $this->SQL
                    ->Update('Article a')
                    ->Join('ArticleComment ac', 'ac.ArticleCommentID = a.LastArticleCommentID')
                    ->Set('a.LastArticleCommentUserID', 'ac.InsertUserID', false, false)
                    ->Where('a.ArticleID >=', $From)
                    ->Where('a.ArticleID <=', $To)
                    ->Put();

                $Result['Complete'] = $To >= $Max;

                $Percent = round($To * 100 / $Max);
                if ($Percent > 100 || $Result['Complete'])
                    $Result['Percent'] = '100%';
                else
                    $Result['Percent'] = $Percent . '%';

                $From = $To + 1;
                $To = $From + DBAModel::$ChunkSize - 1;
                $Result['Args']['From'] = $From;
                $Result['Args']['To'] = $To;
                $Result['Args']['Max'] = $Max;

                break;
            default:
                throw new Gdn_UserException("Unknown column $Column");
        }

        return $Result;
    }

    /**
     * Gets the data for multiple articles based on given criteria.
     *
     * @param int $Offset Number of articles to skip.
     * @param bool $Limit Max number of articles to return.
     * @param array $Wheres SQL conditions.
     *
     * @return Gdn_DataSet SQL result.
     */
    public function Get($Offset = 0, $Limit = false, $Wheres = null) {
        // Set up selection query.
        $this->SQL->Select('a.*')->From('Article a');

        // Assign up limits and offsets.
        $Limit = $Limit ? $Limit : Gdn::Config('Articles.Articles.PerPage', 12);
        $Offset = is_numeric($Offset) ? (($Offset < 0) ? 0 : $Offset) : false;

        if (($Offset !== false) && ($Limit !== false))
            $this->SQL->Limit($Limit, $Offset);

        // Handle SQL conditions for wheres.
        $this->EventArguments['Wheres'] = & $Wheres;
        $this->FireEvent('BeforeGet');

        // Handle ArticleCategoryID in Wheres clause
        $ArticleCategoryID = false;
        if (isset($Wheres['ArticleCategoryID']) && is_numeric($Wheres['ArticleCategoryID'])) {
            $ArticleCategoryID = $Wheres['ArticleCategoryID'];

            unset($Wheres['ArticleCategoryID']); // Remove ambiguous ArticleCategoryID selection
            $Wheres['a.ArticleCategoryID'] = $ArticleCategoryID; // Fully qualify ArticleCategoryID selection
        } else if (isset($Wheres['a.ArticleCategoryID']) && is_numeric($Wheres['a.ArticleCategoryID'])) {
            $ArticleCategoryID = $Wheres['a.ArticleCategoryID'];
        }

        if (is_array($Wheres))
            $this->SQL->Where($Wheres);

        // Set order of data.
        $this->SQL->OrderBy('a.DateInserted', 'desc');

        $this->JoinArticleCategoryInfo($ArticleCategoryID);

        // Fetch data.
        $Articles = $this->SQL->Get();

        Gdn::UserModel()->JoinUsers($Articles, array('InsertUserID', 'UpdateUserID', 'AttributionUserID'));

        // Prepare and fire event.
        $this->EventArguments['Data'] = $Articles;
        $this->FireEvent('AfterGet');

        return $Articles;
    }

    /**
     * Get an article by ID.
     *
     * @param int $ArticleID
     * @return bool|object
     */
    public function GetByID($ArticleID) {
        // Set up the query.
        $this->SQL->Select('a.*')
            ->From('Article a')
            ->Where('a.ArticleID', $ArticleID);

        $this->JoinArticleCategoryInfo();
        
        // Fetch data.
        $Article = $this->SQL->Get()->FirstRow();

        Gdn::UserModel()->JoinUsers($Article, array('InsertUserID', 'UpdateUserID', 'AttributionUserID'));

        return $Article;
    }

    /**
     * Get an article by ID.
     *
     * @param string $ArticleUrlCode
     * @return bool|object
     */
    public function GetByUrlCode($ArticleUrlCode) {
        // Set up the query.
        $this->SQL->Select('a.*')
            ->From('Article a')
            ->Where('a.UrlCode', $ArticleUrlCode);

        $this->JoinArticleCategoryInfo();
        
        // Fetch data.
        $Article = $this->SQL->Get()->FirstRow();

        Gdn::UserModel()->JoinUsers($Article, array('InsertUserID', 'UpdateUserID', 'AttributionUserID'));

        return $Article;
    }

    /**
     * Get articles for a user by user ID.
     *
     * @param int $UserID
     * @param int $Offset
     * @param bool|int $Limit
     * @param null|array $Wheres
     * @return bool|object
     */
    public function GetByUser($UserID, $Offset = 0, $Limit = false, $Wheres = null) {
        if (!$Wheres)
            $Wheres = array();

        $Wheres['AttributionUserID'] = $UserID;

        $Articles = $this->Get($Offset, $Limit, $Wheres);
        $this->LastArticleCount = $Articles->NumRows();

        return $Articles;
    }

    private function JoinArticleCategoryInfo($ArticleCategoryID = false) {
        $this->SQL->Select('ac.Name', '', 'ArticleCategoryName')
            ->Select('ac.UrlCode', '', 'ArticleCategoryUrlCode');

        if (is_numeric($ArticleCategoryID))
            $this->SQL->LeftJoin('ArticleCategory ac', 'ac.ArticleCategoryID = ' . $ArticleCategoryID);
        else
            $this->SQL->LeftJoin('ArticleCategory ac', 'a.ArticleCategoryID = ac.ArticleCategoryID');
    }

    /**
     * Takes a set of form data ($Form->_PostValues), validates them, and
     * inserts or updates them to the database.
     *
     * @param array $FormPostValues An associative array of $Field => $Value pairs that represent data posted
     * from the form in the $_POST or $_GET collection.
     * @param array $Settings If a custom model needs special settings in order to perform a save, they
     * would be passed in using this variable as an associative array.
     * @return unknown
     */
    public function Save($FormPostValues, $Settings = false) {
        // Define the primary key in this model's table.
        $this->DefineSchema();

        // See if a primary key value was posted and decide how to save
        $PrimaryKeyVal = val($this->PrimaryKey, $FormPostValues, false);
        $Insert = $PrimaryKeyVal === false ? true : false;
        if ($Insert) {
            $this->AddInsertFields($FormPostValues);
        } else {
            $this->AddUpdateFields($FormPostValues);
        }

        // Validate the form posted values
        if ($this->Validate($FormPostValues, $Insert) === true) {
            $Fields = $this->Validation->ValidationFields();

            // Add the activity.
            if (C('Articles.Articles.AddActivity', true))
                $this->AddActivity($Fields, $Insert);

            $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey); // Don't try to insert or update the primary key
            if ($Insert === false) {
                // Updating.
                $this->Update($Fields, array($this->PrimaryKey => $PrimaryKeyVal));
            } else {
                // Inserting.
                $PrimaryKeyVal = $this->Insert($Fields);
            }

            // Update article count for affected category and user.
            $Article = $this->GetByID($PrimaryKeyVal);
            $ArticleCategoryID = val('ArticleCategoryID', $Article, false);

            $this->UpdateArticleCount($ArticleCategoryID, $Article);
            $this->UpdateUserArticleCount(val('AttributionUserID', $Article, false));
        } else {
            $PrimaryKeyVal = false;
        }

        return $PrimaryKeyVal;
    }

    /**
     * Delete an article and its comments, then update counts accordingly.
     *
     * @param string $Where
     * @param bool $Limit
     * @param bool $ResetData
     * @return bool|Gdn_DataSet|string
     */
    public function Delete($Where = '', $Limit = false, $ResetData = false) {
        if (is_numeric($Where))
            $Where = array($this->PrimaryKey => $Where);

        $ArticleID = val($this->PrimaryKey, $Where, false);

        $ArticleToDelete = $this->GetByID($ArticleID);

        if ($ResetData)
            $Result = $this->SQL->Delete($this->Name, $Where, $Limit);
        else
            $Result = $this->SQL->NoReset()->Delete($this->Name, $Where, $Limit);

        if ($ArticleToDelete && $Result) {
            // Delete comments for this article.
            $this->SQL
                ->From('ArticleComment ac')
                ->Join('Article a', 'ac.ArticleID = a.ArticleID')
                ->Where('a.ArticleID', $ArticleID)
                ->Delete();

            // Get the newest article in the table to set the LastDateInserted and LastArticleID accordingly.
            $LastArticle = $this->SQL
                ->Select('a.*')
                ->From('Article a')
                ->OrderBy('a.ArticleID', 'desc')
                ->Limit(1)->Get()->FirstRow(DATASET_TYPE_OBJECT);

            // Update article count for affected category and user.
            $this->UpdateArticleCount($ArticleToDelete->ArticleCategoryID, $LastArticle);
            $this->UpdateUserArticleCount(val('AttributionUserID', $ArticleToDelete, false));

            // See if LastDateInserted should be the latest comment.
            $LastComment = $this->SQL
                ->Select('ac.*')
                ->From('ArticleComment ac')
                ->OrderBy('ac.ArticleCommentID', 'desc')
                ->Limit(1)->Get()->FirstRow(DATASET_TYPE_OBJECT);

            if ($LastComment && (strtotime($LastComment->DateInserted) > strtotime($LastArticle->DateInserted))) {
                $ArticleCategoryModel = new ArticleCategoryModel();

                $ArticleCategoryModel->Update(array('LastDateInserted' => $LastComment->DateInserted),
                    array('ArticleCategoryID' => $LastArticle->ArticleCategoryID), false);
            }
        }

        return $Result;
    }

    /**
     * Update related counts for an article.
     *
     * @param int $ArticleCategoryID
     * @param bool $Article
     * @return bool
     */
    public function UpdateArticleCount($ArticleCategoryID, $Article = false) {
        $ArticleID = val('ArticleID', $Article, false);

        if (!is_numeric($ArticleCategoryID) && !is_numeric($ArticleID))
            return false;

        $CategoryData = $this->SQL
            ->Select('a.ArticleID', 'count', 'CountArticles')
            ->Select('a.CountArticleComments', 'count', 'CountArticleComments')
            ->Select('a.LastArticleCommentID', '', 'LastArticleCommentID')
            ->From('Article a')
            ->Where('a.ArticleCategoryID', $ArticleCategoryID)
            ->Get()->FirstRow();

        if (!$CategoryData)
            return false;

        $CountArticles = (int)val('CountArticles', $CategoryData, 0);

        $ArticleCategoryModel = new ArticleCategoryModel();

        $Fields = array(
            'LastDateInserted' => val('DateInserted', $Article, false),
            'CountArticles' => $CountArticles,
            'LastArticleID' => $ArticleID,
            'CountArticleComments' => (int)val('CountArticleComments', $CategoryData, 0),
            'LastArticleCommentID' => (int)val('LastArticleCommentID', $CategoryData, 0)
        );

        $Wheres = array('ArticleCategoryID' => val('ArticleCategoryID', $Article, false));

        $ArticleCategoryModel->Update($Fields, $Wheres, false);
    }

    /**
     * Update a user's article count.
     *
     * @param int $UserID
     * @return bool
     */
    public function UpdateUserArticleCount($UserID) {
        if (!is_numeric($UserID))
            return false;

        $CountArticles = $this->SQL
            ->Select('a.ArticleID', 'count', 'CountArticles')
            ->From('Article a')
            ->Where('a.AttributionUserID', $UserID)
            ->Get()->Value('CountArticles', 0);

        Gdn::UserModel()->SetField($UserID, 'CountArticles', $CountArticles);
    }

    /**
     * Creates an activity post for an article.
     *
     * @param array $Fields
     * @param bool $Insert
     */
    private function AddActivity($Fields, $Insert) {
        // Current user must be logged in for an activity to be posted.
        if (!Gdn::Session()->IsValid())
            return;

        // Determine whether to add a new activity.
        if ($Insert && ($Fields['Status'] === self::STATUS_PUBLISHED)) {
            // The article is new and will be published.
            $InsertActivity = true;
        } else {
            // The article already exists.
            $CurrentArticle = Gdn::SQL()->Select('a.Status, a.DateInserted')->From('Article a')
                ->Where('a.ArticleID', $Fields['ArticleID'])->Get()->FirstRow();

            // Set $InsertActivity to true if the article wasn't published and is being changed to published status.
            $InsertActivity = ($CurrentArticle->Status !== self::STATUS_PUBLISHED)
                && ($Fields['Status'] === self::STATUS_PUBLISHED);

            // Pass the DateInserted to be used for the route of the activity.
            $Fields['DateInserted'] = $CurrentArticle->DateInserted;
        }

        if ($InsertActivity) {
            //if ($Fields['Excerpt'] != '') {
            //    $ActivityStory = Gdn_Format::To($Fields['Excerpt'], $Fields['Format']);
            //} else {
            //    $ActivityStory = SliceParagraph(Gdn_Format::PlainText($Fields['Body'], $Fields['Format']),
            //        C('Articles.Excerpt.MaxLength', 160));
            //}

            $ActivityModel = new ActivityModel();
            $Activity = array(
                'ActivityType' => 'Article',
                'ActivityUserID' => $Fields['AttributionUserID'],
                'NotifyUserID' => ActivityModel::NOTIFY_PUBLIC,
                'HeadlineFormat' => '{ActivityUserID,user} posted the "<a href="{Url,html}">{Data.Name}</a>" article.',
                //'Story' => $ActivityStory,
                'Route' => '/article/' . Gdn_Format::Date($Fields['DateInserted'], '%Y') . '/' . $Fields['UrlCode'],
                'RecordType' => 'Article',
                'RecordID' => $Fields['ArticleID'],
                'Data' => array('Name' => $Fields['Name'])
            );
            $ActivityModel->Save($Activity);
        }
    }

    /**
     * Remove the "new article" activity for an article.
     *
     * @param int $ArticleID
     */
    public function DeleteActivity($ArticleID) {
        $ActivityModel = new ActivityModel();

        $Where = array('RecordType' => 'Article', 'RecordID' => $ArticleID);
        $Activity = $ActivityModel->GetWhere($Where, 0, 1)->FirstRow();

        if ($Activity)
            $ActivityModel->Delete(val('ActivityID', $Activity, false));
    }
}
