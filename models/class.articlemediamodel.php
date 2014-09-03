<?php
if (!defined('APPLICATION'))
    exit();

/**
 * Handles data for articles.
 */
class ArticleMediaModel extends Gdn_Model {
    /**
     * Class constructor. Defines the related database table name.
     */
    public function __construct() {
        parent::__construct('ArticleMedia');
    }
}
