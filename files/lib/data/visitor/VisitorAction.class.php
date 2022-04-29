<?php
namespace wcf\data\visitor;
use DateTime;
use DateTimeZone;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\DateUtil;
use function preg_match;
use function strtotime;
use const TIME_NOW;
use const TIMEZONE;

/**
 * Provides functions for user visits.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2022 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 * 
 * @method	VisitorEditor[]		getObjects()
 * @method	Visitor			getSingleObject() 
 */
class VisitorAction extends AbstractDatabaseObjectAction {
	/**
	 * @inheritDoc
	 */
	protected $requireACP = ['getData'];
	
	/**
	 * @inheritDoc
	 */
	protected $resetCache = ['delete', 'toggle', 'update', 'updatePosition'];
	
	/**
	 * Return daily click statistics.
	 * 
	 * @return	mixed[]
	 */
	public function getData() {
		// get time zone
		$dateTime = DateUtil::getDateTimeByTimestamp(TIME_NOW);
		$dateTime->setTimezone(new DateTimeZone(TIMEZONE));
		// add timezone offset since it's being used inside JavaScript
		// where the timestamp represents the current timezone, not GMT
		$todayTimestamp = $dateTime->setTime(0, 0)->getTimestamp() + $dateTime->format('Z');
		
		$conditionBuilder = new PreparedStatementConditionBuilder();
		
		// display only guests
		if ($this->parameters['displayGuests'] && !$this->parameters['displayRegistered']) {
			$conditionBuilder->add('isRegistered = ?', [0]);
		}
		
		// display only registered users
		if (!$this->parameters['displayGuests'] && $this->parameters['displayRegistered']) {
			$conditionBuilder->add('isRegistered = ?', [1]);
		}
		
		$conditionBuilder->add('date BETWEEN ? AND ?', [$this->parameters['startDate'], $this->parameters['endDate']]);
		
		// get data
		$data = [];
		$sql = "SELECT		counter, date, isRegistered
			FROM		" . Visitor::getDatabaseTableName() . "_daily
			" . $conditionBuilder . "
			GROUP BY	isRegistered, date, counter";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditionBuilder->getParameters());
		
		$data[1]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits.user');
		$data[2]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits.guest');
		$counts = [];
		
		while ($row = $statement->fetchArray()) {
			// to timestamp
			$dayTime = DateTime::createFromFormat( 'Y-m-d', $row['date'] );
			$dayTime->setTimezone(new DateTimeZone(TIMEZONE));
			// add timezone offset since it's being used inside JavaScript
			// where the timestamp represents the current timezone, not GMT
			$row['dayTime'] = $dayTime->setTime(0, 0)->getTimestamp() + $dayTime->format('Z');
			
			if (!isset($counts[$row['dayTime']])) {
				$counts[$row['dayTime']] = [];
			}
			
			if ($row['isRegistered']) {
				$counts[$row['dayTime']]['user'] = $row['counter'];
			}
			else {
				$counts[$row['dayTime']]['guest'] = $row['counter'];
			}
		}
		
		$conditionBuilder = new PreparedStatementConditionBuilder();
		
		// display only guests
		if ($this->parameters['displayGuests'] && !$this->parameters['displayRegistered']) {
			$conditionBuilder->add('isRegistered = ?', [0]);
		}
		
		// display only registered users
		if (!$this->parameters['displayGuests'] && $this->parameters['displayRegistered']) {
			$conditionBuilder->add('isRegistered = ?', [1]);
		}
		
		$conditionBuilder->add('time >= UNIX_TIMESTAMP(CURDATE())');
		
		// get today's data
		if ( strtotime($this->parameters['endDate']) >= strtotime('today midnight') ) {
			$sql = "SELECT		COUNT(*) AS counter,
						DATE_FORMAT(FROM_UNIXTIME(time), '%Y-%m-%d') AS date,
						isRegistered
				FROM		" . Visitor::getDatabaseTableName() . "
				" . $conditionBuilder . "
				GROUP BY	isRegistered, date";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditionBuilder->getParameters());
			$counts[$todayTimestamp] = [];
			
			if ($this->parameters['displayGuests']) {
				$counts[$todayTimestamp]['guest'] = 0;
			}
			
			if ($this->parameters['displayRegistered']) {
				$counts[$todayTimestamp]['user'] = 0;
			}
		}
		
		while($row = $statement->fetchArray()) {
			if ($row['isRegistered'] && $this->parameters['displayRegistered']) {
				$counts[$todayTimestamp]['user'] = $row['counter'];
			}
			else if ($this->parameters['displayGuests']) {
				$counts[$todayTimestamp]['guest'] = $row['counter'];
			}
		}
		
		// sort data ASC by day
		ksort($counts);
		
		// separate data for each data
		foreach ($counts as $dayTime => $count) {
			if ($this->parameters['displayRegistered']) {
				$data[1]['data'][] = [
					$dayTime,
					$count['user'] ?? 0
				];
			}
			else {
				unset($data[1]);
			}
			
			if ($this->parameters['displayGuests']) {
				$data[2]['data'][] = [
					$dayTime,
					$count['guest'] ?? 0
				];
			}
			else {
				unset($data[2]);
			}
		}
		
		return $data;
	}
	
	/**
	 * Validates the getData action.
	 */
	public function validateGetData() {
		WCF::getSession()->checkPermissions(['admin.management.canViewLog']);
		
		// validate start date
		if (
			empty($this->parameters['startDate']) || !preg_match(
				'/^\d{4}\-\d{2}\-\d{2}$/',
				$this->parameters['startDate']
			)
		) {
			throw new UserInputException('startDate');
		}
		
		// validate end date
		if (
			empty($this->parameters['endDate']) || !preg_match(
				'/^\d{4}\-\d{2}\-\d{2}$/',
				$this->parameters['endDate']
			)
		) {
			throw new UserInputException('endDate');
		}
	}
}
