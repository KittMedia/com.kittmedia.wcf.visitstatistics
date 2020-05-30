<?php
namespace wcf\data\visitor;
use DateTime;
use DateTimeZone;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\WCF;
use function is_array;
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
		$timezone = $time->format('Z');
		
		// get data
		$data = [];
		$sql = "SELECT		COUNT(*) AS count,
					UNIX_TIMESTAMP(DATE_FORMAT(FROM_UNIXTIME(time), '%Y-%m-%d')) AS dayTime,
					isRegistered
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		time >= UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))
			GROUP BY	isRegistered, dayTime";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		
		$data[0]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits.total');
		$data[1]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits.user');
		$data[2]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits.guest');
		$counts = [];
		
		while ($row = $statement->fetchArray()) {
			// respect timezone
			$row['dayTime'] += $timezone;
			
			if (!isset($counts[$row['dayTime']])) {
				$counts[$row['dayTime']] = [];
			}
			
			if (!isset($counts[$row['dayTime']]['total'])) {
				$counts[$row['dayTime']]['total'] = $row['count'];
			}
			else {
				$counts[$row['dayTime']]['total'] += $row['count'];
			}
			
			if ($row['isRegistered']) {
				$counts[$row['dayTime']]['user'] = $row['count'];
			}
			else {
				$counts[$row['dayTime']]['guest'] = $row['count'];
			}
		}
		
		// separate data for each data
		foreach ($counts as $dayTime => $count) {
			$data[0]['data'][] = [
				$dayTime,
				$count['total'] ?? 0
			];
			$data[1]['data'][] = [
				$dayTime,
				$count['user'] ?? 0
			];
			$data[2]['data'][] = [
				$dayTime,
				$count['guest'] ?? 0
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
