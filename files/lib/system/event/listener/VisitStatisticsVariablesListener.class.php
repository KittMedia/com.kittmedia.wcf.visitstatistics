<?php
namespace wcf\system\event\listener;
use wcf\data\visitor\Visitor;
use wcf\system\WCF;
use wcf\util\StringUtil;
use function explode;
use function implode;
use function preg_replace;

/**
 * Assign variables to templates.
 * 
 * @since	1.3.0
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2022 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 */
class VisitStatisticsVariablesListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		WCF::getTPL()->assign([
			'visitStatisticsRequestURL' => $this->removeQueryParameters($_SERVER['REQUEST_URI']),
			'visitStatisticsHideTitle' => Visitor::hideTitle(),
			'visitStatisticsPageID' => (int) (!empty(WCF::getActivePage()->pageID) ? WCF::getActivePage()->pageID : null),
			'visitStatisticsPageObjectID' => (int) (!empty($_REQUEST['id']) ? $_REQUEST['id'] : null),
			'visitStatisticsSkipTracking' => Visitor::skipTracking(),
		]);
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