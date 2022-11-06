<?php
namespace wcf\system\visitor;
use DateTimeZone;
use wcf\data\visitor\VisitorAction;
use wcf\system\IAJAXInvokeAction;
use wcf\system\language\LanguageFactory;
use wcf\system\SingletonFactory;
use wcf\system\WCF;
use wcf\util\DateUtil;
use wcf\util\StringUtil;
use function html_entity_decode;
use function mb_convert_encoding;
use function parse_url;
use function preg_replace;
use function str_replace;
use const MODULE_USER_VISITOR;
use const TIME_NOW;
use const TIMEZONE;

/**
 * The Visitor Handler.
 * 
 * @since	1.3.0
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2022 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 */
class VisitorHandler extends SingletonFactory implements IAJAXInvokeAction {
	const REGEX_FILTER_HTML = '/\<\w[^<>]*?\>([^<>]+?)\<\/\w+?\>?|\<\/\w+?\>/';
	
	/**
	 * List of methods which can be invoked via ajax
	 * @var		string[]
	 */
	public static $allowInvoke = ['track'];
	
	/**
	 * Add a tracking entry.
	 */
	public function track() {
		if (!MODULE_USER_VISITOR) {
			return;
		}
		
		$parameters = $_REQUEST['parameters'];
		
		// get host
		if (WCF::getActivePath() !== null) {
			$urlParts = parse_url(WCF::getActivePath());
			$host = $urlParts['scheme'] . '://' . $urlParts['host'];
		}
		else {
			$host = WCF::getActiveApplication()->domainName;
		}
		
		// get proper request URI
		if ($parameters['hideURL'] === 'true') {
			$requestURI = '';
		}
		else {
			$requestURI = str_replace($host, '', $parameters['requestURL']);
			
			// convert to UTF-8
			if (!StringUtil::isUTF8($requestURI)) {
				$requestURI = mb_convert_encoding($requestURI, 'UTF-8');
			}
		}
		
		(new VisitorAction([], 'create', [
			'data' => [
				'requestURI' => StringUtil::truncate($requestURI, 191),
				'title' => StringUtil::truncate(html_entity_decode(preg_replace(self::REGEX_FILTER_HTML, "$1", $parameters['title'])), 255),
				'host' => StringUtil::truncate($host, 255),
				'isRegistered' => WCF::getSession()->userID ? 1 : 0,
				'languageID' => (!empty(WCF::getLanguage()->getObjectID()) ? WCF::getLanguage()->getObjectID() : LanguageFactory::getInstance()->getDefaultLanguageID()),
				'pageID' => $parameters['pageID'] ?: null,
				'pageObjectID' => $parameters['pageObjectID'] ?: null,
				'time' => (DateUtil::getDateTimeByTimestamp(TIME_NOW))->setTimezone(new DateTimeZone(TIMEZONE))->getTimestamp()
			]
		]))->executeAction();
	}
}
