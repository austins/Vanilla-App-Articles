<?php
if (!defined('APPLICATION'))
    exit();

// An associative array of information about this application.
$ApplicationInfo['Articles'] = array(
    'Description' => 'Provides a way to create articles.',
    'Version' => '0.0.1',
    'Author' => 'Shadowdare',
    'AuthorUrl' => 'http://vanillaforums.org/profile/addons/16014/Shadowdare',
    'Url' => 'http://vanillaforums.org/addon/articles-application',
    'License' => 'GPLv3',
    'RequiredApplications' => array('Vanilla' => '2.1'),
    'RegisterPermissions' => false,
    'SetupController' => 'setup',
    'Settings' => '/settings/articles/'
);
