<?php
if (!defined('APPLICATION'))
    exit();

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
    public function Get($Offset = 0, $Limit = false, $Wheres = null) {
        // Set up selection query.
        $this->SQL->Select('c.*')->From('ArticleComment c');

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
        $this->SQL->OrderBy('c.DateInserted', 'asc');

        // Fetch data.
        $Comments = $this->SQL->Get();

        // Prepare and fire event.
        $this->EventArguments['Data'] = $Comments;
        $this->FireEvent('AfterGet');

        return $Comments;
    }

    public function GetByArticleID($ArticleID, $Offset = 0, $Limit = false, $Wheres = null)
    {
        if (!is_numeric($ArticleID))
            throw new InvalidArgumentException('The article ID must be a numeric value.');

        $Wheres = array('c.ArticleID', $ArticleID);

        $Comments = $this->Get($Offset, $Limit, $Wheres);

        return $Comments;
    }

    public function GetByID($CommentID, $Offset = 0, $Limit = false, $Wheres = null)
    {
        if (!is_numeric($CommentID))
            throw new InvalidArgumentException('The comment ID must be a numeric value.');

        $Wheres = array('c.CommentID', $CommentID);

        $Comment = $this->Get($Offset, $Limit, $Wheres)->FirstRow();

        return $Comment;
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
        $PrimaryKeyVal = GetValue($this->PrimaryKey, $FormPostValues, false);
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
                // Inserting.
                $PrimaryKeyVal = $this->Insert($Fields);

                // Update comment count for affected article, category, and user.
                $Comment = $this->SQL
                    ->Select('ac.*')
                    ->From('ArticleComment ac')
                    ->OrderBy('ac.CommentID', 'desc')
                    ->Limit(1)->Get()->FirstRow(DATASET_TYPE_OBJECT);

                $ArticleModel = new ArticleModel();
                $Article = $ArticleModel->GetByID(GetValue('ArticleID', $FormPostValues, false));

                $this->UpdateCommentCount($Article, $Comment);
                $this->UpdateUserCommentCount($Comment->InsertUserID);
            }
        } else {
            $PrimaryKeyVal = false;
        }

        return $PrimaryKeyVal;
    }

    /**
     * Delete a comment.
     *
     * This is a hard delete that completely removes it from the database.
     * Events: DeleteComment.
     *
     * @param int $CommentID Unique ID of the comment to be deleted.
     * @param array $Options Additional options for the delete.
     *
     * @returns true on successful delete; false if comment ID doesn't exist.
     */
    public function Delete($CommentID, $Options = array()) {
        $this->EventArguments['CommentID'] = &$CommentID;

        $Comment = $this->GetByID($CommentID);
        if (!$Comment)
            return false;

        $this->FireEvent('DeleteComment');

        // Log the deletion.
        $Log = GetValue('Log', $Options, 'Delete');
        LogModel::Insert($Log, 'ArticleComment', $Comment, GetValue('LogOptions', $Options, array()));

        // Delete the comment.
        $this->SQL->Delete('ArticleComment', array('CommentID' => $CommentID));

        // Update the comment count for the article.
        $Article = $this->SQL->GetWhere('Article', array('ArticleId' => $Comment->ArticleID))->FirstRow();
        $LastComment = $this->SQL
            ->Select('ac.*')
            ->From('ArticleComment ac')
            ->OrderBy('ac.CommentID', 'desc')
            ->Where('ac.ArticleID', $Article->ArticleID)
            ->Limit(1)->Get()->FirstRow(DATASET_TYPE_OBJECT);

        $this->UpdateCommentCount($Article, $LastComment);

        // Update the comment count for the user.
        $this->UpdateUserCommentCount(GetValue('InsertUserID', $Comment, false));

        // TODO: Add logic in either controller or in this method to handle deletion of child comments and guest comments.

        return true;
    }

    public function UpdateCommentCount($Article, $Comment = false) {
        $ArticleID = GetValue('ArticleID', $Article, false);

        if (!is_numeric($ArticleID))
            return false;

        $ArticleData = $this->SQL
            ->Select('ac.CommentID', 'count', 'CountComments')
            ->From('ArticleComment ac')
            ->Where('ac.ArticleID', $ArticleID)
            ->Get()->FirstRow();

        if (!$ArticleData)
            return false;

        $CountComments = (int)GetValue('CountComments', $ArticleData, 0);

        $Fields = array(
            'CountComments' => $CountComments,
            'FirstCommentID' => $this->SQL
                                    ->Select('ac.CommentID')
                                    ->From('ArticleComment ac')
                                    ->OrderBy('ac.CommentID', 'asc')
                                    ->Limit(1)->Get()->FirstRow(DATASET_TYPE_OBJECT)->CommentID,
            'LastCommentID' => $Comment->CommentID,
            'DateLastComment' => $Comment->DateInserted,
            'LastCommentUserID' => $Comment->InsertUserID
        );

        $ArticleModel = new ArticleModel();
        $ArticleModel->Update($Fields, array('ArticleID' => $ArticleID), false);

        // Update the comment counts on the article's category.
        $ArticleModel->UpdateArticleCount($Article->CategoryID, $Article);
    }

    public function UpdateUserCommentCount($UserID) {
        if (!is_numeric($UserID))
            return false;

        $CountComments = $this->SQL
            ->Select('ac.CommentID', 'count', 'CountComments')
            ->From('ArticleComment ac')
            ->Where('ac.InsertUserID', $UserID)
            ->Get()->Value('CountComments', 0);

        Gdn::UserModel()->SetField($UserID, 'CountArticleComments', $CountComments);
    }
}