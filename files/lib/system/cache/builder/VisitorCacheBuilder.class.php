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
		$this->getTodayStatistics();
		$this->getTotalStatistics();
		$this->getYesterdayStatistics();
		
		$this->statistics['rebuildTime'] = TIME_NOW;
		
		return $this->statistics;
	}
	
	/**
	 * Get statistics from today.
	 * 
	 * @return	mixed[]
	 */
	protected function getTodayStatistics() {
		// get today's count
		$sql = "SELECT		COUNT(*)
			FROM		".Visitor::getDatabaseTableName()." AS ".Visitor::getDatabaseTableAlias()."
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
			FROM		".Visitor::getDatabaseTableName()." AS ".Visitor::getDatabaseTableAlias()."
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
		
		return $this->statistics;
	}
	
	/**
	 * Get total statistics.
	 * 
	 * @return	mixed[]
	 */
	protected function getTotalStatistics() {
		// get total count
		$sql = "SELECT		COUNT(*)
			FROM		".Visitor::getDatabaseTableName()." AS ".Visitor::getDatabaseTableAlias();
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countTotal'] = StringUtil::formatNumeric($statement->fetchColumn());
		
		return $this->statistics;
	}
	
	/**
	 * Get statistics from yesterday.
	 * 
	 * @return	mixed[]
	 */
	protected function getYesterdayStatistics() {
		// get yesterday's count
		$sql = "SELECT		COUNT(*)
			FROM		".Visitor::getDatabaseTableName()." AS ".Visitor::getDatabaseTableAlias()."
			WHERE		DATE(FROM_UNIXTIME(time)) = CURDATE() - INTERVAL 1 DAY";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute();
		$this->statistics['countYesterday'] = StringUtil::formatNumeric($statement->fetchColumn());
		
		return $this->statistics;
	}
}
