<?php
namespace wcf\system\cache\builder;
use DateTime;
use wcf\data\visitor\Visitor;
use \wcf\system\WCF;
use wcf\util\StringUtil;
use function array_merge;
use function date_diff;
use function intval;
use function round;
use function str_replace;
use const TIME_NOW;

/**
 * Caches visitor related statistics.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2021 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 */
class VisitorCacheBuilder extends AbstractCacheBuilder {
	/**
	 * @inheritDoc
	 */
	protected $maxLifetime = 600;
	
	/**
	 * Statistics
	 * @var		mixed[]
	 */
	protected $statistics = [
		'countAverage' => 0,
		'countLastMonth' => 0,
		'countLastWeek' => 0,
		'countThisMonth' => 0,
		'countThisWeek' => 0,
		'countToday' => 0,
		'countTotal' => 0,
		'countYesterday' => 0,
	];
	
	/**
	 * @inheritDoc
	 */
	protected function rebuild(array $parameters) {
		$this->calculateTodayStatistics();
		$this->calculateLastMonthStatistics();
		$this->calculateLastWeekStatistics();
		$this->calculateThisMonthStatistics();
		$this->calculateThisWeekStatistics();
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
			return;
		}
		
		// get first date
		$sql = "SELECT		date
			FROM		".Visitor::getDatabaseTableName()."_daily
			ORDER BY	date ASC";
		$statement = WCF::getDB()->prepareStatement($sql, 1);
		$statement->execute();
		
		// get day difference
		$firstDate = new DateTime($statement->fetchColumn());
		$today = new DateTime();
		$diffDays = date_diff($firstDate, $today);
		
		// calculate average
		if ($diffDays->days) {
			// add 1 day for today
			$this->statistics['countAverage'] = StringUtil::formatNumeric(round(intval(str_replace([',', '.'], '', $this->statistics['countTotal'])) / ($diffDays->days + 1), 2));
		}
		else {
			$this->statistics['countAverage'] = $this->statistics['countTotal'];
		}
	}
	
	/**
	 * Calculate statistics for last month.
	 */
	protected function calculateLastMonthStatistics() {
		// get last month's count
		$sql = "SELECT		SUM(counter)
			FROM		".Visitor::getDatabaseTableName()."_daily
			WHERE		MONTH(date) = MONTH(CURDATE() - INTERVAL 1 MONTH)
			AND		YEAR(date) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countLastMonth'] = StringUtil::formatNumeric($statement->fetchColumn());
	}
	
	/**
	 * Calculate statistics for last week.
	 */
	protected function calculateLastWeekStatistics() {
		// get last week's count
		$sql = "SELECT		SUM(counter)
			FROM		".Visitor::getDatabaseTableName()."_daily
			WHERE		date >= CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) + 6 DAY
			AND		date < CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) - 1 DAY";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countLastWeek'] = StringUtil::formatNumeric($statement->fetchColumn());
	}
	
	/**
	 * Calculate statistics for this month.
	 */
	protected function calculateThisMonthStatistics() {
		// get this month's count
		$sql = "SELECT		SUM(counter)
			FROM		".Visitor::getDatabaseTableName()."_daily
			WHERE		MONTH(date) = MONTH(CURDATE())
			AND		YEAR(date) = YEAR(CURDATE())
			AND		date < CURDATE()";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countThisMonth'] = StringUtil::formatNumeric($statement->fetchColumn() + $this->statistics['countToday']);
	}
	
	/**
	 * Calculate statistics for this week.
	 */
	protected function calculateThisWeekStatistics() {
		// get this week's count
		$sql = "SELECT		SUM(counter)
			FROM		".Visitor::getDatabaseTableName()."_daily
			WHERE		YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)
			AND		date < CURDATE()";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countThisWeek'] = StringUtil::formatNumeric($statement->fetchColumn() + $this->statistics['countToday']);
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
		$this->statistics['requestList'] = [];
		
		while ($row = $statement->fetchArray()) {
			$data = (object) $row;
			$data->requestCount = StringUtil::formatNumeric($data->requestCount);
			$this->statistics['requestList'][] = $data;
		}
	}
	
	/**
	 * Calculate total statistics.
	 */
	protected function calculateTotalStatistics() {
		// get total count
		$sql = "SELECT		SUM(counter)
			FROM		".Visitor::getDatabaseTableName()."_daily
			WHERE		date < CURDATE()";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countTotal'] = StringUtil::formatNumeric($statement->fetchColumn() + $this->statistics['countToday']);
		
		// get the most requested URIs
		$sql = "SELECT		requestURI, title, host, languageID, pageID, pageObjectID, SUM(counter) AS requestCount
			FROM		".Visitor::getDatabaseTableName()."_url
			GROUP BY	requestURI, title, host, languageID, pageID, pageObjectID
			ORDER BY	requestCount DESC, title";
		$statement = WCF::getDB()->prepareStatement($sql, 20);
		$statement->execute();
		$this->statistics['requestListAll'] = [];
		
		while ($row = $statement->fetchArray()) {
			$data = (object) $row;
			$data->requestCount = StringUtil::formatNumeric($data->requestCount);
			$this->statistics['requestListAll'][] = $data;
		}
		
		$this->statistics['requestListAll'] = array_merge($this->statistics['requestListAll'], $this->statistics['requestList']);
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
