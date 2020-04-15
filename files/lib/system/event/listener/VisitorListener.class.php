<?php
namespace wcf\system\event\listener;
use wcf\data\visitor\Visitor;
use wcf\data\visitor\VisitorAction;
use \wcf\system\WCF;
use wcf\util\StringUtil;
use function explode;
use function implode;
use function mb_convert_encoding;
use function parse_url;
use function preg_replace;
use const TIME_NOW;

/**
 * Add new user visits.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitors
 */
class VisitorListener implements IParameterizedEventListener {
	const REGEX_FILTER_HTML = '/\<\w[^<>]*?\>([^<>]+?\<\/\w+?\>)?|\<\/\w+?\>/';
	
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		if (!MODULE_USER_VISITOR) return;
		if (Visitor::skipTracking()) return;
		
		// get title
		$title = preg_replace(self::REGEX_FILTER_HTML, '', WCF::getTPL()->get('contentTitle'));
		
		if (!$title) {
			$title = preg_replace(self::REGEX_FILTER_HTML, '', WCF::getTPL()->get('pageTitle'));
		}
		
		if (Visitor::hideTitle()) {
			$title = WCF::getLanguage()->get('wcf.visitor.hidden');
		}
		
		// get host
		if (WCF::getActivePath() !== null) {
			$urlParts = parse_url(WCF::getActivePath());
			$host = $urlParts['scheme'] . '://' . $urlParts['host'];
		}
		else {
			$host = WCF::getActiveApplication()->domainName;
		}
		
		// convert to UTF-8
		$requestURI = (!StringUtil::isUTF8($_SERVER['REQUEST_URI']) ? mb_convert_encoding($_SERVER['REQUEST_URI'], 'UTF-8') : $_SERVER['REQUEST_URI']);
		$title = (!StringUtil::isUTF8($title) ? mb_convert_encoding($title, 'UTF-8') : $title);
		
		(new VisitorAction([], 'create', [
			'data' => [
				'requestURI' => StringUtil::truncate((!Visitor::hideTitle() ? $this->removeQueryParameters($requestURI) : ''), 255),
				'title' => StringUtil::truncate($title, 255),
				'host' => StringUtil::truncate($host, 255),
				'isRegistered' => WCF::getSession()->userID ? 1 : 0,
				'time' => TIME_NOW
			]
		]))->executeAction();
	}
	
	/**
	 * Remove additional query parameters.
	 * 
	 * @param	string		$requestURI
	 * @return	string
	 */
	private function removeQueryParameters($requestURI) {
		$parts = explode('&', $requestURI);
		
		foreach ($parts as $key => &$part) {
			if (
				StringUtil::startsWith($part, 's=')
				|| StringUtil::startsWith($part, '?s=')
				|| StringUtil::startsWith($part, 't=')
				|| StringUtil::startsWith($part, '?t=')
			) {
				unset($parts[$key]);
			}
			
			$part = preg_replace('/(\?|&)(s|t)\=([^&?]+)/', '', $part);
		}
		
		return implode('&', $parts);
	}
	
}
