<?php
/**
 * ArticleComment model
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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

    // This method is needed since Gdn::userModel()->checkPermission() doesn't handle junctions yet.
    public static function canAdd($permissionArticleCategoryID = 'any', $userID = false) {
        $addPermission = 'Articles.Comments.Add';

        // If no UserID passed, check current session permission.
        if (!$userID && Gdn::session()->checkPermission($addPermission, true, 'ArticleCategory', $permissionArticleCategoryID)) {
            return true;
        }

        // UserID passed, check specific user's permission.
        $foreignKey = false;
        $foreignID = false;
        if (is_numeric($permissionArticleCategoryID)) {
            $foreignKey = 'ArticleCategoryID';
            $foreignID = $permissionArticleCategoryID;
        }

        $userPerms = Gdn::permissionModel()->getUserPermissions($userID, $addPermission,
            'ArticleCategory', 'PermissionArticleCategoryID', $foreignKey, $foreignID);

        $canAdd = false;
        foreach ($userPerms as $categoryPerms) {
            foreach ($categoryPerms as $key => $val) {
                if ($key === $addPermission && (bool)$val === true) {
                    $canAdd = true;

                    break 2;
                }
            }
        }

        return $canAdd;
    }

    /**
     * Gets the data for multiple comments based on given criteria.
     *
     * @param int $offset Number of comments to skip.
     * @param bool $limit Max number of comments to return.
     * @param array $wheres SQL conditions.
     *
     * @return Gdn_DataSet SQL result.
     */
    public function get($offset = 0, $limit = false, $wheres = null, $sortOrder = 'asc') {
        // Set up selection query.
        $this->SQL->select('ac.*')->from('ArticleComment ac');

        // Assign up limits and offsets.
        $limit = $limit ? $limit : Gdn::config('Articles.Comments.PerPage', 30);
        $offset = is_numeric($offset) ? (($offset < 0) ? 0 : $offset) : false;

        if (($offset !== false) && ($limit !== false))
            $this->SQL->limit($limit, $offset);

        // Handle SQL conditions for wheres.
        $this->EventArguments['Wheres'] = & $wheres;
        $this->fireEvent('BeforeGet');

        if (is_array($wheres))
            $this->SQL->where($wheres);

        // Set order of data.
        if (($sortOrder !== 'asc') && ($sortOrder !== 'desc'))
            $sortOrder = 'asc';

        $this->SQL->orderBy('ac.DateInserted', $sortOrder);

        // Join in article info.
        $this->SQL->select('a.Name', '', 'ArticleName')
            ->leftJoin('Article a', 'ac.ArticleID = a.ArticleID');

        // Fetch data.
        $comments = $this->SQL->get();

        $result =& $comments->result();
        $this->LastCommentCount = $comments->numRows();
        if (count($result) > 0) {
            $this->LastArticleCommentID = $result[count($result) - 1]->ArticleCommentID;
        } else {
            $this->LastArticleCommentID = null;
        }

        Gdn::userModel()->joinUsers($comments, array('InsertUserID', 'UpdateUserID'));

        // Prepare and fire event.
        $this->EventArguments['Data'] = $comments;
        $this->fireEvent('AfterGet');

        return $comments;
    }

    /**
     * Get article comment by article ID.
     *
     * @param int $articleID
     * @param int $offset
     * @param bool $limit
     * @param null|array $wheres
     * @return Gdn_DataSet
     * @throws InvalidArgumentException on invalid article ID.
     */
    public function getByArticleID($articleID, $offset = 0, $limit = false, $wheres = null) {
        if (!is_numeric($articleID))
            throw new InvalidArgumentException('The article ID must be a numeric value.');

        $wheres = array('ac.ArticleID' => $articleID);

        $comments = $this->get($offset, $limit, $wheres);

        return $comments;
    }

    /**
     * Get article comment by ID.
     *
     * @param $articleCommentID
     * @param int $offset
     * @param bool $limit
     * @param null|array $wheres
     * @return bool
     * @throws InvalidArgumentException on invalid comment ID.
     */
    public function getByID($articleCommentID, $offset = 0, $limit = false, $wheres = null) {
        if (!is_numeric($articleCommentID))
            throw new InvalidArgumentException('The comment ID must be a numeric value.');

        $wheres = array('ac.ArticleCommentID' => $articleCommentID);

        $comment = $this->get($offset, $limit, $wheres)->firstRow();

        return $comment;
    }

    /**
     * Get comments for a user.
     *
     * @param int $userID Which user to get comments for.
     * @param int $limit Max number to get.
     * @param int $offset Number to skip.
     * @return object SQL results.
     */
    public function getByUser($userID, $offset = 0, $limit = false) {
        if (!is_numeric($userID))
            throw new InvalidArgumentException('The user ID must be a numeric value.');

        $wheres = array('ac.InsertUserID' => $userID);

        $comments = $this->get($offset, $limit, $wheres);

        return $comments;
    }

    /**
     * Takes a set of form data ($Form->_PostValues), validates them, and
     * inserts or updates them to the database.
     *
     * @param array $formPostValues An associative array of $Field => $Value pairs that represent data posted
     * from the form in the $_POST or $_GET collection.
     * @param array $settings If a custom model needs special settings in order to perform a save, they
     * would be passed in using this variable as an associative array.
     * @return unknown
     */
    public function save($formPostValues, $settings = false) {
        // Define the primary key in this model's table.
        $this->defineSchema();

        // See if a primary key value was posted and decide how to save
        $primaryKeyVal = val($this->PrimaryKey, $formPostValues, false);
        $insert = $primaryKeyVal === false ? true : false;
        if ($insert) {
            $this->addInsertFields($formPostValues);
        } else {
            $this->addUpdateFields($formPostValues);
        }

        // Validate the form posted values
        if ($this->validate($formPostValues, $insert) === true) {
            $fields = $this->Validation->validationFields();

            $fields = removeKeyFromArray($fields, $this->PrimaryKey); // Don't try to insert or update the primary key
            if ($insert === false) {
                // Updating.
                $this->update($fields, array($this->PrimaryKey => $primaryKeyVal));
            } else {
                // Check for spam
                $spam = SpamModel::isSpam('Comment', $fields);
                if($spam) {
                  return SPAM;
                }
                
                // Inserting.
                $primaryKeyVal = $this->insert($fields);

                // Update comment count for affected article, category, and user.
                $comment = $this->SQL
                    ->select('ac.*')
                    ->from('ArticleComment ac')
                    ->orderBy('ac.ArticleCommentID', 'desc')
                    ->limit(1)->get()->firstRow(DATASET_TYPE_OBJECT);

                $articleModel = new ArticleModel();
                $article = $articleModel->getByID(val('ArticleID', $formPostValues, false));

                // Add the activity.
                $articleName = $article->Name;
                if (c('Articles.Comments.AddActivity', true))
                    $this->addActivity($fields, $insert, $primaryKeyVal, $articleName);

                $this->updateCommentCount($article, $comment);

                // Update user comment count if this isn't a guest comment.
                if (is_numeric($comment->InsertUserID))
                    $this->updateUserCommentCount($comment->InsertUserID);
            }
        } else {
            $primaryKeyVal = false;
        }

        return $primaryKeyVal;
    }

    /**
     * Creates an activity post for an article comment.
     *
     * @param array $fields
     * @param bool $insert
     * @param int $articleCommentID
     * @param string $articleName
     */
    private function addActivity($fields, $insert, $articleCommentID, $articleName) {
        // Current user must be logged in for an activity to be posted.
        if (!Gdn::session()->isValid())
            return;

        // Only add a new activity if the comment is new and not a threaded reply.
        if (!$insert || ($fields['ParentArticleCommentID'] > 0))
            return;

        $activityModel = new ActivityModel();
        $activity = array(
            'ActivityType' => 'ArticleComment',
            'ActivityUserID' => $fields['InsertUserID'],
            'NotifyUserID' => ActivityModel::NOTIFY_PUBLIC,
            'HeadlineFormat' => '{ActivityUserID,user} commented on the "<a href="{Url,html}">{Data.Name}</a>" article.',
            //'Story' => sliceParagraph(Gdn_Format::plainText($Fields['Body'], $Fields['Format']),
            //    c('Articles.Excerpt.MaxLength', 160)),
            'Route' => '/article/comment/' . $articleCommentID . '/#Comment_' . $articleCommentID,
            'RecordType' => 'ArticleComment',
            'RecordID' => $articleCommentID,
            'Data' => array('Name' => $articleName)
        );
        $activityModel->save($activity);
    }

    /**
     * Remove the "new article comment" activity for an article comment.
     *
     * @param int $articleCommentID
     */
    public function deleteActivity($articleCommentID) {
        $activityModel = new ActivityModel();

        $where = array('RecordType' => 'ArticleComment', 'RecordID' => $articleCommentID);
        $activity = $activityModel->getWhere($where, 0, 1)->firstRow();

        if ($activity)
            $activityModel->delete(val('ActivityID', $activity, false));
    }

    /**
     * Delete a comment.
     *
     * This is a hard delete that completely removes it from the database.
     * Events: DeleteComment.
     *
     * @param int $articleCommentID Unique ID of the comment to be deleted.
     * @param array $options Additional options for the delete.
     *
     * @returns true on successful delete; false if comment ID doesn't exist.
     */
    public function delete($articleCommentID, $options = array()) {
        $this->EventArguments['ArticleCommentID'] = & $articleCommentID;

        $comment = $this->getByID($articleCommentID);
        if (!$comment)
            return false;

        $this->fireEvent('DeleteComment');

        // Log the deletion.
        $log = val('Log', $options, 'Delete');
        LogModel::insert($log, 'ArticleComment', $comment, val('LogOptions', $options, array()));

        $this->SQL->delete('ArticleComment', array('ArticleCommentID' => $articleCommentID));

        // Update the comment count for the article.
        $article = $this->SQL->getWhere('Article', array('ArticleID' => $comment->ArticleID))->firstRow();
        $lastComment = $this->SQL
            ->select('ac.*')
            ->from('ArticleComment ac')
            ->orderBy('ac.ArticleCommentID', 'desc')
            ->where('ac.ArticleID', $article->ArticleID)
            ->limit(1)->get()->firstRow(DATASET_TYPE_OBJECT);

        $this->updateCommentCount($article, $lastComment);

        // Update the comment count for the user if this isn't a guest comment.
        $insertUserID = val('InsertUserID', $comment, false);
        if (is_numeric($insertUserID))
            $this->updateUserCommentCount($insertUserID);

        return true;
    }

    /**
     * Update comment count for a specific article.
     *
     * If a comment entity is passed as a parameter, then that comment ID
     * will be set for the article's last comment fields.
     *
     * @param mixed $article
     * @param bool|object $comment
     * @return bool
     */
    public function updateCommentCount($article, $comment = false) {
        $articleID = val('ArticleID', $article, false);

        if (!is_numeric($articleID))
            return false;

        $articleData = $this->SQL
            ->select('ac.ArticleCommentID', 'count', 'CountArticleComments')
            ->from('ArticleComment ac')
            ->where('ac.ArticleID', $articleID)
            ->get()->firstRow();

        if (!$articleData)
            return false;

        $countArticleComments = (int)val('CountArticleComments', $articleData, 0);

        $fields = array(
            'CountArticleComments' => $countArticleComments,
            'FirstArticleCommentID' => $this->SQL
                    ->select('ac.ArticleCommentID')
                    ->from('ArticleComment ac')
                    ->orderBy('ac.ArticleCommentID', 'asc')
                    ->limit(1)->get()->firstRow(DATASET_TYPE_OBJECT)->ArticleCommentID,
            'LastArticleCommentID' => val('ArticleCommentID', $comment, null),
            'DateLastArticleComment' => val('DateInserted', $comment, null),
            'LastArticleCommentUserID' => val('InsertUserID', $comment, null)
        );

        $articleModel = new ArticleModel();
        $articleModel->update($fields, array('ArticleID' => $articleID), false);

        // Update the comment counts on the article's category.
        $articleModel->updateArticleCount($article->ArticleCategoryID, $article);
    }

    /**
     * Update a user's comment count.
     *
     * @param int $userID
     * @return bool
     */
    public function updateUserCommentCount($userID) {
        if (!is_numeric($userID))
            return false;

        $countArticleComments = $this->SQL
            ->select('ac.ArticleCommentID', 'count', 'CountArticleComments')
            ->from('ArticleComment ac')
            ->where('ac.InsertUserID', $userID)
            ->get()->value('CountArticleComments', 0);

        Gdn::userModel()->setField($userID, 'CountArticleComments', $countArticleComments);
    }

    /**
     * Builds Where statements for getOffset method.
     *
     * @access protected
     * @see CommentModel::GetOffset()
     *
     * @param array $part Value from $this->_OrderBy.
     * @param object $comment
     * @param string $op Comparison operator.
     * @return array Expression and value.
     */
    protected function _whereFromOrderBy($part, $comment, $op = '') {
        if (!$op || $op == '=') {
            $op = ($part[1] == 'desc' ? '>' : '<') . $op;
        } elseif ($op == '==') {
            $op = '=';
        }
        $expr = $part[0] . ' ' . $op;
        if (preg_match('/c\.(\w*\b)/', $part[0], $matches)) {
            $field = $matches[1];
        } else {
            $field = $part[0];
        }
        $value = val($field, $comment);
        if (!$value) {
            $value = 0;
        }

        return array($expr, $value);
    }

    /**
     * Gets the offset of the specified comment in its related article.
     *
     * Events: BeforeGetOffset
     *
     * @param mixed $comment Unique ID or or a comment object for which the offset is being defined.
     * @return object SQL result.
     */
    public function getOffset($comment) {
        $this->fireEvent('BeforeGetOffset');

        if (is_numeric($comment)) {
            $comment = $this->getID($comment);
        }

        $this->SQL
            ->select('ac.ArticleCommentID', 'count', 'CountArticleComments')
            ->from('ArticleComment ac')
            ->where('ac.ArticleID', val('ArticleID', $comment));

        $this->SQL->beginWhereGroup();

        // Figure out the where clause based on the sort.
        foreach ($this->_OrderBy as $part) {
            //$Op = count($this->_OrderBy) == 1 || isset($PrevWhere) ? '=' : '';
            list($expr, $value) = $this->_whereFromOrderBy($part, $comment, '');

            if (!isset($prevWhere)) {
                $this->SQL->where($expr, $value);
            } else {
                $this->SQL->beginWhereGroup();
                $this->SQL->orWhere($prevWhere[0], $prevWhere[1]);
                $this->SQL->where($expr, $value);
                $this->SQL->endWhereGroup();
            }

            $prevWhere = $this->_whereFromOrderBy($part, $comment, '==');
        }

        $this->SQL->endWhereGroup();

        return $this->SQL
            ->get()
            ->firstRow()
            ->CountArticleComments;
    }
}