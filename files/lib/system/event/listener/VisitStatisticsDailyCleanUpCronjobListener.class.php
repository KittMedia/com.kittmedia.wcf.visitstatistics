<?php
namespace wcf\system\event\listener;
use wcf\data\visitor\Visitor;
use wcf\system\WCF;
use const TIME_NOW;
use const WCF_N;

/**
 * Clean up data daily.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitors
 */
class VisitStatisticsDailyCleanUpCronjobListener implements IParameterizedEventListener {
	// TODO: move to options
	const DELETE_AFTER = 30;
	
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		$lastDay = $this->getLastProcessedDay();
		
		$this->setDailyStats($lastDay);
		//$this->deleteVisits();
	}
	
	/**
	 * Delete old visits from main visitor table.
	 */
	protected function deleteVisits() {
		$sql = "DELETE FROM	".Visitor::getDatabaseTableName()."
			WHERE		time < UNIX_TIMESTAMP(?)";
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
			WHERE		time >= UNIX_TIMESTAMP(?)
			GROUP BY	isRegistered, date";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute([$day ?: 0]);
	}
}
