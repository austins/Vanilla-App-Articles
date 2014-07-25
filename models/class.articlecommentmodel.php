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

        $Article = $this->SQL->GetWhere('Article', array('ArticleId' => $Comment->ArticleID))->FirstRow();

        // TODO: Update the comment count

        // TODO: Update the user's comment count

        // TODO: Update the category count and last comment info.

        return true;
    }
}