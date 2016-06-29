<?php defined('APPLICATION') or exit();
/**
 * Copyright (C) 2015  Austin S.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/*
 * Set up the Articles database structure.
 *
 * Called by ArticleHooks->Setup() to update database upon enabling app.
 */
$Database = Gdn::Database();
$SQL = $Database->SQL();
$Construct = $Database->Structure();
$Px = $Construct->DatabasePrefix();

$Drop = false;
$Explicit = true;

// Construct the ArticleCategory table.
$Construct->Table('ArticleCategory');
$ArticleCategoryExists = $Construct->TableExists();
$PermissionArticleCategoryIDExists = $Construct->ColumnExists('PermissionArticleCategoryID');
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
    ->Column('Sort', 'int', true)
    ->Column('PermissionArticleCategoryID', 'int', '-1') // Default to root category
    ->Set($Explicit, $Drop);

$SystemUserID = Gdn::UserModel()->GetSystemUserID();
$Now = Gdn_Format::ToDateTime();

if ($SQL->GetWhere('ArticleCategory', array('ArticleCategoryID' => -1))->NumRows() == 0) {
    // Insert root article category for use with permissions.
    $SQL->Insert('ArticleCategory', array(
        'ArticleCategoryID' => -1,
        'Name' => 'Root',
        'UrlCode' => '',
        'Description' => 'Root of article category tree. Users should never see this.',
        'DateInserted' => $Now,
        'InsertUserID' => $SystemUserID,
        'PermissionArticleCategoryID' => -1));
}

if ($Drop || !$ArticleCategoryExists) {
    // Insert first article category.
    $SQL->Insert('ArticleCategory', array(
        'Name' => 'General',
        'UrlCode' => 'general',
        'Description' => 'Uncategorized articles.',
        'DateInserted' => $Now,
        'InsertUserID' => $SystemUserID,
        'LastDateInserted' => $Now,
        'CountArticles' => 1,
        'LastArticleID' => 1,
        'PermissionArticleCategoryID' => -1
    ));
} elseif ($ArticleCategoryExists && !$PermissionArticleCategoryIDExists) {
    // Existing installations need to be set up with per/ArticleCategory permissions.
    $SQL->Update('ArticleCategory')->Set('PermissionArticleCategoryID', -1, false)->Put();
    $SQL->Update('Permission')->Set('JunctionColumn', 'PermissionArticleCategoryID')->Where('JunctionColumn', 'ArticleCategoryID')->Put();
}

// Construct the Article table.
$Construct->Table('Article');
$ArticleExists = $Construct->TableExists();

$AttributionUserIDExists = $Construct->ColumnExists('AttributionUserID');
if ($ArticleExists && $AttributionUserIDExists) {
    $AttributionUserIDNotSameCount = $SQL->Query('SELECT COUNT(CASE WHEN AttributionUserID != InsertUserID'
        . ' THEN 1 ELSE NULL END) AS NotSameCount FROM ' . $Px . 'Article;')->FirstRow()->NotSameCount;

    if ($AttributionUserIDNotSameCount > 0) {
        $SQL->Update('Article a')->Set('a.InsertUserID', 'a.AttributionUserID', false, false)->Put();
    }
}

$Construct->PrimaryKey('ArticleID')
    ->Column('ArticleCategoryID', 'int', false, array('key', 'index.CategoryPages'))
    ->Column('Name', 'varchar(100)', false, 'fulltext')
    ->Column('UrlCode', 'varchar(255)', false, 'unique')
    ->Column('Body', 'longtext', false, 'fulltext')
    ->Column('Excerpt', 'text', true)
    ->Column('Format', 'varchar(20)', true)
    ->Column('Status', 'varchar(20)', 'Draft') // Draft; Pending; Published; Trash
    ->Column('Closed', 'tinyint(1)', 0)
    ->Column('DateInserted', 'datetime', false, 'index')
    ->Column('DateUpdated', 'datetime', true)
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
    ->Column('InsertUserID', 'int', true)
    ->Column('UpdateUserID', 'int', true)
    ->Column('InsertIPAddress', 'varchar(39)', true)
    ->Column('UpdateIPAddress', 'varchar(39)', true)
    ->Column('GuestName', 'varchar(50)', true)
    ->Column('GuestEmail', 'varchar(200)', true)
    ->Column('ParentArticleCommentID', 'int', true)
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
    ->Column('IsThumbnail', 'tinyint(1)', 0)
    ->Column('DateInserted', 'datetime')
    ->Column('InsertUserID', 'int(11)')
    ->Set($Explicit, $Drop);

