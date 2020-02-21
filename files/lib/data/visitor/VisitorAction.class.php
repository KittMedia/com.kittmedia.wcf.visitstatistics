<?php
namespace wcf\data\visitor;
use DateTime;
use DateTimeZone;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\WCF;
use const TIMEZONE;

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
		
		// get time zone
		$time = new DateTime('now', new DateTimeZone(TIMEZONE));
		$timezone = $time->format('P');
		
		// get data
		$data = [];
		$sql = "SELECT		COUNT(*) AS count,
					UNIX_TIMESTAMP(CONVERT_TZ(DATE_FORMAT(FROM_UNIXTIME(time), '%Y-%m-%d 00:00:00'), '+00:00', ?)) AS dayTime
			FROM		".Visitor::getDatabaseTableName()." AS ".Visitor::getDatabaseTableAlias()."
			WHERE		time >= UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))
			GROUP BY	dayTime";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([$timezone]);
		
		while ($row = $statement->fetchArray()) {
			$data[0]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits');
			$data[0]['data'][] = [
				$row['dayTime'],
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
