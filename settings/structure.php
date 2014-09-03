<?php
if (!defined('APPLICATION'))
    exit();

/*
 * Set up the Articles database structure.
 *
 * Called by ArticleHooks->Setup() to update database upon enabling app.
 */
$Construct = $Database->Structure();
$Px = $Construct->DatabasePrefix();

// Construct the ArticleCategory table.
$Construct->Table('ArticleCategory');
$Construct->PrimaryKey('ArticleCategoryID')
    ->Column('Name', 'varchar(255)')
    ->Column('UrlCode', 'varchar(255)', false, 'unique')
    ->Column('Description', 'varchar(500)', true)
    ->Column('DateInserted', 'datetime')
    ->Column('DateUpdated', 'datetime', true)
    ->Column('InsertUserID', 'int', false, 'key')
    ->Column('UpdateUserID', 'int', true)
    ->Column('LastDateInserted', 'datetime', null)
    ->Column('CountArticles', 'int', 0)
    ->Column('LastArticleID', 'int', null)
    ->Column('CountArticleComments', 'int', 0)
    ->Column('LastArticleCommentID', 'int', null)
    ->Set($Explicit, $Drop);

// Construct the Article table.
$Construct->Table('Article');
$Construct->PrimaryKey('ArticleID')
    ->Column('ArticleCategoryID', 'int', false, array('key', 'index.CategoryPages'))
    ->Column('Name', 'varchar(100)', false, 'fulltext')
    ->Column('UrlCode', 'varchar(255)', false, 'unique')
    ->Column('Body', 'longtext', false, 'fulltext')
    ->Column('Excerpt', 'text', true)
    ->Column('Format', 'varchar(20)', true)
    ->Column('Status', 'varchar(20)', 'Draft') // Draft; Pending; Published; Trash
    //->Column('Tags', 'varchar(255)', true)
    ->Column('Closed', 'tinyint(1)', 0)
    ->Column('DateInserted', 'datetime', false, 'index')
    ->Column('DateUpdated', 'datetime', true)
    ->Column('AttributionUserID', 'int', false, 'key')
    ->Column('InsertUserID', 'int', false, 'key')
    ->Column('UpdateUserID', 'int', true)
    ->Column('InsertIPAddress', 'varchar(15)', true)
    ->Column('UpdateIPAddress', 'varchar(15)', true)
    ->Column('CountArticleComments', 'int', 0)
    ->Column('FirstArticleCommentID', 'int', true)
    ->Column('LastArticleCommentID', 'int', true)
    ->Column('DateLastArticleComment', 'datetime', null, array('index', 'index.CategoryPages'))
    ->Column('LastArticleCommentUserID', 'int', true)
    ->Set($Explicit, $Drop);

// Construct the ArticleComment table.
$Construct->Table('ArticleComment');
$Construct->PrimaryKey('ArticleCommentID')
    ->Column('ArticleID', 'int', false, 'index.1')
    ->Column('Body', 'text', false, 'fulltext')
    ->Column('Format', 'varchar(20)', true)
    ->Column('DateInserted', 'datetime', false, array('index.1', 'index'))
    ->Column('DateUpdated', 'datetime', true)
    ->Column('ParentArticleCommentID', 'int', true)
    ->Column('InsertUserID', 'int', true)
    ->Column('UpdateUserID', 'int', true)
    ->Column('InsertIPAddress', 'varchar(39)', true)
    ->Column('UpdateIPAddress', 'varchar(39)', true)
    ->Column('GuestName', 'varchar(50)', true)
    ->Column('GuestEmail', 'varchar(200)', true)
    ->Set($Explicit, $Drop);

// Add extra columns to user table for tracking articles and comments.
$Construct->Table('User')
    ->Column('CountArticles', 'int', 0)
    ->Column('CountArticleComments', 'int', 0)
    ->Set(false, false);

// Construct the ArticleMedia table.
$Construct->Table('ArticleMedia');
$Construct->PrimaryKey('ArticleMediaID')
    ->Column('ArticleID', 'int(11)', true)
    ->Column('Name', 'varchar(255)')
    ->Column('Path', 'varchar(255)')
    ->Column('Type', 'varchar(128)')
    ->Column('Size', 'int(11)')
    ->Column('ImageWidth', 'usmallint', null)
    ->Column('ImageHeight', 'usmallint', null)
    ->Column('StorageMethod', 'varchar(24)', 'local')
    ->Column('DateInserted', 'datetime')
    ->Column('InsertUserID', 'int(11)')
    ->Set($Explicit, $Drop);

/*
 * Set up permissions.
 */
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

// Set the initial guest permissions.
$PermissionModel->Save(array(
    'Role' => 'Guest',
    'Articles.Articles.View' => 1,
    'Articles.Comments.Add' => 0
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
