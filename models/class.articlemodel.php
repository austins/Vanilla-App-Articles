<?php
/**
 * Article model
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Handles data for articles.
 */
class ArticleModel extends Gdn_Model {
    const STATUS_DRAFT = 'Draft';
    const STATUS_PENDING = 'Pending';
    const STATUS_PUBLISHED = 'Published';

    /** @var array */
    protected static $_ArticleCategoryPermissions = null;

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('Article');
    }

    // This method is needed since Gdn::userModel()->checkPermission() doesn't handle junctions yet.
    public static function canAdd($permissionArticleCategoryID = 'any', $userID = false) {
        $addPermission = 'Articles.Articles.Add';

        // SystemUser doesn't have any assigned roles by default, so if UserID is SystemUserID, then return true.
        // If no UserID passed, check current session permission.
        if (($userID && $userID == Gdn::userModel()->getSystemUserID())
                || (!$userID && Gdn::session()->checkPermission($addPermission, true, 'ArticleCategory', $permissionArticleCategoryID))
        ) {
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
     * Determines whether or not the current user can edit an article.
     *
     * @param object|array $article The article to examine.
     *
     * @return bool Returns true if the user can edit or false otherwise.
     */
    public static function canEdit($article) {
        if (!($permissionArticleCategoryID = val('PermissionArticleCategoryID', $article))) {
            $articleCategoryModel = new ArticleCategoryModel();
            $articleCategory = $articleCategoryModel->getByID(val('ArticleCategoryID', $article));
            $permissionArticleCategoryID = val('PermissionArticleCategoryID', $articleCategory);
        }

        // Users with category edit permission can edit.
        if (Gdn::session()
            ->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', $permissionArticleCategoryID)
        ) {
            return true;
        }

        // Author can edit article even if they don't have edit permission.
        if (Gdn::session()->UserID == val('InsertUserID', $article)) {
            return true;
        }

        return false;
    }

    /**
     * Identify current user's ArticleCategory permissions and set as local array.
     *
     * @param bool $escape Prepends category IDs with @
     * @return array Protected local _CategoryPermissions
     */
    public static function articleCategoryPermissions($escape = false) {
        if (is_null(self::$_ArticleCategoryPermissions)) {
            $session = Gdn::session();

            if ((is_object($session->User) && $session->User->Admin)) {
                self::$_ArticleCategoryPermissions = true;
            } else {
                $categories = ArticleCategoryModel::categories();
                $IDs = array();

                foreach ($categories as $ID => $category) {
                    if ($category['PermsArticlesView']) {
                        $IDs[] = $ID;
                    }
                }

                // Check to see if the user has permission to all categories. This is for speed.
                $categoryCount = count($categories);

                if (count($IDs) === $categoryCount) {
                    self::$_ArticleCategoryPermissions = true;
                } else {
                    self::$_ArticleCategoryPermissions = array();
                    foreach ($IDs as $ID) {
                        self::$_ArticleCategoryPermissions[] = ($escape ? '@' : '') . $ID;
                    }
                }
            }
        }

        return self::$_ArticleCategoryPermissions;
    }

    /**
     * Count recalculation. Called by DBAModel->Counts().
     *
     * @param string $column
     * @param int $from ID range begin inclusive.
     * @param int $to ID range end inclusive.
     * @param int $max ID range max inclusive.
     * @return array
     */
    public function counts($column, $from = false, $to = false, $max = false) {
        $result = array('Complete' => true);

        switch ($column) {
            case 'CountArticleComments':
                $this->Database->query(DBAModel::getCountSQL('count', 'Article', 'ArticleComment'));
                break;
            case 'FirstArticleCommentID':
                $this->Database->query(DBAModel::getCountSQL('min', 'Article', 'ArticleComment', $column));
                break;
            case 'LastArticleCommentID':
                $this->Database->query(DBAModel::getCountSQL('max', 'Article', 'ArticleComment', $column));
                break;
            case 'DateLastArticleComment':
                $this->Database->query(DBAModel::getCountSQL('max', 'Article', 'ArticleComment', $column,
                    'DateInserted'));
                $this->SQL
                    ->update('Article')
                    ->set('DateLastArticleComment', 'DateInserted', false, false)
                    ->where('DateLastArticleComment', null)
                    ->put();

                break;
            case 'LastArticleCommentUserID':
                if (!$max) {
                    // Get the range for this update.
                    $dbaModel = new DBAModel();
                    list($min, $max) = $dbaModel->primaryKeyRange('Article');

                    if (!$from) {
                        $from = $min;
                        $to = $min + DBAModel::$ChunkSize - 1;
                    }
                }

                $this->SQL
                    ->update('Article a')
                    ->join('ArticleComment ac', 'ac.ArticleCommentID = a.LastArticleCommentID')
                    ->set('a.LastArticleCommentUserID', 'ac.InsertUserID', false, false)
                    ->where('a.ArticleID >=', $from)
                    ->where('a.ArticleID <=', $to)
                    ->put();

                $result['Complete'] = $to >= $max;

                $percent = round($to * 100 / $max);
                if ($percent > 100 || $result['Complete']) {
                    $result['Percent'] = '100%';
                } else {
                    $result['Percent'] = $percent . '%';
                }

                $from = $to + 1;
                $to = $from + DBAModel::$ChunkSize - 1;
                $result['Args']['From'] = $from;
                $result['Args']['To'] = $to;
                $result['Args']['Max'] = $max;

                break;
            default:
                throw new Gdn_UserException("Unknown column $column");
        }

        return $result;
    }

    /**
     * Gets the data for multiple articles based on given criteria.
     *
     * @param int $offset Number of articles to skip.
     * @param bool $limit Max number of articles to return.
     * @param array $wheres SQL conditions.
     * @param string $orderByField Field to order results by.
     * @param string $orderByDirection Direction to order results by (asc/desc).
     *
     * @return Gdn_DataSet SQL result.
     */
    public function get($offset = 0, $limit = false, $wheres = null,
                        $orderByField = 'a.DateInserted', $orderByDirection = 'desc') {
        $perms = self::articleCategoryPermissions();
        if (is_array($perms) && empty($perms)) {
            return new Gdn_DataSet(array());
        }

        // Set up selection query.
        $this->SQL->select('a.*')->from('Article a');

        // Assign up limits and offsets.
        $limit = $limit ? $limit : Gdn::config('Articles.Articles.PerPage', 12);
        $offset = is_numeric($offset) ? (($offset < 0) ? 0 : $offset) : false;

        if (($offset !== false) && ($limit !== false)) {
            $this->SQL->limit($limit, $offset);
        }

        // Handle SQL conditions for wheres.
        $this->EventArguments['Wheres'] = &$wheres;
        $this->fireEvent('BeforeGet');

        // Handle ArticleCategoryID in Wheres clause
        $articleCategoryID = false;
        if (isset($wheres['ArticleCategoryID']) && is_numeric($wheres['ArticleCategoryID'])) {
            $articleCategoryID = $wheres['ArticleCategoryID'];

            unset($wheres['ArticleCategoryID']); // Remove ambiguous ArticleCategoryID selection
            $wheres['a.ArticleCategoryID'] = $articleCategoryID; // Fully qualify ArticleCategoryID selection
        } else if (isset($wheres['a.ArticleCategoryID']) && is_numeric($wheres['a.ArticleCategoryID'])) {
            $articleCategoryID = $wheres['a.ArticleCategoryID'];
        }

        if (is_array($wheres)) {
            $this->SQL->where($wheres);
        }

        // Set order of data.
        $this->SQL->orderBy($orderByField, $orderByDirection);

        // Fetch data.
        $articles = $this->SQL->get();
        $result =& $articles->result();

        // Now that we have the articles, we can filter out the ones we don't have permission to.
        if ($perms !== true) {
            $remove = array();

            foreach ($articles->result() as $index => $row) {
                if (!in_array($row->ArticleCategoryID, $perms)) {
                    $remove[] = $index;
                }
            }

            if (count($remove) > 0) {
                foreach ($remove as $index) {
                    unset($result[$index]);
                }
                $result = array_values($result);
            }
        }

        Gdn::userModel()->joinUsers($articles, array('InsertUserID', 'UpdateUserID'));
        ArticleCategoryModel::joinCategories($articles);

        // Prepare and fire event.
        $this->EventArguments['Data'] = $articles;
        $this->fireEvent('AfterGet');

        return $articles;
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

            // Add the activity.
            if (c('Articles.Articles.AddActivity', false)) {
                $this->addActivity($fields, $insert);
            }

            $fields = removeKeyFromArray($fields, $this->PrimaryKey); // Don't try to insert or update the primary key
            if ($insert === false) {
                // Updating.
                $this->update($fields, array($this->PrimaryKey => $primaryKeyVal));
            } else {
                // Inserting.
                $primaryKeyVal = $this->insert($fields);
            }

            // Update article count for affected category and user.
            $article = $this->getByID($primaryKeyVal);
            $articleCategoryID = val('ArticleCategoryID', $article, false);

            $this->updateArticleCount($articleCategoryID, $article);
            $this->updateUserArticleCount(val('InsertUserID', $article, false));
        } else {
            $primaryKeyVal = false;
        }

        return $primaryKeyVal;
    }

    /**
     * Delete an article and its comments, then update counts accordingly.
     *
     * @param string $where
     * @param bool $limit
     * @param bool $resetData
     * @return bool|Gdn_DataSet|string
     */
    public function delete($where = '', $limit = false, $resetData = false) {
        if (is_numeric($where)) {
            $where = array($this->PrimaryKey => $where);
        }

        $articleID = val($this->PrimaryKey, $where, false);

        $articleToDelete = $this->getByID($articleID);

        if ($resetData) {
            $result = $this->SQL->delete($this->Name, $where, $limit);
        } else {
            $result = $this->SQL->noReset()->delete($this->Name, $where, $limit);
        }

        if ($articleToDelete && $result) {
            // Delete comments for this article.
            $this->SQL
                ->from('ArticleComment ac')
                ->join('Article a', 'ac.ArticleID = a.ArticleID')
                ->where('a.ArticleID', $articleID)
                ->delete();

            // Get the newest article in the table to set the LastDateInserted and LastArticleID accordingly.
            $lastArticle = $this->SQL
                ->select('a.*')
                ->from('Article a')
                ->orderBy('a.ArticleID', 'desc')
                ->limit(1)->get()->firstRow(DATASET_TYPE_OBJECT);

            // Update article count for affected category and user.
            $this->updateArticleCount($articleToDelete->ArticleCategoryID, $lastArticle);
            $this->updateUserArticleCount(val('InsertUserID', $articleToDelete, false));

            // See if LastDateInserted should be the latest comment.
            $lastComment = $this->SQL
                ->select('ac.*')
                ->from('ArticleComment ac')
                ->orderBy('ac.ArticleCommentID', 'desc')
                ->limit(1)->get()->firstRow(DATASET_TYPE_OBJECT);

            if ($lastComment && (strtotime($lastComment->DateInserted) > strtotime($lastArticle->DateInserted))) {
                $articleCategoryModel = new ArticleCategoryModel();

                $articleCategoryModel->update(array('LastDateInserted' => $lastComment->DateInserted),
                    array('ArticleCategoryID' => $lastArticle->ArticleCategoryID), false);
            }
        }

        return $result;
    }

    /**
     * Get an article by ID.
     *
     * @param int $articleID
     * @return bool|object
     */
    public function getByID($articleID) {
        // Set up the query.
        $this->SQL->select('a.*')
            ->from('Article a')
            ->where('a.ArticleID', $articleID);

        // Fetch data.
        $article = $this->SQL->get()->firstRow();

        // Join in the users and category.
        $article = array($article);
        Gdn::userModel()->joinUsers($article, array('InsertUserID', 'UpdateUserID'));
        ArticleCategoryModel::joinCategories($article);
        $article = $article[0];

        return $article;
    }

    /**
     * Get an article by ID.
     *
     * @param string $articleUrlCode
     * @return bool|object
     */
    public function getByUrlCode($articleUrlCode) {
        // Set up the query.
        $this->SQL->select('a.*')
            ->from('Article a')
            ->where('a.UrlCode', $articleUrlCode);

        // Fetch data.
        $article = $this->SQL->get()->firstRow();

        // Join in the users and category.
        $article = array($article);
        Gdn::userModel()->joinUsers($article, array('InsertUserID', 'UpdateUserID'));
        ArticleCategoryModel::joinCategories($article);
        $article = $article[0];

        return $article;
    }

    /**
     * Get articles for a user by user ID.
     *
     * @param int $userID
     * @param int $offset
     * @param bool|int $limit
     * @param null|array $wheres
     * @return bool|object
     */
    public function getByUser($userID, $offset = 0, $limit = false, $wheres = array()) {
        $wheres['InsertUserID'] = $userID;

        $articles = $this->get($offset, $limit, $wheres);
        $this->LastArticleCount = $articles->numRows();

        return $articles;
    }

    /**
     * Update related counts for an article.
     *
     * @param int $articleCategoryID
     * @param bool $article
     * @return bool
     */
    public function updateArticleCount($articleCategoryID, $article = false) {
        $articleID = val('ArticleID', $article, false);

        if (!is_numeric($articleCategoryID) && !is_numeric($articleID)) {
            return false;
        }

        $categoryData = $this->SQL
            ->select('a.ArticleID', 'count', 'CountArticles')
            ->select('a.CountArticleComments', 'count', 'CountArticleComments')
            ->select('a.LastArticleCommentID', '', 'LastArticleCommentID')
            ->from('Article a')
            ->where('a.ArticleCategoryID', $articleCategoryID)
            ->get()->firstRow();

        if (!$categoryData) {
            return false;
        }

        $countArticles = (int)val('CountArticles', $categoryData, 0);

        $articleCategoryModel = new ArticleCategoryModel();

        $fields = array(
            'LastDateInserted' => val('DateInserted', $article, false),
            'CountArticles' => $countArticles,
            'LastArticleID' => $articleID,
            'CountArticleComments' => (int)val('CountArticleComments', $categoryData, 0),
            'LastArticleCommentID' => (int)val('LastArticleCommentID', $categoryData, 0)
        );

        $wheres = array('ArticleCategoryID' => val('ArticleCategoryID', $article, false));

        $articleCategoryModel->update($fields, $wheres, false);
    }

    /**
     * Update a user's article count.
     *
     * @param int $userID
     * @return bool
     */
    public function updateUserArticleCount($userID) {
        if (!is_numeric($userID)) {
            return false;
        }

        $countArticles = $this->SQL
            ->select('a.ArticleID', 'count', 'CountArticles')
            ->from('Article a')
            ->where('a.InsertUserID', $userID)
            ->get()->value('CountArticles', 0);

        Gdn::userModel()->setField($userID, 'CountArticles', $countArticles);
    }

    /**
     * Remove the "new article" activity for an article.
     *
     * @param int $articleID
     */
    public function deleteActivity($articleID) {
        $activityModel = new ActivityModel();

        $where = array('RecordType' => 'Article', 'RecordID' => $articleID);
        $activity = $activityModel->getWhere($where, 0, 1)->firstRow();

        if ($activity) {
            $activityModel->delete(val('ActivityID', $activity, false));
        }
    }

    public function getSimilarArticles($articleID, $articleCategoryID) {
        if (!is_numeric($articleID) || !is_numeric($articleCategoryID)) {
            throw new InvalidArgumentException('The article ID and article category ID must be a numeric value.');
        }

//        $ArticleCategoryModel = new ArticleCategoryModel();
//        $Category = $ArticleCategoryModel->getByID($ArticleCategoryID);
//        if (!$Category)
//            return false;

        // Get articles from the same category, excluding the current article
        $numberOfSimilarArticles = 4;

        $wheres = array(
            'ArticleID <>' => $articleID,
            'ArticleCategoryID' => $articleCategoryID,
            'Status' => self::STATUS_PUBLISHED
        );

        // Retrieve articles from DB in random order
        $articles = $this->get(0, $numberOfSimilarArticles, $wheres, 'RAND()', '');

        // Try to retrieve articles from other categories instead if articles
        // retrieved in same category is less than the number to list
        $articleCategoryModel = new ArticleCategoryModel();
        $categoriesCount = $articleCategoryModel->getCount(array('CountArticles >' => '0')); // Prevent unnecessary select.

        if (($articles->numRows() < $numberOfSimilarArticles) && ($categoriesCount > 1)) {
            unset($wheres['ArticleCategoryID']);

            $articles = $this->get(0, $numberOfSimilarArticles, $wheres, 'RAND()', '');
        }

        return $articles;
    }

    //

    /**
     * Creates an activity post for an article.
     *
     * @param array $fields
     * @param bool $insert
     */
    private function addActivity($fields, $insert) {
        // Current user must be logged in for an activity to be posted.
        if (!Gdn::session()->isValid()) {
            return;
        }

        // Determine whether to add a new activity.
        if ($insert && ($fields['Status'] === self::STATUS_PUBLISHED)) {
            // The article is new and will be published.
            $insertActivity = true;
        } else {
            // The article already exists.
            $currentArticle = Gdn::sql()->select('a.Status, a.DateInserted')->from('Article a')
                ->where('a.ArticleID', $fields['ArticleID'])->get()->firstRow();

            // Set $InsertActivity to true if the article wasn't published and is being changed to published status.
            $insertActivity = ($currentArticle->Status !== self::STATUS_PUBLISHED)
                && ($fields['Status'] === self::STATUS_PUBLISHED);

            // Pass the DateInserted to be used for the route of the activity.
            $fields['DateInserted'] = $currentArticle->DateInserted;
        }

        if ($insertActivity) {
            //if ($Fields['Excerpt'] != '') {
            //    $ActivityStory = Gdn_Format::to($Fields['Excerpt'], $Fields['Format']);
            //} else {
            //    $ActivityStory = sliceParagraph(Gdn_Format::plainText($Fields['Body'], $Fields['Format']),
            //        c('Articles.Excerpt.MaxLength', 160));
            //}

            $activityModel = new ActivityModel();
            $activity = array(
                'ActivityType' => 'Article',
                'ActivityUserID' => $fields['InsertUserID'],
                'NotifyUserID' => ActivityModel::NOTIFY_PUBLIC,
                'HeadlineFormat' => '{ActivityUserID,user} posted the "<a href="{Url,html}">{Data.Name}</a>" article.',
                //'Story' => $ActivityStory,
                'Route' => '/article/' . Gdn_Format::date($fields['DateInserted'], '%Y') . '/' . $fields['UrlCode'],
                'RecordType' => 'Article',
                'RecordID' => $fields['ArticleID'],
                'Data' => array('Name' => $fields['Name'])
            );
            $activityModel->save($activity);
        }
    }
}
