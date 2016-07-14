<?php
/**
 * An associative array of information about this application.
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

$ApplicationInfo['Articles'] = array(
    'Description' => 'Provides a way to create articles.',
    'Version' => '1.1.1',
    'Author' => 'Austin S.',
    'AuthorUrl' => 'https://github.com/austins',
    'Url' => 'http://vanillaforums.org/addon/articles-application',
    'License' => 'GNU GPL v2',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'RegisterPermissions' => false,
    'SetupController' => 'setup',
    'SettingsUrl' => '/settings/articles/'
);
