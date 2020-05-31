<?php
namespace wcf\data\visitor;
use DateTime;
use DateTimeZone;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\WCF;
use function date;
use function strtotime;
use const TIMEZONE;

/**
 * Provides functions for user visits.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
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
		$sql = "SELECT		counter, date, isRegistered
			FROM		".Visitor::getDatabaseTableName()."_daily
			WHERE		date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
			GROUP BY	isRegistered, date";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		
		$data[0]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits.total');
		$data[1]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits.user');
		$data[2]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits.guest');
		$counts = [];
		
		while ($row = $statement->fetchArray()) {
			// to timestamp
			$row['dayTime'] = strtotime($row['date']) + $timezone;
			
			if (!isset($counts[$row['dayTime']])) {
				$counts[$row['dayTime']] = [];
			}
			
			if (!isset($counts[$row['dayTime']]['total'])) {
				$counts[$row['dayTime']]['total'] = $row['counter'];
			}
			else {
				$counts[$row['dayTime']]['total'] += $row['counter'];
			}
			
			if ($row['isRegistered']) {
				$counts[$row['dayTime']]['user'] = $row['counter'];
			}
			else {
				$counts[$row['dayTime']]['guest'] = $row['counter'];
			}
		}
		
		// get today's data
		$sql = "SELECT		COUNT(*) AS counter,
					DATE_FORMAT(FROM_UNIXTIME(time), '%Y-%m-%d') AS date,
					isRegistered
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		time >= UNIX_TIMESTAMP(CURDATE())
			GROUP BY	isRegistered, date";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$todayTimestamp = strtotime(date('Y-m-d')) + $timezone;
		$counts[$todayTimestamp] = [
			'guest' => 0,
			'total' => 0,
			'user' => 0
		];
		
		while($row = $statement->fetchArray()) {
			$counts[$todayTimestamp]['total'] += $row['counter'];
			
			if ($row['isRegistered']) {
				$counts[$todayTimestamp]['user'] = $row['counter'];
			}
			else {
				$counts[$todayTimestamp]['guest'] = $row['counter'];
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
