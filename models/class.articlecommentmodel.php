<?php defined('APPLICATION') or exit();
/**
 * Copyright (C) 2015  Austin S.
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
 * Handles data for article comments.
 */
class ArticleCommentModel extends Gdn_Model {
    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('ArticleComment');
    }

    /**
     * Gets the data for multiple comments based on given criteria.
     *
     * @param int $Offset Number of comments to skip.
     * @param bool $Limit Max number of comments to return.
     * @param array $Wheres SQL conditions.
     *
     * @return Gdn_DataSet SQL result.
     */
    public function Get($Offset = 0, $Limit = false, $Wheres = null, $SortOrder = 'asc') {
        // Set up selection query.
        $this->SQL->Select('ac.*')->From('ArticleComment ac');

        // Assign up limits and offsets.
        $Limit = $Limit ? $Limit : Gdn::Config('Articles.Comments.PerPage', 30);
        $Offset = is_numeric($Offset) ? (($Offset < 0) ? 0 : $Offset) : false;

        if (($Offset !== false) && ($Limit !== false))
            $this->SQL->Limit($Limit, $Offset);

        // Handle SQL conditions for wheres.
        $this->EventArguments['Wheres'] = & $Wheres;
        $this->FireEvent('BeforeGet');

        if (is_array($Wheres))
            $this->SQL->Where($Wheres);

        // Set order of data.
        if (($SortOrder !== 'asc') && ($SortOrder !== 'desc'))
            $SortOrder = 'asc';

        $this->SQL->OrderBy('ac.DateInserted', $SortOrder);

        // Join in article info.
        $this->SQL->Select('a.Name', '', 'ArticleName')
            ->LeftJoin('Article a', 'ac.ArticleID = a.ArticleID');

        // Fetch data.
        $Comments = $this->SQL->Get();

        $Result =& $Comments->Result();
        $this->LastCommentCount = $Comments->NumRows();
        if (count($Result) > 0) {
            $this->LastArticleCommentID = $Result[count($Result) - 1]->ArticleCommentID;
        } else {
            $this->LastArticleCommentID = null;
        }

        Gdn::UserModel()->JoinUsers($Comments, array('InsertUserID', 'UpdateUserID'));

        // Prepare and fire event.
        $this->EventArguments['Data'] = $Comments;
        $this->FireEvent('AfterGet');

        return $Comments;
    }

    /**
     * Get article comment by article ID.
     *
     * @param int $ArticleID
     * @param int $Offset
     * @param bool $Limit
     * @param null|array $Wheres
     * @return Gdn_DataSet
     * @throws InvalidArgumentException on invalid article ID.
     */
    public function GetByArticleID($ArticleID, $Offset = 0, $Limit = false, $Wheres = null) {
        if (!is_numeric($ArticleID))
            throw new InvalidArgumentException('The article ID must be a numeric value.');

        $Wheres = array('ac.ArticleID' => $ArticleID);

        $Comments = $this->Get($Offset, $Limit, $Wheres);

        return $Comments;
    }

    /**
     * Get article comment by ID.
     *
     * @param $ArticleCommentID
     * @param int $Offset
     * @param bool $Limit
     * @param null|array $Wheres
     * @return bool
     * @throws InvalidArgumentException on invalid comment ID.
     */
    public function GetByID($ArticleCommentID, $Offset = 0, $Limit = false, $Wheres = null) {
        if (!is_numeric($ArticleCommentID))
            throw new InvalidArgumentException('The comment ID must be a numeric value.');

        $Wheres = array('ac.ArticleCommentID' => $ArticleCommentID);

        $Comment = $this->Get($Offset, $Limit, $Wheres)->FirstRow();

        return $Comment;
    }

    /**
     * Get comments for a user.
     *
     * @param int $UserID Which user to get comments for.
     * @param int $Limit Max number to get.
     * @param int $Offset Number to skip.
     * @param int $LastCommentID A hint for quicker paging.
     * @return object SQL results.
     */
    public function GetByUser($UserID, $Offset = 0, $Limit = false) {
        if (!is_numeric($UserID))
            throw new InvalidArgumentException('The user ID must be a numeric value.');

        $Wheres = array('ac.InsertUserID' => $UserID);

        $Comments = $this->Get($Offset, $Limit, $Wheres);

        return $Comments;
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

            $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey); // Don't try to insert or update the primary key
            if ($Insert === false) {
                // Updating.
                $this->Update($Fields, array($this->PrimaryKey => $PrimaryKeyVal));
            } else {
                // Check for spam
                $Spam = SpamModel::IsSpam('Comment', $Fields);
                if($Spam) {
                  return SPAM;
                }
                
                // Inserting.
                $PrimaryKeyVal = $this->Insert($Fields);

                // Update comment count for affected article, category, and user.
                $Comment = $this->SQL
                    ->Select('ac.*')
                    ->From('ArticleComment ac')
                    ->OrderBy('ac.ArticleCommentID', 'desc')
                    ->Limit(1)->Get()->FirstRow(DATASET_TYPE_OBJECT);

                $ArticleModel = new ArticleModel();
                $Article = $ArticleModel->GetByID(val('ArticleID', $FormPostValues, false));

                // Add the activity.
                $ArticleName = $Article->Name;
                if (C('Articles.Comments.AddActivity', true))
                    $this->AddActivity($Fields, $Insert, $PrimaryKeyVal, $ArticleName);

                $this->UpdateCommentCount($Article, $Comment);

                // Update user comment count if this isn't a guest comment.
                if (is_numeric($Comment->InsertUserID))
                    $this->UpdateUserCommentCount($Comment->InsertUserID);
            }
        } else {
            $PrimaryKeyVal = false;
        }

        return $PrimaryKeyVal;
    }

    /**
     * Creates an activity post for an article comment.
     *
     * @param array $Fields
     * @param bool $Insert
     * @param int $ArticleCommentID
     * @param string $ArticleName
     */
    private function AddActivity($Fields, $Insert, $ArticleCommentID, $ArticleName) {
        // Current user must be logged in for an activity to be posted.
        if (!Gdn::Session()->IsValid())
            return;

        // Only add a new activity if the comment is new and not a threaded reply.
        if (!$Insert || ($Fields['ParentArticleCommentID'] > 0))
            return;

        $ActivityModel = new ActivityModel();
        $Activity = array(
            'ActivityType' => 'ArticleComment',
            'ActivityUserID' => $Fields['InsertUserID'],
            'NotifyUserID' => ActivityModel::NOTIFY_PUBLIC,
            'HeadlineFormat' => '{ActivityUserID,user} commented on the "<a href="{Url,html}">{Data.Name}</a>" article.',
            //'Story' => SliceParagraph(Gdn_Format::PlainText($Fields['Body'], $Fields['Format']),
            //    C('Articles.Excerpt.MaxLength', 160)),
            'Route' => '/article/comment/' . $ArticleCommentID . '/#Comment_' . $ArticleCommentID,
            'RecordType' => 'ArticleComment',
            'RecordID' => $ArticleCommentID,
            'Data' => array('Name' => $ArticleName)
        );
        $ActivityModel->Save($Activity);
    }

    /**
     * Remove the "new article comment" activity for an article comment.
     *
     * @param int $ArticleCommentID
     */
    public function DeleteActivity($ArticleCommentID) {
        $ActivityModel = new ActivityModel();

        $Where = array('RecordType' => 'ArticleComment', 'RecordID' => $ArticleCommentID);
        $Activity = $ActivityModel->GetWhere($Where, 0, 1)->FirstRow();

        if ($Activity)
            $ActivityModel->Delete(val('ActivityID', $Activity, false));
    }

    /**
     * Delete a comment.
     *
     * This is a hard delete that completely removes it from the database.
     * Events: DeleteComment.
     *
     * @param int $ArticleCommentID Unique ID of the comment to be deleted.
     * @param array $Options Additional options for the delete.
     *
     * @returns true on successful delete; false if comment ID doesn't exist.
     */
    public function Delete($ArticleCommentID, $Options = array()) {
        $this->EventArguments['ArticleCommentID'] = & $ArticleCommentID;

        $Comment = $this->GetByID($ArticleCommentID);
        if (!$Comment)
            return false;

        $this->FireEvent('DeleteComment');

        // Log the deletion.
        $Log = val('Log', $Options, 'Delete');
        LogModel::Insert($Log, 'ArticleComment', $Comment, val('LogOptions', $Options, array()));

        $this->SQL->Delete('ArticleComment', array('ArticleCommentID' => $ArticleCommentID));

        // Update the comment count for the article.
        $Article = $this->SQL->GetWhere('Article', array('ArticleID' => $Comment->ArticleID))->FirstRow();
        $LastComment = $this->SQL
            ->Select('ac.*')
            ->From('ArticleComment ac')
            ->OrderBy('ac.ArticleCommentID', 'desc')
            ->Where('ac.ArticleID', $Article->ArticleID)
            ->Limit(1)->Get()->FirstRow(DATASET_TYPE_OBJECT);

        $this->UpdateCommentCount($Article, $LastComment);

        // Update the comment count for the user if this isn't a guest comment.
        $InsertUserID = val('InsertUserID', $Comment, false);
        if (is_numeric($InsertUserID))
            $this->UpdateUserCommentCount($InsertUserID);

        return true;
    }

    /**
     * Update comment count for a specific article.
     *
     * If a comment entity is passed as a parameter, then that comment ID
     * will be set for the article's last comment fields.
     *
     * @param mixed $Article
     * @param bool|object $Comment
     * @return bool
     */
    public function UpdateCommentCount($Article, $Comment = false) {
        $ArticleID = val('ArticleID', $Article, false);

        if (!is_numeric($ArticleID))
            return false;

        $ArticleData = $this->SQL
            ->Select('ac.ArticleCommentID', 'count', 'CountArticleComments')
            ->From('ArticleComment ac')
            ->Where('ac.ArticleID', $ArticleID)
            ->Get()->FirstRow();

        if (!$ArticleData)
            return false;

        $CountArticleComments = (int)val('CountArticleComments', $ArticleData, 0);

        $Fields = array(
            'CountArticleComments' => $CountArticleComments,
            'FirstArticleCommentID' => $this->SQL
                    ->Select('ac.ArticleCommentID')
                    ->From('ArticleComment ac')
                    ->OrderBy('ac.ArticleCommentID', 'asc')
                    ->Limit(1)->Get()->FirstRow(DATASET_TYPE_OBJECT)->ArticleCommentID,
            'LastArticleCommentID' => val('ArticleCommentID', $Comment, null),
            'DateLastArticleComment' => val('DateInserted', $Comment, null),
            'LastArticleCommentUserID' => val('InsertUserID', $Comment, null)
        );

        $ArticleModel = new ArticleModel();
        $ArticleModel->Update($Fields, array('ArticleID' => $ArticleID), false);

        // Update the comment counts on the article's category.
        $ArticleModel->UpdateArticleCount($Article->ArticleCategoryID, $Article);
    }

    /**
     * Update a user's comment count.
     *
     * @param int $UserID
     * @return bool
     */
    public function UpdateUserCommentCount($UserID) {
        if (!is_numeric($UserID))
            return false;

        $CountArticleComments = $this->SQL
            ->Select('ac.ArticleCommentID', 'count', 'CountArticleComments')
            ->From('ArticleComment ac')
            ->Where('ac.InsertUserID', $UserID)
            ->Get()->Value('CountArticleComments', 0);

        Gdn::UserModel()->SetField($UserID, 'CountArticleComments', $CountArticleComments);
    }

    /**
     * Gets the offset of the specified comment in its related article.
     *
     * Events: BeforeGetOffset
     *
     * @param mixed $Comment Unique ID or or a comment object for which the offset is being defined.
     * @return object SQL result.
     */
    public function GetOffset($Comment) {
        $this->FireEvent('BeforeGetOffset');

        if (is_numeric($Comment)) {
            $Comment = $this->GetID($Comment);
        }

        $this->SQL
            ->Select('ac.ArticleCommentID', 'count', 'CountArticleComments')
            ->From('ArticleComment ac')
            ->Where('ac.ArticleID', val('ArticleID', $Comment));

        $this->SQL->BeginWhereGroup();

        // Figure out the where clause based on the sort.
        foreach ($this->_OrderBy as $Part) {
            //$Op = count($this->_OrderBy) == 1 || isset($PrevWhere) ? '=' : '';
            list($Expr, $Value) = $this->_WhereFromOrderBy($Part, $Comment, '');

            if (!isset($PrevWhere)) {
                $this->SQL->Where($Expr, $Value);
            } else {
                $this->SQL->BeginWhereGroup();
                $this->SQL->OrWhere($PrevWhere[0], $PrevWhere[1]);
                $this->SQL->Where($Expr, $Value);
                $this->SQL->EndWhereGroup();
            }

            $PrevWhere = $this->_WhereFromOrderBy($Part, $Comment, '==');
        }

        $this->SQL->EndWhereGroup();

        return $this->SQL
            ->Get()
            ->FirstRow()
            ->CountArticleComments;
    }
}