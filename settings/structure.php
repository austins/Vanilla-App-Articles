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
$Construct->PrimaryKey('CategoryID')
    ->Column('Name', 'varchar(255)')
    ->Column('UrlCode', 'varchar(255)', false, 'unique')
    ->Column('Description', 'varchar(500)', true)
    ->Column('DateInserted', 'datetime')
    ->Column('DateUpdated', 'datetime', true)
    //->Column('CountArticles', 'int', 0)
    //->Column('LastArticleID', 'int', null)
    ->Column('InsertUserID', 'int', false, 'key')
    ->Column('UpdateUserID', 'int', true)
    //->Column('CountComments', 'int', 0)
    //->Column('LastCommentID', 'int', null)
    //->Column('LastDateInserted', 'datetime', null)
    ->Set($Explicit, $Drop);

// Construct the Article table.
$Construct->Table('Article');
$Construct->PrimaryKey('ArticleID')
    ->Column('CategoryID', 'int', false, array('key', 'index.CategoryPages'))
    ->Column('Name', 'varchar(100)', false, 'fulltext')
    ->Column('UrlCode', 'varchar(255)', false, 'unique')
    ->Column('Body', 'longtext', false, 'fulltext')
    ->Column('Excerpt', 'text', true)
    ->Column('Format', 'varchar(20)', true)
    ->Column('Status', 'varchar(20)', 'Draft') // draft; pending; published; trash
    ->Column('Tags', 'varchar(255)', true)
    ->Column('Closed', 'tinyint(1)', 0)
    ->Column('DateInserted', 'datetime', false, 'index')
    ->Column('DateUpdated', 'datetime', true)
    ->Column('AuthorUserID', 'int', false, 'key')
    ->Column('InsertUserID', 'int', false, 'key')
    ->Column('UpdateUserID', 'int', true)
    ->Column('InsertIPAddress', 'varchar(15)', true)
    ->Column('UpdateIPAddress', 'varchar(15)', true)
    ->Column('CountComments', 'int', 0)
    //->Column('FirstCommentID', 'int', true)
    //->Column('LastCommentID', 'int', true)
    //->Column('DateLastComment', 'datetime', null, array('index', 'index.CategoryPages'))
    //->Column('LastCommentUserID', 'int', true)
    ->Set($Explicit, $Drop);

// Construct the ArticleComment table.
$Construct->Table('ArticleComment');
$Construct->PrimaryKey('CommentID')
    ->Column('ArticleID', 'int', false, 'index.1')
    ->Column('Body', 'text', false, 'fulltext')
    ->Column('Format', 'varchar(20)', true)
    ->Column('DateInserted', 'datetime', null, array('index.1', 'index'))
    ->Column('DateUpdated', 'datetime', true)
    ->Column('ParentCommentID', 'int', true)
    ->Column('InsertUserID', 'int', true)
    ->Column('UpdateUserID', 'int', true)
    ->Column('InsertIPAddress', 'varchar(39)', true)
    ->Column('UpdateIPAddress', 'varchar(39)', true)
    ->Column('GuestName', 'varchar(50)', true)
    ->Column('GuestEmail', 'varchar(200)', true)
    //->Column('DateDeleted', 'datetime', true)
    //->Column('DeleteUserID', 'int', true)
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
