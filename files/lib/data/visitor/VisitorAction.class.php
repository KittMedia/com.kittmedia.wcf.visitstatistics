<?php
namespace wcf\data\visitor;
use DateTime;
use DateTimeZone;
use hisorange\BrowserDetect\Parser;
use wcf\data\AbstractDatabaseObjectAction;
use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\exception\UserInputException;
use wcf\system\language\LanguageFactory;
use wcf\system\WCF;
use wcf\util\DateUtil;
use wcf\util\StringUtil;
use wcf\util\Url;
use function html_entity_decode;
use function preg_match;
use function preg_replace;
use function round;
use function str_replace;
use function strcmp;
use function strtotime;
use function usort;
use function unserialize;
use const MODULE_USER_VISITOR;
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
	const REGEX_FILTER_HTML = '/\<\w[^<>]*?\>([^<>]+?)\<\/\w+?\>?|\<\/\w+?\>/';
	
	/**
	 * @inheritDoc
	 */
	public $allowGuestAccess = ['track'];
	
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
		$data = [
			'browsers' => [],
			'systems' => [],
			'visitors' => []
		];
		$sql = "SELECT		counter, date, isRegistered, additionalData
			FROM		" . Visitor::getDatabaseTableName() . "_daily
			" . $conditionBuilder . "
			GROUP BY	isRegistered, date, counter";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditionBuilder->getParameters());
		
		$data['visitors'][1]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits.user');
		$data['visitors'][2]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits.guest');
		$counts = [];
		$systems = [];
		
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
			
			if (!isset($systems[$row['dayTime']])) {
				$systems[$row['dayTime']] = [];
			}
			
			if ($row['isRegistered']) {
				$counts[$row['dayTime']]['user'] = $row['counter'];
				$systems[$row['dayTime']]['user'] = unserialize($row['additionalData']);
			}
			else {
				$counts[$row['dayTime']]['guest'] = $row['counter'];
				$systems[$row['dayTime']]['guest'] = unserialize($row['additionalData']);
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
						isRegistered,
						browserName,
						browserVersion,
						osName,
						osVersion
				FROM		" . Visitor::getDatabaseTableName() . "
				" . $conditionBuilder . "
				GROUP BY	isRegistered, date, browserName, browserVersion, osName, osVersion";
			$statement = WCF::getDB()->prepareStatement($sql);
			$statement->execute($conditionBuilder->getParameters());
			$counts[$todayTimestamp] = [];
			$systems[$todayTimestamp] = [];
			
			if ($this->parameters['displayGuests']) {
				$counts[$todayTimestamp]['guest'] = 0;
			}
			
			if ($this->parameters['displayRegistered']) {
				$counts[$todayTimestamp]['user'] = 0;
			}
		}
		
		while($row = $statement->fetchArray()) {
			if ($row['isRegistered'] && $this->parameters['displayRegistered']) {
				if (!isset($counts[$todayTimestamp]['user'])) {
					$counts[$todayTimestamp]['user'] = 0;
				}
				
				if (!isset($systems[$todayTimestamp]['user']['browsers'][$row['browserName'] . ' ' . $row['browserVersion']])) {
					$systems[$todayTimestamp]['user']['browsers'][$row['browserName'] . ' ' . $row['browserVersion']] = 0;
				}
				
				if (!isset($systems[$todayTimestamp]['user']['systems'][$row['osName'] . ' ' . $row['osVersion']])) {
					$systems[$todayTimestamp]['user']['systems'][$row['osName'] . ' ' . $row['osVersion']] = 0;
				}
				
				$counts[$todayTimestamp]['user'] += $row['counter'];
				$systems[$todayTimestamp]['user']['browsers'][$row['browserName'] . ' ' . $row['browserVersion']] += $row['counter'];
				$systems[$todayTimestamp]['user']['systems'][$row['osName'] . ' ' . $row['osVersion']] += $row['counter'];
			}
			else if ($this->parameters['displayGuests']) {
				if (!isset($counts[$todayTimestamp]['guest'])) {
					$counts[$todayTimestamp]['guest'] = 0;
				}
				
				if (!isset($systems[$todayTimestamp]['guest']['browsers'][$row['browserName'] . ' ' . $row['browserVersion']])) {
					$systems[$todayTimestamp]['guest']['browsers'][$row['browserName'] . ' ' . $row['browserVersion']] = 0;
				}
				
				if (!isset($systems[$todayTimestamp]['guest']['systems'][$row['osName'] . ' ' . $row['osVersion']])) {
					$systems[$todayTimestamp]['guest']['systems'][$row['osName'] . ' ' . $row['osVersion']] = 0;
				}
				
				$counts[$todayTimestamp]['guest'] += $row['counter'];
				$systems[$todayTimestamp]['guest']['browsers'][$row['browserName'] . ' ' . $row['browserVersion']] += $row['counter'];
				$systems[$todayTimestamp]['guest']['systems'][$row['osName'] . ' ' . $row['osVersion']] += $row['counter'];
			}
		}
		
		// sort data ASC by day
		ksort($counts);
		ksort($systems);
		
		// separate data for each date
		foreach ($counts as $dayTime => $count) {
			if ($this->parameters['displayRegistered']) {
				$data['visitors'][1]['data'][] = [
					$dayTime,
					$count['user'] ?? 0
				];
			}
			else {
				unset($data['visitors'][1]);
			}
			
			if ($this->parameters['displayGuests']) {
				$data['visitors'][2]['data'][] = [
					$dayTime,
					$count['guest'] ?? 0
				];
			}
			else {
				unset($data['visitors'][2]);
			}
		}
		
		$overall = 0;
		
		foreach ($systems as $dayTime => $userData) {
			$addCounts = true;
			
			foreach ($userData as $systemData) {
				if (empty($systemData)) {
					$addCounts = false;
					
					continue;
				}
				
				foreach ($systemData as $type => $systemCounts) {
					foreach ($systemCounts as $system => $count) {
						if (empty($data[$type][$system])) {
							$data[$type][$system] = $count;
						}
						else {
							$data[$type][$system] += $count;
						}
					}
				}
			}
			
			if ($addCounts) {
				if ($this->parameters['displayGuests']) {
					$overall += $counts[$dayTime]['guest'] ?? 0;
				}
				
				if ($this->parameters['displayRegistered']) {
					$overall += $counts[$dayTime]['user'] ?? 0;
				}
			}
		}
		
		foreach ($systems as $userData) {
			foreach ($userData as $systemData) {
				if (empty($systemData)) {
					continue;
				}
				
				foreach ($systemData as $type => $systemCounts) {
					foreach ($systemCounts as $system => $count) {
						if (!isset($data[$type][$system])) {
							continue;
						}
						
						$data[$type][] = [
							'data' => $data[$type][$system],
							'label' => $system,
							'percentage' => $overall ? round(100 / $overall * $data[$type][$system], 2) : 0
						];
						
						unset($data[$type][$system]);
					}
				}
			}
		}
		
		// sort DESC by data
		usort($data['browsers'], function($a, $b) {
			if ($a['data'] === $b['data']) {
				return strcmp($a['label'], $b['label']);
			}
			
			return $a['data'] < $b['data'];
		});
		usort($data['systems'], function($a, $b) {
			if ($a['data'] === $b['data']) {
				return strcmp($a['label'], $b['label']);
			}
			
			return $a['data'] < $b['data'];
		});
		
		return $data;
	}
	
	/**
	 * Add a tracking entry.
	 * 
	 * @since	1.3.0
	 */
	public function track() {
		if (!MODULE_USER_VISITOR) {
			return;
		}
		
		// get host
		if (WCF::getActivePath() !== null) {
			$urlParts = Url::parse(WCF::getActivePath());
			$host = $urlParts['scheme'] . '://' . $urlParts['host'];
		}
		else {
			$host = WCF::getActiveApplication()->domainName;
		}
		
		// get proper request URI
		if ($this->parameters['hideURL'] === 'true') {
			$requestURI = '';
		}
		else {
			$requestURI = str_replace($host, '', $this->parameters['requestURL']);
			
			// convert to UTF-8
			if (!StringUtil::isUTF8($requestURI)) {
				$requestURI = mb_convert_encoding($requestURI, 'UTF-8', 'UTF-8');
			}
		}
		
		require_once __DIR__ . '/../../system/api/visitStatistics/autoload.php';
		$browser = (new Parser())->detect();
		
		(new VisitorAction([], 'create', [
			'data' => [
				'requestURI' => StringUtil::truncate($requestURI, 191),
				'title' => StringUtil::truncate(html_entity_decode(preg_replace(self::REGEX_FILTER_HTML, "$1", $this->parameters['title'])), 255),
				'host' => StringUtil::truncate($host, 255),
				'isRegistered' => (int) (bool) WCF::getUser()->getObjectID(),
				'languageID' => (!empty(WCF::getLanguage()->getObjectID()) ? WCF::getLanguage()->getObjectID() : LanguageFactory::getInstance()->getDefaultLanguageID()),
				'pageID' => $this->parameters['pageID'] ?: null,
				'pageObjectID' => $this->parameters['pageObjectID'] ?: null,
				'time' => TIME_NOW,
				'browserName' => $browser->browserFamily(),
				'browserVersion' => $browser->browserVersionMajor(),
				'osName' => $browser->platformFamily(),
				'osVersion' => $browser->platformVersionMajor() . '.' . $browser->platformVersionMinor()
			]
		]))->executeAction();
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
	
	/**
	 * Validates the track action.
	 * 
	 * @since	1.3.0
	 */
	public function validateTrack() {}
}
