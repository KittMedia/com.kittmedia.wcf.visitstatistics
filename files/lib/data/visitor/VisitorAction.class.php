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
use function array_map;
use function array_slice;
use function html_entity_decode;
use function preg_match;
use function preg_replace;
use function str_replace;
use function strcmp;
use function strtotime;
use function usort;
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
		$sql = "SELECT		counter, date, isRegistered
			FROM		" . Visitor::getDatabaseTableName() . "_daily
			" . $conditionBuilder . "
			GROUP BY	isRegistered, date, counter";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditionBuilder->getParameters());
		
		$data['visitors'][1]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits.user');
		$data['visitors'][2]['label'] = WCF::getLanguage()->get('wcf.acp.visitor.visits.guest');
		$counts = [];
		$systems = [];
		$systemsOverall = [
			'guest' => 0,
			'user' => 0
		];
		
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
		
		$sql = "SELECT		counter, date, isRegistered, browserName, browserVersion, osName, osVersion
			FROM		" . Visitor::getDatabaseTableName() . "_daily_system
			" . $conditionBuilder . "
			GROUP BY	isRegistered, date, isRegistered, browserName, browserVersion, osName, osVersion, counter";
		$statement = WCF::getDB()->prepareStatement($sql);
		$statement->execute($conditionBuilder->getParameters());
		
		while ($row = $statement->fetchArray()) {
			// to timestamp
			$dayTime = DateTime::createFromFormat( 'Y-m-d', $row['date'] );
			$dayTime->setTimezone(new DateTimeZone(TIMEZONE));
			// add timezone offset since it's being used inside JavaScript
			// where the timestamp represents the current timezone, not GMT
			$row['dayTime'] = $dayTime->setTime(0, 0)->getTimestamp() + $dayTime->format('Z');
			$systemData = [
				'browserName' => $row['browserName'],
				'browserVersion' => $row['browserVersion'],
				'osName' => $row['osName'],
				'osVersion' => $row['osVersion'],
				'counter' => $row['counter']
			];
			$systemsKey = $row['browserName'] . '-' . $row['browserVersion'] . '-' . $row['osName'] . '-' . $row['osVersion'];
			
			if ($row['isRegistered']) {
				$systems[$row['dayTime']]['user']['system'][$systemsKey] = $systemData;
				$systemsOverall['user'] += $row['counter'];
			}
			else {
				$systems[$row['dayTime']]['guest']['system'][$systemsKey] = $systemData;
				$systemsOverall['guest'] += $row['counter'];
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
			
			
			while ($row = $statement->fetchArray()) {
				$systemsKey = $row['browserName'] . '-' . $row['browserVersion'] . '-' . $row['osName'] . '-' . $row['osVersion'];
				$todaySystems = [
					$systemsKey => [
						'browserName' => $row['browserName'],
						'browserVersion' => $row['browserVersion'],
						'osName' => $row['osName'],
						'osVersion' => $row['osVersion'],
						'counter' => $row['counter']
					]
				];
				
				if ($row['isRegistered'] && $this->parameters['displayRegistered']) {
					if (!isset($counts[$todayTimestamp]['user'])) {
						$counts[$todayTimestamp]['user'] = 0;
					}
					
					$counts[$todayTimestamp]['user'] += $row['counter'];
					$systems[$todayTimestamp]['user'] = $todaySystems;
				}
				else if ($this->parameters['displayGuests']) {
					if (!isset($counts[$todayTimestamp]['guest'])) {
						$counts[$todayTimestamp]['guest'] = 0;
					}
					
					$counts[$todayTimestamp]['guest'] += $row['counter'];
					$systems[$todayTimestamp]['guest'] = $todaySystems;
				}
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
		
		$overall = $systemsOverall['guest'] + $systemsOverall['user'];
		
		foreach ($systems as $userData) {
			foreach ($userData as $systemData) {
				foreach ($systemData as $systemCounts) {
					foreach ($systemCounts as $system) {
						if (!isset($system['counter'])) {
							continue;
						}
						
						$browserKey = $system['browserName'] . ' ' . $system['browserVersion'];
						$systemKey = $system['osName'] . ' ' . $system['osVersion'];
						
						if (!isset($data['browsers'][$browserKey])) {
							$data['browsers'][$browserKey] = [
								'data' => $system['counter'],
								'label' => $system['browserName'] . ($system['browserVersion'] > 0 ? ' ' . $system['browserVersion'] : '' ),
								'percentage' => $overall ? StringUtil::formatNumeric(100 / $overall * $system['counter']) : 0
							];
						}
						else {
							$data['browsers'][$browserKey]['data'] += $system['counter'];
							$data['browsers'][$browserKey]['percentage'] = $overall ? StringUtil::formatNumeric(100 / $overall * $data['browsers'][$browserKey]['data']) : 0;
						}
						
						if (!isset($data['systems'][$systemKey])) {
							$data['systems'][$systemKey] = [
								'data' => $system['counter'],
								'label' => $system['osName'] . ($system['osVersion'] > 0 ? ' ' . $system['osVersion'] : '' ),
								'percentage' => $overall ? StringUtil::formatNumeric(100 / $overall * $system['counter']) : 0
							];
						}
						else {
							$data['systems'][$systemKey]['data'] += $system['counter'];
							$data['systems'][$systemKey]['percentage'] = $overall ? StringUtil::formatNumeric(100 / $overall * $data['systems'][$systemKey]['data']) : 0;
						}
					}
				}
			}
		}
		
		// limit output to 15 items
		$data['browsers'] = array_slice($data['browsers'], 0, 15);
		$data['systems'] = array_slice($data['systems'], 0, 15);
		
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
		
		// format data
		$data['browsers'] = array_map(function($data) {
			$data['data'] = StringUtil::formatNumeric($data['data']);
			
			return $data;
		}, $data['browsers']);
		$data['systems'] = array_map(function($data) {
			$data['data'] = StringUtil::formatNumeric($data['data']);
			
			return $data;
		}, $data['systems']);
		
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
