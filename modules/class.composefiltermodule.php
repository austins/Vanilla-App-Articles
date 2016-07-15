<?php
/**
 * ComposeFilter module
 *
 * @copyright 2015-2016 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Renders the menu for the Compose controller.
 */
class ComposeFilterModule extends Gdn_Module {
    public function __construct($sender = '') {
        parent::__construct($sender);
    }

    /**
     * Returns the asset name that the panel will be displayed to.
     *
     * @return string
     */
    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Returns the module as a string.
     *
     * @return string
     */
    public function toString() {
        $controller = Gdn::controller();
        $session = Gdn::session();

        $controller->EventArguments['ComposeFilterModule'] = &$this;
        $controller->fireEvent('BeforeComposeFilterModule');

        $permissionsAllowed = array('Articles.Articles.Add', 'Articles.Articles.Edit');
        if (!$session->checkPermission($permissionsAllowed, false, 'ArticleCategory', 'any')) {
            return '';
        }

        return parent::toString();
    }
}
