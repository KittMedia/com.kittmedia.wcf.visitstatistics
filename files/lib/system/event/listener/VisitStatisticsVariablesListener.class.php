<?php
namespace wcf\system\event\listener;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use wcf\data\visitor\Visitor;
use wcf\system\WCF;
use wcf\util\StringUtil;
use function array_filter;
use function array_map;
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
final class VisitStatisticsVariablesListener implements IParameterizedEventListener {
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		require_once __DIR__ . '/../../api/visitStatistics/autoload.php';
		
		WCF::getTPL()->assign([
			'visitStatisticsRequestURL' => $this->removeQueryParameters($_SERVER['REQUEST_URI']),
			'visitStatisticsHideTitle' => Visitor::hideTitle(),
			'visitStatisticsIsCrawler' => (new CrawlerDetect())->isCrawler(),
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
		$parts = array_filter(array_map(function($part) {
			if (
				StringUtil::startsWith($part, 's=')
				|| StringUtil::startsWith($part, '?s=')
				|| StringUtil::startsWith($part, 't=')
				|| StringUtil::startsWith($part, '?t=')
			) {
				return false;
			}
			
			return preg_replace('/(\?|&)(s|t)\=([^&?]+)/', '', $part);
		}, explode('&', $requestURI)));
		
		return implode('&', $parts);
	}
}
