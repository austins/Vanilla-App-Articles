<?php
if (!defined('APPLICATION'))
    exit();

/**
 * Handles data for articles.
 */
class ArticleModel extends Gdn_Model {
    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('Article');
    }

    const STATUS_DRAFT = 'Draft';
    const STATUS_PENDING = 'Pending';
    const STATUS_PUBLISHED = 'Published';

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

        if (is_array($Wheres))
            $this->SQL->Where($Wheres);

        // Set order of data.
        $this->SQL->OrderBy('a.DateInserted', 'desc');

        // Join in the author data
        $this->SQL->Select('u.Name as InsertName, u.Email as InsertEmail, u.Photo as InsertPhoto')->Join('User u', 'u.UserID = a.InsertUserID');
        
        // Fetch data.
        $Articles = $this->SQL->Get();

        // Prepare and fire event.
        $this->EventArguments['Data'] = $Articles;
        $this->FireEvent('AfterGet');

        return $Articles;
    }

    public function GetByID($ArticleID) {
        // Set up the query.
        $this->SQL->Select('a.*')
            ->From('Article a')
            ->Where('a.ArticleID', $ArticleID);
        
        // Join in the author data
        $this->SQL->Select('u.Name as AuthorName, u.Email as AuthorEmail, u.Photo as AuthorPhoto')->Join('User u', 'u.UserID = a.AuthorUserID');
        
        // Fetch data.
        $Article = $this->SQL->Get()->FirstRow();

        return $Article;
    }

    public function GetByUrlCode($ArticleUrlCode) {
        // Set up the query.
        $this->SQL->Select('a.*')
            ->From('Article a')
            ->Where('a.UrlCode', $ArticleUrlCode);

        // Join in the author data
        $this->SQL->Select('u.Name as AuthorName, u.Email as AuthorEmail, u.Photo as AuthorPhoto')->Join('User u', 'u.UserID = a.AuthorUserID');
        
        // Fetch data.
        $Article = $this->SQL->Get()->FirstRow();

        return $Article;
    }

    // TODO: Update delete method to recalculate counts, remove related article material, etc.
    public function Delete($Where = '', $Limit = false, $ResetData = false) {
        if (is_numeric($Where))
            $Where = array($this->PrimaryKey => $Where);

        if ($ResetData)
            $Result = $this->SQL->Delete($this->Name, $Where, $Limit);
        else
            $Result = $this->SQL->NoReset()->Delete($this->Name, $Where, $Limit);

        return $Result;
    }

    public function GetByUser($UserID, $Offset = 0, $Limit = false, $Wheres = null) {
        if (!$Wheres)
            $Wheres = array();

        $Wheres['AuthorUserID'] = $UserID;

        $Articles = $this->Get($Offset, $Limit, $Wheres);
        $this->LastArticleCount = $Articles->NumRows();

        return $Articles;
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
                $this->Update($Fields, array($this->PrimaryKey => $PrimaryKeyVal));
            } else {
                $PrimaryKeyVal = $this->Insert($Fields);
            }

            $this->AddActivity($Fields, $Insert); // Add the activity.
        } else {
            $PrimaryKeyVal = false;
        }

        return $PrimaryKeyVal;
    }

    private function AddActivity($Fields, $Insert) {
        // Determine whether to add a new activity.
        if ($Insert && ($Fields['Status'] === self::STATUS_PUBLISHED)) {
            // The article is new and will be published.
            $InsertActivity = true;
        } else {
            // The article already exists.
            $CurrentStatus = $this->SQL->Select('a.Status')->From('Article a')
                ->Where('a.ArticleID', $Fields['ArticleID'])->Get()->FirstRow()->Status;

            // Set $InsertActivity to true if the article wasn't published and is being changed to published status.
            $InsertActivity = ($CurrentStatus !== self::STATUS_PUBLISHED)
                && ($Fields['Status'] === self::STATUS_PUBLISHED);
        }

        if ($InsertActivity) {
            if ($Fields['Excerpt'] != '') {
                $ActivityStory = Gdn_Format::To($Fields['Excerpt'], $Fields['Format']);
            } else {
                $ActivityStory = SliceParagraph(Gdn_Format::PlainText($Fields['Body'], $Fields['Format']),
                    C('Articles.Excerpt.MaxLength'));
            }

            $ActivityModel = new ActivityModel();
            $Activity = array(
                'ActivityType' => 'Article',
                'ActivityUserID' => $Fields['AuthorUserID'],
                'NotifyUserID' => ActivityModel::NOTIFY_PUBLIC,
                'HeadlineFormat' => '{ActivityUserID,user} published "<a href="{Url,html}">{Data.Name}</a>".',
                'Story' => $ActivityStory,
                'Route' => rawurlencode(ArticleUrl($Fields)),
                'Data' => array('Name' => $Fields['Name'])
            );
            $ActivityModel->Save($Activity);
        }
    }
}
