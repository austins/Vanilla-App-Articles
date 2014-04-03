<?php if(!defined('APPLICATION')) exit();

/**
 * Set up the Articles database structure.
 *
 * Called by ArticleHooks->Setup() to update database upon enabling app.
 */
$Construct = $Database->Structure();
$Px = $Construct->DatabasePrefix();

// Construct the ArticleCategory table.
$Construct->Table('ArticleCategory');
$Construct->PrimaryKey('CategoryID')
    ->Column('Name', 'varchar(255)')
    ->Column('UrlCode', 'varchar(255)', TRUE)
    ->Column('Description', 'varchar(500)', TRUE)
    ->Column('DateInserted', 'datetime')
    ->Column('DateUpdated', 'datetime')
    ->Column('CountArticles', 'int', '0')
    ->Column('LastArticleID', 'int', NULL)
    ->Column('InsertUserID', 'int', FALSE, 'key')
    ->Column('UpdateUserID', 'int', TRUE)
    ->Column('CountComments', 'int', '0')
    ->Column('LastCommentID', 'int', NULL)
    ->Column('LastDateInserted', 'datetime', NULL)
    ->Set($Explicit, $Drop);

// Construct the Article table.
$Construct->Table('Article');
$Construct->PrimaryKey('ArticleID')
    ->Column('CategoryID', 'int', FALSE, array('key', 'index.CategoryPages'))
    ->Column('Name', 'varchar(100)', FALSE, 'fulltext')
    ->Column('UrlCode', 'varchar(255)', TRUE)
    ->Column('Body', 'longtext', FALSE, 'fulltext')
    ->Column('Excerpt', 'text', TRUE)
    ->Column('Format', 'varchar(20)', TRUE)
    ->Column('Status', 'varchar(20)', 'draft') // draft; pending; published; trash
    ->Column('Tags', 'varchar(255)', TRUE)
    ->Column('Closed', 'tinyint(1)', '0')
    ->Column('DateInserted', 'datetime', FALSE, 'index')
    ->Column('DateUpdated', 'datetime', TRUE)
    ->Column('AuthorUserID', 'int', FALSE, 'key')
    ->Column('InsertUserID', 'int', FALSE, 'key')
    ->Column('UpdateUserID', 'int', TRUE)
    ->Column('InsertIPAddress', 'varchar(15)', TRUE)
    ->Column('UpdateIPAddress', 'varchar(15)', TRUE)
    ->Column('CountComments', 'int', '0')
    ->Column('FirstCommentID', 'int', TRUE)
    ->Column('LastCommentID', 'int', TRUE)
    ->Column('DateLastComment', 'datetime', NULL, array('index', 'index.CategoryPages'))
    ->Column('LastCommentUserID', 'int', TRUE)
    ->Set($Explicit, $Drop);

// Set up permissions.
$PermissionModel = Gdn::PermissionModel();
$PermissionModel->Database = $Database;
$PermissionModel->SQL = $SQL;

// Define some global permissions.
$PermissionModel->Define(array(
    'Articles.Articles.Add' => 0,
    'Articles.Articles.Close' => 0,
    'Articles.Articles.Delete' => 0,
    'Articles.Articles.Edit' => 0,
    'Articles.Articles.View' => 1,
    'Articles.Comments.Add' => 1,
    'Articles.Comments.Delete' => 0,
    'Articles.Comments.Edit' => 0
));

// Set initial role permissions for the Administrator role.
$PermissionModel->Save(array(
    'Role' => 'Administrator',
    'Articles.Articles.Add' => 1,
    'Articles.Articles.Close' => 1,
    'Articles.Articles.Delete' => 1,
    'Articles.Articles.Edit' => 1,
    'Articles.Comments.Delete' => 1,
    'Articles.Comments.Edit' => 1
));
