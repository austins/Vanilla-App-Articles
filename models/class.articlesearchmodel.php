<?php defined('APPLICATION') or exit();
/**
 * Copyright (C) 2015  Austin S. (Shadowdare)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Manages searches for Articles and associated comments.
 *
 * @package Articles
 */
class ArticleSearchModel extends Gdn_Model {
   /**
    * @var object ArticleModel
    */	
	protected $_ArticleModel = FALSE;
	
	/**
	 * Makes an article model available.
	 * 
	 * @param object $Value ArticleModel.
	 * @return object ArticleModel.
	 */
	public function ArticleModel($Value = FALSE) {
		if($Value !== FALSE) {
			$this->_ArticleModel = $Value;
		}
		if($this->_ArticleModel === FALSE) {
			require_once(dirname(__FILE__) . DS . 'class.articlemodel.php');
			$this->_ArticleModel = new ArticleModel();
		}
		return $this->_ArticleModel;
	}
	
	/**
	 * Execute discussion search query.
	 * 
	 * @param object $SearchModel SearchModel (Dashboard)
	 * @return object SQL result.
	 */
	public function ArticleSql($SearchModel) {
		// TODO: Add in a search restriction based on category permissions
		
		// Build search part of query
		$SearchModel->AddMatchSql($this->SQL, 'a.Name, a.Body', 'a.DateInserted');
		
		// Build base query
		$this->SQL
			->Select('a.ArticleID as PrimaryID, a.Name as Title, a.Excerpt as Summary, a.Format, a.ArticleCategoryID')
			->Select('a.UrlCode', "concat('/article/', year(a.DateInserted), '/', %s)", 'Url')
			->Select('a.DateInserted')
			->Select('a.AttributionUserID as UserID')
            ->Select("'Article'", '', 'RecordType')
			->From('Article a');
		
		// Execute query
		$Result = $this->SQL->GetSelect();
		
		// Unset SQL
		$this->SQL->Reset();
		
		return $Result;
	}
	
	/**
	 * Execute comment search query.
	 * 
	 * @param object $SearchModel SearchModel (Dashboard)
	 * @return object SQL result.
	 */
	public function CommentSql($SearchModel) {
		// TODO: Add in a search restriction based on category permissions
		
		// Build search part of query
		$SearchModel->AddMatchSql($this->SQL, 'ac.Body', 'ac.DateInserted');
		
		// Build base query
		$this->SQL
			->Select('ac.ArticleCommentID as PrimaryID, a.Name as Title, ac.Body as Summary, ac.Format, a.ArticleCategoryID')
			->Select("'/article/comment/', ac.ArticleCommentID, '/#Comment_', ac.ArticleCommentID", "concat", 'Url')
			->Select('ac.DateInserted')
			->Select('ac.InsertUserID as UserID')
            ->Select("'ArticleComment'", '', 'RecordType')
			->From('ArticleComment ac')
			->Join('Article a', 'a.ArticleID = ac.ArticleID');
		
		// Exectute query
		$Result = $this->SQL->GetSelect();
		
		// Unset SQL
		$this->SQL->Reset();
		
		return $Result;
	}
	
	/**
	 * Add the searches for Articles to the search model.
	 * 
	 * @param object $SearchModel SearchModel (Dashboard)
	 */
	public function Search($SearchModel) {
		$SearchModel->AddSearch($this->ArticleSql($SearchModel));
		$SearchModel->AddSearch($this->CommentSql($SearchModel));
	}
}