<?php
if (!defined('APPLICATION'))
    exit();

/**
 * Renders the "Articles Dashboard" button.
 */
class ArticlesDashboardModule extends Gdn_Module {
    public function __construct($Sender = '') {
        parent::__construct($Sender);
    }

    /**
     * Returns the asset name that the panel will be displayed to.
     *
     * @return string
     */
    public function AssetTarget() {
        return 'Panel';
    }

    /**
     * Returns the module as a string.
     *
     * @return string
     */
    public function ToString() {
        $Controller = Gdn::Controller();
        $Session = Gdn::Session();

        $Controller->EventArguments['ArticlesDashboardModule'] = &$this;
        $Controller->FireEvent('BeforeArticlesDashboardModuleButton');

        $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        if (!$Session->CheckPermission($PermissionsAllowed, false))
            return '';

        return parent::ToString();
    }
}
