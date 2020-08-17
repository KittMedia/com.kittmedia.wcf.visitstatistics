<?php
namespace wcf\system\event\listener;
use DateTime;
use DateTimeZone;
use wcf\data\visitor\Visitor;
use wcf\system\WCF;
use function date;
use function strtotime;
use const TIME_NOW;
use const TIMEZONE;
use const WCF_N;

/**
 * Clean up data daily.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 */
class VisitStatisticsDailyCleanUpCronjobListener implements IParameterizedEventListener {
	const DELETE_AFTER = 7;
	
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		$lastDay = $this->getLastProcessedDay();
		
		$this->setDailyStats($lastDay);
		$this->setURLStats($lastDay);
		$this->deleteVisits();
	}
	
	/**
	 * Delete old daily stats.
	 * 
	 * @param	int	$minDate
	 */
	protected function deleteOldDailyStats($minDate) {
		$sql = "DELETE FROM	wcf".WCF_N."_visitor_daily
			WHERE		date >= ?";
		WCF::getDB()->prepareStatement($sql)->execute([
			date('Y-m-d', $minDate)
		]);
	}
	
	/**
	 * Delete old visits from main visitor table.
	 */
	protected function deleteVisits() {
		$sql = "DELETE FROM	".Visitor::getDatabaseTableName()."
			WHERE		time < ?";
		WCF::getDB()->prepareStatement($sql)->execute([
			TIME_NOW - 86400 * self::DELETE_AFTER
		]);
	}
	
	/**
	 * Get the first datetime from SQL.
	 * 
	 * Depending on the timezone, FROM_UNIXTIME(0) returns something different,
	 * so we need the live value.
	 * 
	 * @return	string
	 */
	protected function getFirstSQLDate() {
		$sql = "SELECT	FROM_UNIXTIME(0)";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		
		return $statement->fetchSingleColumn();
	}
	
	/**
	 * Get last date in format Y-m-d where there are daily stats.
	 * 
	 * @return	string
	 */
	protected function getLastProcessedDay() {
		$sql = "SELECT		MAX(date)
			FROM		wcf".WCF_N."_visitor_daily";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		
		return $statement->fetchSingleColumn();
	}
	
	/**
	 * Set the daily visit stats.
	 * 
	 * @param	string		$day
	 */
	protected function setDailyStats($day) {
		if (!empty($day)) {
			$day = strtotime($day . ' +1 day');
			
			// get at least the previous 7 days
			if ($day > strtotime('-7 day')) {
				$day = strtotime('-7 day');
			}
		}
		else {
			$day = $this->getFirstSQLDate();
		}
		
		// delete old stats of the last 7 days
		$this->deleteOldDailyStats($day);
		
		// get time zone
		$time = new DateTime('now', new DateTimeZone(TIMEZONE));
		$timeZone = $time->format('P');
		$sql = "INSERT INTO	wcf".WCF_N."_visitor_daily
					(date, counter, isRegistered)
			SELECT		CONVERT_TZ(DATE_FORMAT(FROM_UNIXTIME(time), '%Y-%m-%d'), @@SESSION.time_zone, ?) AS date,
					COUNT(*) AS counter,
					isRegistered
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		time BETWEEN ? AND ?
			GROUP BY	isRegistered, date";
		WCF::getDB()->prepareStatement($sql)->execute([
			$timeZone,
			$day,
			strtotime('yesterday 23:59:59')
		]);
	}
	
	/**
	 * Set the URL stats.
	 * 
	 * @param	string		$day
	 */
	protected function setURLStats($day) {
		if (!empty($day)) {
			$day = strtotime($day . ' +1 day');
			
			if (strtotime(date('Y-m-d', $day) . ' +1 day') > strtotime('yesterday 23:59:59')) {
				return;
			}
		}
		else {
			$day = $this->getFirstSQLDate();
		}
		
		$sql = "INSERT INTO	wcf".WCF_N."_visitor_url
					(requestURI, title, host, counter, isRegistered, languageID, pageID, pageObjectID)
			SELECT		requestURI,
					title,
					host,
					COUNT(*) AS counter,
					isRegistered,
					languageID,
					pageID,
					pageObjectID
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		time BETWEEN ? AND ?
			GROUP BY	requestURI, title, host, isRegistered, languageID, pageID, pageObjectID";
		WCF::getDB()->prepareStatement($sql)->execute([
			$day,
			strtotime('yesterday 23:59:59')
		]);
	}
}
