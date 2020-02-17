<?php
namespace wcf\data\visitor;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\WCF;

/**
 * Provides functions for user visits.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitors
 * 
 * @method	VisitorEditor[]		getObjects()
 * @method	Visitor		        getSingleObject() 
 */
class VisitorAction extends AbstractDatabaseObjectAction {
	/**
	 * Return daily click statistics.
	 * 
	 * @return	mixed[]
	 */
	public function getData() {
		// change sql mode
		$sql = "SELECT		@@sql_mode";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$currentSqlMode = $statement->fetchColumn();
		$sqlModeStatement = WCF::getDB()->prepareStatement("SET SESSION sql_mode = ?");
		$sqlModeStatement->execute(['TRADITIONAL']);
		
		// get data
		$data = [];
		$sql = "SELECT		COUNT(*) AS count,
					time,
					DAY(FROM_UNIXTIME(time)) AS daily
			FROM		".Visitor::getDatabaseTableName()." AS ".Visitor::getDatabaseTableAlias()."
			GROUP BY	daily";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		
		while ($row = $statement->fetchArray()) {
			$data[0]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visitor');
			$data[0]['data'][] = [
				$row['time'],
				$row['count']
			];
		}
		
		// restore sql mode
		$sqlModeStatement->execute([$currentSqlMode]);
		
		return $data;
	}
	
	/**
	 * Validates the getData action.
	 */
	public function validateGetData() {
		WCF::getSession()->checkPermissions(['admin.management.canViewLog']);
	}
}
