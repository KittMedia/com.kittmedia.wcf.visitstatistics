<?php
namespace wcf\system\event\listener;
use wcf\data\visitor\Visitor;
use wcf\system\WCF;
use function date;
use const TIME_NOW;
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
	 * Delete old visits from main visitor table.
	 */
	protected function deleteVisits() {
		$sql = "DELETE FROM	".Visitor::getDatabaseTableName()."
			WHERE		time < ?";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([
			TIME_NOW - 86400 * self::DELETE_AFTER
		]);
	}
	
	/**
	 * Get last date in format Y-m-d where there are daily stats.
	 * 
	 * @return	string
	 */
	protected function getLastProcessedDay() {
		$sql = "SELECT		date
			FROM		wcf".WCF_N."_visitor_daily
			ORDER BY	date DESC
			LIMIT 1";
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
		$sql = "INSERT INTO	wcf".WCF_N."_visitor_daily
					(date, counter, isRegistered)
			SELECT		DATE_FORMAT(FROM_UNIXTIME(time), '%Y-%m-%d') AS date,
					COUNT(*) AS counter,
					isRegistered
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		time BETWEEN UNIX_TIMESTAMP(?) AND UNIX_TIMESTAMP(?)
			GROUP BY	isRegistered, date";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([
			$day ?: '1970-01-01',
			date('Y-m-d')
		]);
	}
	
	/**
	 * Set the URL stats.
	 * 
	 * @param	string		$day
	 */
	protected function setURLStats($day) {
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
			WHERE		time BETWEEN UNIX_TIMESTAMP(?) AND UNIX_TIMESTAMP(?)
			GROUP BY	requestURI, title, host, isRegistered, languageID, pageID, pageObjectID";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([
			$day ?: '1970-01-01',
			date('Y-m-d')
		]);
	}
}
