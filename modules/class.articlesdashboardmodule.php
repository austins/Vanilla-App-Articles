<?php if(!defined('APPLICATION')) exit();

/**
 * Renders the "Articles Dashboard" button.
 */
class ArticlesDashboardModule extends Gdn_Module {
    public function __construct($Sender = '') {
        parent::__construct($Sender);
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function ToString() {
        $Controller = Gdn::Controller();
        $Session = Gdn::Session();

        $Controller->EventArguments['ArticlesDashboardModule'] = &$this;
        $Controller->FireEvent('BeforeArticlesDashboardModuleButton');

        $PermissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        if(!$Session->CheckPermission($PermissionsAllowed, false))
            return '';

        return parent::ToString();
    }
}
