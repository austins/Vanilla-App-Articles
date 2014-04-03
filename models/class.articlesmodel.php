<?php if(!defined('APPLICATION')) exit();

/**
 * Introduces common methods that child classes can use.
 */
abstract class ArticlesModel extends Gdn_Model {
    /**
     * Class constructor. Defines the related database table name.
     *
     * @param string $Name Database table name.
     */
    public function __construct($Name = '') {
        parent::__construct($Name);
    }
}