/*
 * Create activity types.
 */
$ActivityModel = new ActivityModel();
$ActivityModel->DefineType('Article');
$ActivityModel->DefineType('ArticleComment');

/*
 * Set up permissions.
 */
$PermissionModel = Gdn::PermissionModel();
$PermissionModel->Database = $Database;
$PermissionModel->SQL = $SQL;

// Undefine old global permissions (Articles v1.1.1 and older)
// before category-based permissions were implemented
if (!$PermissionArticleCategoryIDExists) {
    $PermissionModel->Undefine(array(
        'Articles.Articles.Add',
        'Articles.Articles.Close',
        'Articles.Articles.Delete',
        'Articles.Articles.Edit',
        'Articles.Articles.View',
        'Articles.Comments.Add',
        'Articles.Comments.Delete',
        'Articles.Comments.Edit'
    ));
}

// Define some global category-based permissions.
$PermissionModel->Define(array(
        'Articles.Articles.Add' => 0,
        'Articles.Articles.Close' => 0,
        'Articles.Articles.Delete' => 0,
        'Articles.Articles.Edit' => 0,
        'Articles.Articles.View' => 1,
        'Articles.Comments.Add' => 1,
        'Articles.Comments.Delete' => 0,
        'Articles.Comments.Edit' => 0
    ),
    'tinyint',
    'ArticleCategory',
    'PermissionArticleCategoryID');

// Set default permissions for roles.
If (!$PermissionArticleCategoryIDExists) {
    // Guest defaults
    $PermissionModel->Save(array(
        'Role' => 'Guest',
        'JunctionTable' => 'ArticleCategory',
        'JunctionColumn' => 'PermissionArticleCategoryID',
        'JunctionID' => -1,
        'Articles.Articles.View' => 1
    ), true);

    // Unconfirmed defaults
    $PermissionModel->Save(array(
        'Role' => 'Unconfirmed',
        'JunctionTable' => 'ArticleCategory',
        'JunctionColumn' => 'PermissionArticleCategoryID',
        'JunctionID' => -1,
        'Articles.Articles.View' => 1
    ), true);

    // Applicant defaults
    $PermissionModel->Save(array(
        'Role' => 'Applicant',
        'JunctionTable' => 'ArticleCategory',
        'JunctionColumn' => 'PermissionArticleCategoryID',
        'JunctionID' => -1,
        'Articles.Articles.View' => 1
    ), true);

    // Member defaults
    $PermissionModel->Save(array(
        'Role' => 'Member',
        'JunctionTable' => 'ArticleCategory',
        'JunctionColumn' => 'PermissionArticleCategoryID',
        'JunctionID' => -1,
        'Articles.Articles.View' => 1,
        'Articles.Comments.Add' => 1
    ), true);

    // Moderator defaults
    $PermissionModel->Save(array(
        'Role' => 'Moderator',
        'JunctionTable' => 'ArticleCategory',
        'JunctionColumn' => 'PermissionArticleCategoryID',
        'JunctionID' => -1,
        'Articles.Articles.Add' => 1,
        'Articles.Articles.Close' => 1,
        'Articles.Articles.Delete' => 1,
        'Articles.Articles.Edit' => 1,
        'Articles.Articles.View' => 1,
        'Articles.Comments.Add' => 1,
        'Articles.Comments.Delete' => 1,
        'Articles.Comments.Edit' => 1
    ), true);

    // Administrator defaults
    $PermissionModel->Save(array(
        'Role' => 'Administrator',
        'JunctionTable' => 'ArticleCategory',
        'JunctionColumn' => 'PermissionArticleCategoryID',
        'JunctionID' => -1,
        'Articles.Articles.Add' => 1,
        'Articles.Articles.Close' => 1,
        'Articles.Articles.Delete' => 1,
        'Articles.Articles.Edit' => 1,
        'Articles.Articles.View' => 1,
        'Articles.Comments.Add' => 1,
        'Articles.Comments.Delete' => 1,
        'Articles.Comments.Edit' => 1
    ), true);
}
