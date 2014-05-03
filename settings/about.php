<?php if(!defined('APPLICATION')) exit();

// An associative array of information about this application.
$ApplicationInfo['Articles'] = array(
    'Description' => 'Provides a way to create articles.',
    'Version' => '0.0.0.1',
    'Author' => 'Shadowdare',
    'AuthorUrl' => 'http://vanillaforums.org/profile/addons/16014/Shadowdare',
    'Url' => 'http://vanillaforums.org/addon/articles-application',
    'License' => 'Do not redistribute, modify, or create derivative works.',
    'RequiredApplications' => array('Vanilla' => '2.1rc1'),
    'RegisterPermissions' => false,
    'SetupController' => 'setup',
    'Settings' => '/settings/articles/'
);
