<?php
namespace wcf\system\event\listener;
use DateInterval;
use DateTime;
use DateTimeZone;
use wcf\data\visitor\Visitor;
use wcf\system\WCF;
use wcf\util\DateUtil;
use const TIME_NOW;
use const TIMEZONE;
use const WCF_N;

/**
 * Clean up data daily.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2022 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 */
final class VisitStatisticsDailyCleanUpCronjobListener implements IParameterizedEventListener {
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
	 * @param	DateTime	$minDate
	 */
	protected function deleteOldDailyStats($minDate) {
		$sql = "DELETE FROM	wcf" . WCF_N . "_visitor_daily
			WHERE		date >= ?";
		WCF::getDB()->prepareStatement($sql)->execute([
			$minDate->format('Y-m-d')
		]);
		$sql = "DELETE FROM	wcf" . WCF_N . "_visitor_daily_system
			WHERE		date >= ?";
		WCF::getDB()->prepareStatement($sql)->execute([
			$minDate->format('Y-m-d')
		]);
	}
	
	/**
	 * Delete old visits from main visitor table.
	 */
	protected function deleteVisits() {
		$dateTime = DateUtil::getDateTimeByTimestamp(TIME_NOW);
		$dateTime->setTimezone(new DateTimeZone(TIMEZONE));
		$dateTime->sub(DateInterval::createFromDateString(self::DELETE_AFTER . ' day'));
		$sql = "DELETE FROM	".Visitor::getDatabaseTableName()."
			WHERE		time < ?";
		WCF::getDB()->prepareStatement($sql)->execute([
			$dateTime->getTimestamp()
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
			$day = DateTime::createFromFormat('Y-m-d', $day);
			$day->setTimezone(new DateTimeZone(TIMEZONE));
			$day->setTime(0, 0);
			$sevenDaysAgo = new DateTime();
			$sevenDaysAgo->setTimezone(new DateTimeZone(TIMEZONE));
			$sevenDaysAgo->modify('-7 day');
			$sevenDaysAgo->setTime(0, 0);
			
			// get at least the previous 7 days
			if ($day > $sevenDaysAgo) {
				$day = $sevenDaysAgo;
			}
		}
		else {
			$day = DateTime::createFromFormat('Y-m-d H:i:s', $this->getFirstSQLDate());
			$day->setTimezone(new DateTimeZone(TIMEZONE));
		}
		
		$yesterday = new DateTime();
		$yesterday->setTimezone(new DateTimeZone(TIMEZONE));
		$yesterday->modify('-24 hour');
		$yesterday->setTime(23, 59, 59);
		
		// delete old stats of the last 7 days
		$this->deleteOldDailyStats($day);
		
		$sql = "INSERT IGNORE INTO	".Visitor::getDatabaseTableName()."_daily
						(date, counter, isRegistered)
			SELECT			CONVERT_TZ(DATE_FORMAT(FROM_UNIXTIME(time), '%Y-%m-%d'), @@SESSION.time_zone, ?) AS date,
						COUNT(*) AS counter,
						isRegistered
			FROM			".Visitor::getDatabaseTableName()."
			WHERE			time BETWEEN ? AND ?
			GROUP BY		isRegistered, date";
		WCF::getDB()->prepareStatement($sql)->execute([
			$day->format('P'),
			$day->getTimestamp(),
			$yesterday->getTimestamp()
		]);
		
		$sql = "INSERT IGNORE INTO	".Visitor::getDatabaseTableName()."_daily_system
						(date, browserName, browserVersion, osName, osVersion, counter, isRegistered)
			SELECT			CONVERT_TZ(DATE_FORMAT(FROM_UNIXTIME(time), '%Y-%m-%d'), @@SESSION.time_zone, ?) AS date,
						browserName,
						browserVersion,
						osName,
						osVersion,
						COUNT(*) AS counter,
						isRegistered
			FROM			".Visitor::getDatabaseTableName()."
			WHERE			time BETWEEN ? AND ?
			GROUP BY		isRegistered, browserName, browserVersion, osName, osVersion, date";
		WCF::getDB()->prepareStatement($sql)->execute([
			$day->format('P'),
			$day->getTimestamp(),
			$yesterday->getTimestamp()
		]);
	}
	
	/**
	 * Set the URL stats.
	 * 
	 * @param	string		$day
	 */
	protected function setURLStats($day) {
		if (!empty($day)) {
			$day = DateTime::createFromFormat('Y-m-d', $day);
			$day->setTimezone(new DateTimeZone(TIMEZONE));
			$sevenDaysAgo = $day->sub(DateInterval::createFromDateString('-7 day'));
			$day->add(DateInterval::createFromDateString('1 day'));
			
			// get at least the previous 7 days
			if ($day > $sevenDaysAgo) {
				$day = $sevenDaysAgo;
			}
		}
		else {
			$day = DateTime::createFromFormat('Y-m-d H:i:s', $this->getFirstSQLDate());
			$day->setTimezone(new DateTimeZone(TIMEZONE));
		}
		
		$yesterday = new DateTime();
		$yesterday->setTimezone(new DateTimeZone(TIMEZONE));
		$yesterday->setTime(23, 59, 59);
		$yesterday->modify('-24 hour');
		
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
			$day->getTimestamp(),
			$yesterday->getTimestamp()
		]);
	}
}
