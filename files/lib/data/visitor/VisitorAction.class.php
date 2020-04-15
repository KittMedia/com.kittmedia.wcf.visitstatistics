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
 * @method	Visitor			getSingleObject() 
 */
class VisitorAction extends AbstractDatabaseObjectAction {
	/**
	 * @inheritDocs
	 */
	protected $requireACP = ['getData'];
	
	/**
	 * Return daily click statistics.
	 * 
	 * @return	mixed[]
	 */
	public function getData() {
		// get time zone
		$time = new DateTime('now', new DateTimeZone(TIMEZONE));
		$timezone = $time->format('P');
		
		// get data
		$data = [];
		$sql = "SELECT		COUNT(*) AS count,
					UNIX_TIMESTAMP(CONVERT_TZ(DATE_FORMAT(FROM_UNIXTIME(time), '%Y-%m-%d 00:00:00'), @@session.time_zone, ?)) AS dayTime
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		time >= UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))
			GROUP BY	dayTime";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([$timezone]);
		
		$data[0]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits');
		
		while ($row = $statement->fetchArray()) {
			$data[0]['data'][] = [
				$row['dayTime'],
				$row['count']
			];
		}
		
		return $data;
	}
	
	/**
	 * Validates the getData action.
	 */
	public function validateGetData() {
		WCF::getSession()->checkPermissions(['admin.management.canViewLog']);
	}
}
