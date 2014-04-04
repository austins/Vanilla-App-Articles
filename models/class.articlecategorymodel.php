<?php if(!defined('APPLICATION')) exit();

/**
 * Handles data for articles.
 */
class ArticleCategoryModel extends Gdn_Model {
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('ArticleCategory');
   }

   /**
    * Gets the data for multiple articles based on given criteria.
    *
    * @param array $Wheres SQL conditions.
    *
    * @return Gdn_DataSet SQL result.
    */
   public function Get($Wheres = NULL) {
      // Set up selection query.
      $this->SQL->Select('ac.*')->From('ArticleCategory ac');

      // Handle SQL conditions for wheres.
      $this->EventArguments['Wheres'] = &$Wheres;
      $this->FireEvent('BeforeGet');

      if(is_array($Wheres))
         $this->SQL->Where($Wheres);

      // Set order of data.
      $this->SQL->OrderBy('ac.Name', 'asc');

      // Fetch data.
      $Categories = $this->SQL->Get();

      // Prepare and fire event.
      $this->EventArguments['Data'] = $Categories;
      $this->FireEvent('AfterGet');

      return $Categories;
   }

   public function GetByID($CategoryID) {
      // Set up the query.
      $this->SQL->Select('ac.*')
         ->From('ArticleCategory ac')
         ->Where('ac.CategoryID', $CategoryID);

      // Fetch data.
      $Category = $this->SQL->Get()->FirstRow();

      return $Category;
   }

   public function GetByUrlCode($CategoryUrlCode) {
      // Set up the query.
      $this->SQL->Select('ac.*')
         ->From('ArticleCategory ac')
         ->Where('ac.UrlCode', $CategoryUrlCode);

      // Fetch data.
      $Category = $this->SQL->Get()->FirstRow();

      return $Category;
   }
}
