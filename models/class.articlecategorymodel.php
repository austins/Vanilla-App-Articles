<?php
/**
 * ArticleCategory model
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Handles data for article categories.
 */
class ArticleCategoryModel extends Gdn_Model {
    /** Cache grace. */
    const CACHE_GRACE = 60;
    /** Cache key. */
    const CACHE_KEY = 'ArticleCategories';
    /** Cache time to live. */
    const CACHE_TTL = 600;
    /** Cache master vote key. */
    const MASTER_VOTE_KEY = 'ArticleCategories.Rebuild.Vote';

    /** @var array Merged ArticleCategory data */
    public static $Categories = null;

    /** @var bool Whether or not to explicitly shard the categories cache. */
    public static $ShardCache = false;

    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('ArticleCategory');
    }

    /**
     * Gets either all of the ArticleCategories or a single ArticleCategory.
     *
     * @param int|string|bool $ID Either the ArticleCategoryID or the ArticleCategory url code.
     *
     * @return array Returns either one or all ArticleCategories (if nothing is passed then all categories are
     *     returned).
     */
    public static function categories($ID = false) {
        if (self::$Categories == null) {
            // Try and get the ArticleCategories from the cache.
            $categoriesCache = Gdn::cache()->get(self::CACHE_KEY);
            $rebuild = true;

            // If we received a valid data structure, extract the embedded expiry
            // and re-store the real ArticleCategories on our static property.
            if (is_array($categoriesCache)) {
                // Test if it's time to rebuild
                $rebuildAfter = val('expiry', $categoriesCache, null);
                if (!is_null($rebuildAfter) && time() < $rebuildAfter) {
                    $rebuild = false;
                }
                self::$Categories = val('categories', $categoriesCache, null);
            }
            unset($categoriesCache);

            if ($rebuild) {
                // Try to get a rebuild lock
                $haveRebuildLock = self::rebuildLock();
                if ($haveRebuildLock || !self::$Categories) {
                    $sql = Gdn::sql();
                    $sql = clone $sql;
                    $sql->reset();

                    $sql->select('ac.*')
                        ->from('ArticleCategory ac')
                        ->where('ac.ArticleCategoryID <>', '-1')
                        ->orderBy('ac.Name', 'asc');

                    self::$Categories = array_merge(array(), $sql->get()->resultArray());
                    self::$Categories = Gdn_DataSet::index(self::$Categories, 'ArticleCategoryID');
                    self::buildCache();

                    // Release lock
                    if ($haveRebuildLock) {
                        self::rebuildLock(true);
                    }
                }
            }

            if (self::$Categories) {
                self::joinUserData(self::$Categories);
            } else {
                return null;
            }
        }

        if ($ID !== false) {
            if (!is_numeric($ID) && $ID) {
                $code = $ID;
                foreach (self::$Categories as $category) {
                    if (strcasecmp($category['UrlCode'], $code) === 0) {
                        $ID = $category['ArticleCategoryID'];
                        break;
                    }
                }
            }

            if (isset(self::$Categories[$ID])) {
                $result = self::$Categories[$ID];

                return $result;
            } else {
                return null;
            }
        } else {
            $result = self::$Categories;

            return $result;
        }
    }

    /**
     * Update &$Categories in memory by applying modifiers for the currently logged-in user.
     *
     * @param array &$categories
     */
    public static function joinUserData(&$categories) {
        $IDs = array_keys($categories);

        // Add permissions.
        $session = Gdn::session();
        foreach ($IDs as $cID) {
            $category = $categories[$cID];

            $categories[$cID]['PermsArticlesView'] = $session->checkPermission('Articles.Articles.View', true,
                'ArticleCategory', $category['PermissionArticleCategoryID']);
            $categories[$cID]['PermsArticlesAdd'] = $session->checkPermission('Articles.Articles.Add', true,
                'ArticleCategory', $category['PermissionArticleCategoryID']);
            $categories[$cID]['PermsArticlesEdit'] = $session->checkPermission('Articles.Articles.Edit', true,
                'ArticleCategory', $category['PermissionArticleCategoryID']);
            $categories[$cID]['PermsCommentsAdd'] = $session->checkPermission('Articles.Comments.Add', true,
                'ArticleCategory', $category['PermissionArticleCategoryID']);
        }
    }

    public static function joinCategories(&$data, $column = 'ArticleCategoryID', $options = array()) {
        $join = val('Join', $options,
            array('Name' => 'ArticleCategoryName', 'UrlCode' => 'ArticleCategoryUrlCode', 'PermissionArticleCategoryID'));

        if ($data instanceof Gdn_DataSet) {
            $data2 = $data->result();
        } else {
            $data2 =& $data;
        }

        foreach ($data2 as &$row) {
            $ID = val($column, $row);
            $category = self::categories($ID);
            foreach ($join as $n => $v) {
                if (is_numeric($n)) {
                    $n = $v;
                }

                if ($category) {
                    $value = $category[$n];
                } else {
                    $value = null;
                }

                setValue($v, $row, $value);
            }
        }
    }

    /**
     * Request rebuild mutex
     *
     * Allows competing instances to "vote" on the process that gets to rebuild
     * the ArticleCategory cache.
     *
     * @return boolean whether we may rebuild
     */
    protected static function rebuildLock($release = false) {
        static $isMaster = null;
        if ($release) {
            Gdn::cache()->remove(self::MASTER_VOTE_KEY);

            return;
        }

        if (is_null($isMaster)) {
            // Vote for master
            $instanceKey = getmypid();
            $masterKey = Gdn::cache()->add(self::MASTER_VOTE_KEY, $instanceKey, array(
                Gdn_Cache::FEATURE_EXPIRY => self::CACHE_GRACE
            ));

            $isMaster = ($instanceKey == $masterKey);
        }

        return (bool)$isMaster;
    }

    /**
     * Build and augment the ArticleCategory cache
     */
    protected static function buildCache() {
        $expiry = self::CACHE_TTL + self::CACHE_GRACE;
        Gdn::cache()->store(self::CACHE_KEY, array(
            'expiry' => time() + $expiry,
            'categories' => self::$Categories
        ), array(
            Gdn_Cache::FEATURE_EXPIRY => $expiry,
            Gdn_Cache::FEATURE_SHARD => self::$ShardCache
        ));
    }

    public static function clearCache() {
        Gdn::cache()->remove(self::CACHE_KEY);
    }

    /**
     * Grab and update the category cache
     *
     * @param int $ID
     * @param array $data
     */
    public static function setCache($ID = false, $data = false) {
        $categories = Gdn::cache()->get(self::CACHE_KEY);
        self::$Categories = null;

        if (!$categories) {
            return;
        }

        // Extract actual category list, remove key if malformed
        if (!$ID || !is_array($categories) || !array_key_exists('categories', $categories)) {
            Gdn::cache()->remove(self::CACHE_KEY);
            return;
        }
        $categories = $categories['categories'];

        // Check for category in list, otherwise remove key if not found
        if (!array_key_exists($ID, $categories)) {
            Gdn::cache()->remove(self::CACHE_KEY);
            return;
        }

        $category = $categories[$ID];
        $category = array_merge($category, $data);
        $categories[$ID] = $category;

        // Update memcache entry
        self::$Categories = $categories;
        unset($categories);
        self::buildCache();

        self::joinUserData(self::$Categories);
    }

    /**
     * Count recalculation. Called by DBAModel->Counts().
     *
     * @param string $column
     * @return array
     */
    public function counts($column) {
        $result = array('Complete' => true);

        switch ($column) {
            case 'CountArticles':
                $this->Database->query(DBAModel::getCountSQL('count', 'ArticleCategory', 'Article'));
                break;
            case 'CountArticleComments':
                $this->Database->query(DBAModel::getCountSQL('sum', 'ArticleCategory', 'Article', $column,
                    'CountArticleComments'));
                break;
            case 'LastArticleID':
                $this->Database->query(DBAModel::getCountSQL('max', 'ArticleCategory', 'Article'));
                break;
            case 'LastArticleCommentID':
                $data = $this->SQL
                    ->select('a.ArticleCategoryID')
                    ->select('ac.ArticleCommentID', 'max', 'LastArticleCommentID')
                    ->select('a.ArticleID', 'max', 'LastArticleID')
                    ->select('ac.DateInserted', 'max', 'DateLastArticleComment')
                    ->select('a.DateInserted', 'max', 'DateLastArticle')
                    ->from('ArticleComment ac')
                    ->join('Article a', 'a.ArticleID = ac.ArticleID')
                    ->groupBy('a.ArticleCategoryID')
                    ->get()->resultArray();

                // Now we have to grab the articles associated with these comments.
                $articleCommentIDs = consolidateArrayValuesByKey($data, 'LastArticleCommentID');

                // Grab the articles for the comments.
                $this->SQL
                    ->select('ac.ArticleCommentID, ac.ArticleID')
                    ->from('ArticleComment ac')
                    ->whereIn('ac.ArticleCommentID', $articleCommentIDs);

                $articles = $this->SQL->get()->resultArray();
                $articles = Gdn_DataSet::index($articles, array('ArticleCommentID'));

                foreach ($data as $row) {
                    $articleCategoryID = (int)$row['ArticleCategoryID'];
                    $category = $this->getByID($articleCategoryID);
                    $articleCommentID = $row['LastArticleCommentID'];
                    $articleID = getValueR("$articleCommentID.ArticleID", $articles, null);

                    $dateLastArticleComment = Gdn_Format::toTimestamp($row['DateLastArticleComment']);
                    $dateLastArticle = Gdn_Format::toTimestamp($row['DateLastArticle']);

                    $set = array('LastArticleCommentID' => $articleCommentID);

                    if ($articleID) {
                        $lastArticleID = val('LastArticleID', $category);

                        if ($dateLastArticleComment >= $dateLastArticle) {
                            // The most recent article is from this comment.
                            $set['LastArticleID'] = $articleID;
                        } else {
                            // The most recent discussion has no comments.
                            $set['LastArticleCommentID'] = null;
                        }
                    } else {
                        // Something went wrong.
                        $set['LastArticleCommentID'] = null;
                        $set['LastArticleID'] = null;
                    }

                    $this->setField($articleCategoryID, $set);
                }

                break;
            case 'LastDateInserted':
                $categories = $this->SQL
                    ->select('ca.ArticleCategoryID')
                    ->select('a.DateInserted', '', 'DateLastArticle')
                    ->select('ac.DateInserted', '', 'DateLastArticleComment')
                    ->from('ArticleCategory ca')
                    ->join('Article a', 'a.ArticleID = ca.LastArticleID')
                    ->join('ArticleComment ac', 'ac.ArticleCommentID = ca.LastArticleCommentID')
                    ->get()->resultArray();

                foreach ($categories as $category) {
                    $dateLastArticle = val('DateLastArticle', $category);
                    $dateLastArticleComment = val('DateLastArticleComment', $category);

                    $maxDate = $dateLastArticleComment;
                    if (is_null($dateLastArticleComment) || $dateLastArticle > $maxDate) {
                        $maxDate = $dateLastArticle;
                    }

                    if (is_null($maxDate)) {
                        continue;
                    }

                    $articleCategoryID = (int)$category['ArticleCategoryID'];
                    $this->setField($articleCategoryID, 'LastDateInserted', $maxDate);
                }

                break;
        }

        self::clearCache();

        return $result;
    }

    /**
     * Gets multiple ArticleCategories based on given criteria (respecting user permission).
     *
     * @param array $wheres SQL conditions.
     *
     * @return Gdn_DataSet SQL result.
     */
    public function get($wheres = null, $permFilter = 'Articles.Articles.View') {
        // Set up selection query.
        $this->SQL->select('ac.*')->from('ArticleCategory ac');

        // Handle SQL conditions for wheres.
        $this->EventArguments['Wheres'] = &$wheres;
        $this->fireEvent('BeforeGet');

        if (is_array($wheres)) {
            $this->SQL->where($wheres);
        }

        // Exclude root category
        $this->SQL->where('ArticleCategoryID <>', '-1');

        // Respect user permission
        $this->SQL->beginWhereGroup()
            ->permission($permFilter, 'ac', 'PermissionArticleCategoryID', 'ArticleCategory')
            ->endWhereGroup();

        // Set order of data.
        $this->SQL->orderBy('ac.Name', 'asc');

        // Fetch data.
        $categories = $this->SQL->get();

        // Prepare and fire event.
        $this->EventArguments['Data'] = $categories;
        $this->fireEvent('AfterGet');

        return $categories;
    }

    /**
     * Delete a single category and assign its articles to another.
     *
     * @return void
     * @throws Exception on invalid category for deletion.
     * @param object $category
     * @param int $replacementArticleCategoryID Unique ID of category all discussion are being move to.
     */
    public function delete($category, $replacementArticleCategoryID) {
        // Don't do anything if the required category object & properties are not defined.
        if (!is_object($category)
            || !property_exists($category, 'ArticleCategoryID')
            || !property_exists($category, 'Name')
            || $category->ArticleCategoryID <= 0
        ) {
            throw new Exception(t('Invalid category for deletion.'));
        } else {
            // If there is a replacement category...
            if ($replacementArticleCategoryID > 0) {
                // Update articles.
                $this->SQL
                    ->update('Article')
                    ->set('ArticleCategoryID', $replacementArticleCategoryID)
                    ->where('ArticleCategoryID', $category->ArticleCategoryID)
                    ->put();

                // Update the article count.
                $count = $this->SQL
                    ->select('ArticleID', 'count', 'ArticleCount')
                    ->from('Article')
                    ->where('ArticleCategoryID', $replacementArticleCategoryID)
                    ->get()
                    ->firstRow()
                    ->ArticleCount;

                if (!is_numeric($count)) {
                    $count = 0;
                }

                $this->SQL
                    ->update('ArticleCategory')->set('CountArticles', $count)
                    ->where('ArticleCategoryID', $replacementArticleCategoryID)
                    ->put();
            } else {
                // Delete comments in this category.
                $this->SQL
                    ->from('ArticleComment ac')
                    ->join('Article a', 'ac.ArticleID = a.ArticleID')
                    ->where('a.ArticleID', $category->ArticleCategoryID)
                    ->delete();

                // Delete articles in this category.
                $this->SQL->delete('Article', array('ArticleCategoryID' => $category->ArticleCategoryID));
            }

            // Finally, delete the category.
            $this->SQL->delete('ArticleCategory', array('ArticleCategoryID' => $category->ArticleCategoryID));
        }
    }

    /**
     * Saves the category.
     *
     * @param array $formPostValues The values being posted back from the form.
     * @return int ID of the saved category.
     */
    public function save($formPostValues) {
        // Define the primary key in this model's table.
        $this->defineSchema();

        // Get data from form
        $articleCategoryID = arrayValue('ArticleCategoryID', $formPostValues);
        $newName = arrayValue('Name', $formPostValues, '');
        $urlCode = arrayValue('UrlCode', $formPostValues, '');
        $customPermissions = (bool)val('CustomPermissions', $formPostValues);

        // Is this a new category?
        $insert = $articleCategoryID > 0 ? false : true;
        if ($insert) {
            $this->addInsertFields($formPostValues);
        }

        $this->addUpdateFields($formPostValues);
        $this->Validation->applyRule('UrlCode', 'Required');
        $this->Validation->applyRule('UrlCode', 'UrlStringRelaxed');

        // Make sure that the UrlCode is unique among categories.
        $this->SQL->select('ArticleCategoryID')
            ->from('ArticleCategory')
            ->where('UrlCode', $urlCode);

        if ($articleCategoryID) {
            $this->SQL->where('ArticleCategoryID <>', $articleCategoryID);
        }

        if ($this->SQL->get()->numRows()) {
            $this->Validation->addValidationResult('UrlCode',
                'The specified URL code is already in use by another article category.');
        }

        //	Prep and fire event.
        $this->EventArguments['FormPostValues'] = &$formPostValues;
        $this->EventArguments['ArticleCategoryID'] = $articleCategoryID;
        $this->fireEvent('BeforeSaveArticleCategory');

        // Validate the form posted values
        if ($this->validate($formPostValues, $insert)) {
            $fields = $this->Validation->schemaValidationFields();
            $fields = removeKeyFromArray($fields, 'ArticleCategoryID');

            if ($insert === false) {
                $oldCategory = $this->getID($articleCategoryID, DATASET_TYPE_ARRAY);

                $this->update($fields, array('ArticleCategoryID' => $articleCategoryID));

                $this->setCache($articleCategoryID, $fields);
            } else {
                $articleCategoryID = $this->insert($fields);

                if ($articleCategoryID) {
                    if ($customPermissions) {
                        $this->SQL->put('ArticleCategory', array('PermissionArticleCategoryID' => $articleCategoryID),
                            array('ArticleCategoryID' => $articleCategoryID));
                    }
                }
            }

            // Save the permissions
            if ($articleCategoryID) {
                // Check to see if this category uses custom permissions.
                if ($customPermissions) {
                    $permissionModel = Gdn::permissionModel();
                    $permissions = $permissionModel->pivotPermissions(val('Permission', $formPostValues, array()),
                        array('JunctionID' => $articleCategoryID));
                    $permissionModel->saveAll($permissions,
                        array('JunctionID' => $articleCategoryID, 'JunctionTable' => 'ArticleCategory'));

                    if (!$insert) {
                        // Update this category's permission.
                        $this->SQL->put('ArticleCategory', array('PermissionArticleCategoryID' => $articleCategoryID),
                            array('ArticleCategoryID' => $articleCategoryID));
                    }
                } elseif (!$insert) {
                    // Delete my custom permissions.
                    $this->SQL->delete(
                        'Permission',
                        array('JunctionTable' => 'ArticleCategory', 'JunctionColumn' => 'PermissionArticleCategoryID', 'JunctionID' => $articleCategoryID)
                    );

                    // Update this category's permission.
                    $this->SQL->put('ArticleCategory', array('PermissionArticleCategoryID' => -1),
                        array('ArticleCategoryID' => $articleCategoryID));
                }

                self::clearCache();
            }

            // Force the user permissions to refresh.
            Gdn::userModel()->clearPermissions();
        } else {
            $articleCategoryID = false;
        }

        return $articleCategoryID;
    }

    /**
     * Update a row in the database.
     *
     * @param int $rowID
     * @param array|string $property
     * @param mixed $value
     */
    public function setField($rowID, $property, $value = false) {
        parent::setField($rowID, $property, $value);

        // Set the cache.
        self::setCache($rowID, $property);
    }

    /**
     * Update fields of rows in the database.
     *
     * @param array $fields
     * @param array $where
     * @param array $limit
     * @return Gdn_Dataset
     */
    public function update($fields, $where = false, $limit = false) {
        parent::update($fields, $where, $limit);

        // Clear the cache.
        self::clearCache();
    }

    /**
     * Get article category by ID.
     *
     * @param int $articleCategoryID
     * @return bool|object
     */
    public function getByID($articleCategoryID) {
        // Set up the query.
        $this->SQL->select('ac.*')
            ->from('ArticleCategory ac')
            ->where('ac.ArticleCategoryID', $articleCategoryID);

        // Fetch data.
        $category = $this->SQL->get()->firstRow();

        return $category;
    }

    /**
     * Get article category by URL code.
     *
     * @param string $categoryUrlCode
     * @return bool|object
     */
    public function getByUrlCode($categoryUrlCode) {
        // Set up the query.
        $this->SQL->select('ac.*')
            ->from('ArticleCategory ac')
            ->where('ac.UrlCode', $categoryUrlCode);

        // Fetch data.
        $category = $this->SQL->get()->firstRow();

        return $category;
    }

    /**
     * Determines and sets the most recent post fields
     * for a specific article category ID.
     *
     * @param int $articleCategoryID
     */
    public function setRecentPost($articleCategoryID) {
        $row = $this->SQL
            ->getWhere('Article', array('ArticleCategoryID' => $articleCategoryID), 'DateLastArticleComment', 'desc', 1)
            ->firstRow(DATASET_TYPE_ARRAY);

        $fields = array('LastArticleCommentID' => null, 'LastArticleID' => null);

        if ($row) {
            $fields['LastArticleCommentID'] = $row['LastArticleCommentID'];
            $fields['LastArticleID'] = $row['ArticleID'];
        }

        $this->setField($articleCategoryID, $fields);
    }
}
