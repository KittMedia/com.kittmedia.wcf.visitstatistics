<?php
namespace wcf\system\event\listener;
use wcf\data\user\visitor\Visitor;
use wcf\data\user\visitor\VisitorAction;
use \wcf\system\WCF;
use function strpos;
use function substr;
use const TIME_NOW;
use const URL_OMIT_INDEX_PHP;

/**
 * Add new user visits.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitors
 */
class VisitorListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		if (Visitor::skipTracking()) return;
		
		(new VisitorAction([], 'create', [
			'data' => [
				'requestUri' => $this->removeQueryParameters(WCF::getSession()->requestURI),
				'isRegistered' => WCF::getSession()->userID ? 1 : 0,
				'time' => TIME_NOW
			]
		]))->executeAction();
	}
	
	/**
	 * Remove additional query parameters.
	 * 
	 * @param	string		$requestUri
	 * @return	string
	 */
	private function removeQueryParameters($requestUri) {
		$delimiter = '?';
		
		if (!URL_OMIT_INDEX_PHP) {
			$delimiter = '&';
		}
		
		if (!strpos($requestUri, $delimiter)) {
			return $requestUri;
		}
		
		return (string) substr($requestUri, 0, strpos($requestUri, $delimiter));
	}
	
}
