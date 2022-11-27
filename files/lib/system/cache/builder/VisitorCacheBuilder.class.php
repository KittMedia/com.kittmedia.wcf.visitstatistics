<?php
namespace wcf\system\cache\builder;
use DateTime;
use DateTimeZone;
use wcf\data\visitor\Visitor;
use wcf\system\WCF;
use wcf\util\DateUtil;
use function round;
use const TIME_NOW;
use const TIMEZONE;

/**
 * Caches visitor related statistics.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2022 KittMedia
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
		'countLastYear' => 0,
		'countThisMonth' => 0,
		'countThisWeek' => 0,
		'countThisYear' => 0,
		'countToday' => 0,
		'countTotal' => 0,
		'countYesterday' => 0
	];
	
	/**
	 * @inheritDoc
	 */
	protected function rebuild(array $parameters) {
		$this->calculateTodayStatistics();
		$this->calculateLastMonthStatistics();
		$this->calculateLastWeekStatistics();
		$this->calculateLastYearStatistics();
		$this->calculateThisMonthStatistics();
		$this->calculateThisWeekStatistics();
		$this->calculateThisYearStatistics();
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
			FROM		wcf1_visitor_daily
			ORDER BY	date ASC";
		$statement = WCF::getDB()->prepareStatement($sql, 1);
		$statement->execute();
		
		// get day difference
		$firstDate = new DateTime($statement->fetchColumn(), new DateTimeZone(TIMEZONE));
		$today = DateUtil::getDateTimeByTimestamp(TIME_NOW);
		$diffDays = $firstDate->diff($today);
		
		// calculate average
		if ($diffDays->days) {
			// add 1 day for today
			$this->statistics['countAverage'] = round($this->statistics['countTotal'] / ($diffDays->days + 1), 2);
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
			FROM		wcf1_visitor_daily
			WHERE		MONTH(date) = MONTH(CURDATE() - INTERVAL 1 MONTH)
			AND		YEAR(date) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countLastMonth'] = (int) $statement->fetchColumn();
	}
	
	/**
	 * Calculate statistics for last week.
	 */
	protected function calculateLastWeekStatistics() {
		// get last week's count
		$sql = "SELECT		SUM(counter)
			FROM		wcf1_visitor_daily
			WHERE		date >= CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) + 6 DAY
			AND		date < CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) - 1 DAY";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countLastWeek'] = (int) $statement->fetchColumn();
	}
	
	/**
	 * Calculate statistics for last year.
	 * 
	 * @since	1.3.0
	 */
	protected function calculateLastYearStatistics() {
		// get last year's count
		$sql = "SELECT		SUM(counter)
			FROM		wcf1_visitor_daily
			WHERE		YEAR(date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countLastYear'] = (int) $statement->fetchColumn();
	}
	
	/**
	 * Calculate statistics for this month.
	 */
	protected function calculateThisMonthStatistics() {
		// get this month's count
		$sql = "SELECT		SUM(counter)
			FROM		wcf1_visitor_daily
			WHERE		MONTH(date) = MONTH(CURDATE())
			AND		YEAR(date) = YEAR(CURDATE())
			AND		date < CURDATE()";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countThisMonth'] = (int) $statement->fetchColumn() + $this->statistics['countToday'];
	}
	
	/**
	 * Calculate statistics for this week.
	 */
	protected function calculateThisWeekStatistics() {
		// get this week's count
		$sql = "SELECT		SUM(counter)
			FROM		wcf1_visitor_daily
			WHERE		YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)
			AND		date < CURDATE()";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countThisWeek'] = (int) $statement->fetchColumn() + $this->statistics['countToday'];
	}
	
	/**
	 * Calculate statistics for this year.
	 * 
	 * @since	1.3.0
	 */
	protected function calculateThisYearStatistics() {
		// get this year's count
		$sql = "SELECT		SUM(counter)
			FROM		wcf1_visitor_daily
			WHERE		YEAR(date) = YEAR(CURDATE())
			AND		date < CURDATE()";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countThisYear'] = (int) $statement->fetchColumn() + $this->statistics['countToday'];
	}
	
	/**
	 * Calculate statistics for today.
	 */
	protected function calculateTodayStatistics() {
		// get today's count
		$sql = "SELECT		COUNT(*)
			FROM		wcf1_visitor
			WHERE		DATE(FROM_UNIXTIME(time)) = CURDATE()";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countToday'] = (int) $statement->fetchColumn();
		
		// get the most requested URIs
		$sql = "SELECT		requestURI, title, host, languageID, pageID, pageObjectID, COUNT(*) AS requestCount
			FROM		wcf1_visitor
			WHERE		DATE(FROM_UNIXTIME(time)) = CURDATE()
			GROUP BY	requestURI, title, host, languageID, pageID, pageObjectID
			ORDER BY	requestCount DESC, title";
		$statement = WCF::getDB()->prepareStatement($sql, 20);
		$statement->execute();
		$this->statistics['requestList'] = [];
		
		while ($row = $statement->fetchArray()) {
			$data = (object) $row;
			$data->requestCount = (int) $data->requestCount;
			$this->statistics['requestList'][] = $data;
		}
	}
	
	/**
	 * Calculate total statistics.
	 */
	protected function calculateTotalStatistics() {
		// get total count
		$sql = "SELECT		SUM(counter)
			FROM		wcf1_visitor_daily
			WHERE		date < CURDATE()";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countTotal'] = (int) $statement->fetchColumn() + $this->statistics['countToday'];
		
		// get the most requested URIs
		$sql = "SELECT		requestURI, title, host, languageID, pageID, pageObjectID, SUM(counter) AS requestCount
			FROM		wcf1_visitor_url
			GROUP BY	requestURI, title, host, languageID, pageID, pageObjectID
			ORDER BY	requestCount DESC, title";
		$statement = WCF::getDB()->prepareStatement($sql, 20);
		$statement->execute();
		$this->statistics['requestListAll'] = [];
		
		while ($row = $statement->fetchArray()) {
			$data = (object) $row;
			$data->requestCount = (int) $data->requestCount;
			$this->statistics['requestListAll'][] = $data;
		}
		
		// cumulate overall data with the data from today
		foreach ($this->statistics['requestListAll'] as &$allRequest) {
			foreach ($this->statistics['requestList'] as $request) {
				if (
					$allRequest->requestURI === $request->requestURI
					&& $allRequest->title === $request->title
					&& $allRequest->languageID === $request->languageID
					&& $allRequest->pageID === $request->pageID
					&& $allRequest->pageObjectID === $request->pageObjectID
				) {
					$allRequest->requestCount += $request->requestCount;
				}
			}
		}
	}
	
	/**
	 * Calculate statistics for yesterday.
	 */
	protected function calculateYesterdayStatistics() {
		// get yesterday's count
		$sql = "SELECT		COUNT(*)
			FROM		wcf1_visitor
			WHERE		DATE(FROM_UNIXTIME(time)) = CURDATE() - INTERVAL 1 DAY";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countYesterday'] = (int) $statement->fetchColumn();
	}
}
