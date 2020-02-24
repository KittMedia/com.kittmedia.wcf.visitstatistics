<?php
namespace wcf\system\cache\builder;
use wcf\data\visitor\Visitor;
use \wcf\system\WCF;
use wcf\util\StringUtil;
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
		$this->calculateTodayStatistics();
		$this->calculateTotalStatistics();
		$this->calculateYesterdayStatistics();
		
		$this->statistics['rebuildTime'] = TIME_NOW;
		
		return $this->statistics;
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
		
		// change sql mode
		$sql = "SELECT		@@sql_mode";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$currentSqlMode = $statement->fetchColumn();
		$sqlModeStatement = WCF::getDB()->prepareStatement("SET SESSION sql_mode = ?");
		$sqlModeStatement->execute(['TRADITIONAL']);
		
		// get the most requested URIs
		$sql = "SELECT		*,
					COUNT(requestURI) AS requestCount
			FROM		".Visitor::getDatabaseTableName()."
			WHERE		DATE(FROM_UNIXTIME(time)) = CURDATE()
			GROUP BY	requestURI
			ORDER BY	requestCount DESC, title";
		$statement = WCF::getDB()->prepareStatement($sql, 20);
		$statement->execute();
		
		while ($row = $statement->fetchArray()) {
			$this->statistics['requestList'][] = (object) $row;
		}
		
		// restore sql mode
		$sqlModeStatement->execute([$currentSqlMode]);
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
