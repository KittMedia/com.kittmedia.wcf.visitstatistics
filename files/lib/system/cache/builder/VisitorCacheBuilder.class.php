<?php
namespace wcf\system\cache\builder;
use DateTime;
use wcf\data\visitor\Visitor;
use \wcf\system\WCF;
use wcf\util\StringUtil;
use function date_diff;
use function intval;
use function round;
use function str_replace;
use const TIME_NOW;

/**
 * Caches visitor related statistics.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitors
 */
class VisitorCacheBuilder extends AbstractCacheBuilder {
	/**
	 * @inheritDoc
	 */
	protected $maxLifetime = 600;
	
	/**
	 * Statistics
	 * @var		mixed[]|null
	 */
	protected $statistics = null;
	
	/**
	 * @inheritDoc
	 */
	protected function rebuild(array $parameters) {
		$this->calculateLastMonthStatistics();
		$this->calculateLastWeekStatistics();
		$this->calculateThisMonthStatistics();
		$this->calculateThisWeekStatistics();
		$this->calculateTodayStatistics();
		$this->calculateTotalStatistics();
		$this->calculateYesterdayStatistics();
		$this->calculateAverageStatistics();
		
		$this->statistics['rebuildTime'] = TIME_NOW;
		
		return $this->statistics;
	}
	
	/**
	 * Calculate statistics for average.
	 */
	protected function calculateAverageStatistics() {
		if (empty($this->statistics['countTotal'])) {
			$this->statistics['countAverage'] = 0;
		}
		else {
			// get first date
			$sql = "SELECT		time
				FROM		".Visitor::getDatabaseTableName()."
				ORDER BY	time ASC";
			$statement = WCF::getDB()->prepareStatement($sql, 1);
			$statement->execute();
			
			// get day difference
			$firstDate = new DateTime();
			$firstDate->setTimestamp($statement->fetchColumn());
			$today = new DateTime();
			$diffDays = date_diff($firstDate, $today);
			
			// calculate average
			$this->statistics['countAverage'] = StringUtil::formatNumeric(round(intval(str_replace( [',', '.'], '', $this->statistics['countTotal'] )) / $diffDays->days, 2));
		}
	}
	
	/**
	 * Calculate statistics for last month.
	 */
	protected function calculateLastMonthStatistics() {
		// get last month's count
		$sql = "SELECT		COUNT(*)
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		MONTH(FROM_UNIXTIME(time)) = MONTH(CURDATE() - INTERVAL 1 MONTH)
			AND		YEAR(FROM_UNIXTIME(time)) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countLastMonth'] = StringUtil::formatNumeric($statement->fetchColumn());
	}
	
	/**
	 * Calculate statistics for last week.
	 */
	protected function calculateLastWeekStatistics() {
		// get last week's count
		$sql = "SELECT		COUNT(*)
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		FROM_UNIXTIME(time) >= CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) + 6 DAY
			AND		FROM_UNIXTIME(time) < CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) - 1 DAY";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countLastWeek'] = StringUtil::formatNumeric($statement->fetchColumn());
	}
	
	/**
	 * Calculate statistics for this month.
	 */
	protected function calculateThisMonthStatistics() {
		// get this month's count
		$sql = "SELECT		COUNT(*)
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		MONTH(FROM_UNIXTIME(time)) = MONTH(CURDATE())
			AND		YEAR(FROM_UNIXTIME(time)) = YEAR(CURDATE())";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countThisMonth'] = StringUtil::formatNumeric($statement->fetchColumn());
	}
	
	/**
	 * Calculate statistics for this week.
	 */
	protected function calculateThisWeekStatistics() {
		// get this week's count
		$sql = "SELECT		COUNT(*)
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		YEARWEEK(FROM_UNIXTIME(time), 1) = YEARWEEK(CURDATE(), 1)";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countThisWeek'] = StringUtil::formatNumeric($statement->fetchColumn());
	}
	
	/**
	 * Calculate statistics for today.
	 */
	protected function calculateTodayStatistics() {
		// get today's count
		$sql = "SELECT		COUNT(*)
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		DATE(FROM_UNIXTIME(time)) = CURDATE()";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countToday'] = StringUtil::formatNumeric($statement->fetchColumn());
		
		// get the most requested URIs
		$sql = "SELECT		requestURI, title, host, languageID, pageID, pageObjectID, COUNT(*) AS requestCount
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		DATE(FROM_UNIXTIME(time)) = CURDATE()
			GROUP BY	requestURI, title, host, languageID, pageID, pageObjectID
			ORDER BY	requestCount DESC, title";
		$statement = WCF::getDB()->prepareStatement($sql, 20);
		$statement->execute();
		
		while ($row = $statement->fetchArray()) {
			$this->statistics['requestList'][] = (object) $row;
		}
	}
	
	/**
	 * Calculate total statistics.
	 */
	protected function calculateTotalStatistics() {
		// get total count
		$sql = "SELECT		COUNT(*)
			FROM		".Visitor::getDatabaseTableName()." AS ".Visitor::getDatabaseTableAlias();
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countTotal'] = StringUtil::formatNumeric($statement->fetchColumn());
	}
	
	/**
	 * Calculate statistics for yesterday.
	 */
	protected function calculateYesterdayStatistics() {
		// get yesterday's count
		$sql = "SELECT		COUNT(*)
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		DATE(FROM_UNIXTIME(time)) = CURDATE() - INTERVAL 1 DAY";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countYesterday'] = StringUtil::formatNumeric($statement->fetchColumn());
	}
}
