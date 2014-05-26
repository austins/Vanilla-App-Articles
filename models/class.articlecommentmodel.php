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
        if (!$Limit)
            $Limit = Gdn::Config('Articles.Comments.PerPage', 30);

        $Offset = !is_numeric($Offset) || ($Offset < 0 ? 0 : $Offset);

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
}