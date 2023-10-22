<?php

namespace wcf\system\event\listener;

use Jaybizzle\CrawlerDetect\CrawlerDetect;
use wcf\data\visitor\Visitor;
use wcf\system\session\SessionHandler;
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
 * @since   1.3.0
 *
 * @author  Matthias Kittsteiner
 * @copyright   2023 KittMedia
 * @license Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package com.kittmedia.wcf.visitstatistics
 */
final class VisitStatisticsVariablesListener implements IParameterizedEventListener
{
    /**
     * @inheritDoc
     */
    public function execute($eventObj, $className, $eventName, array &$parameters)
    {
        require_once __DIR__ . '/../../api/visitStatistics/autoload.php';

        $isCrawler = SessionHandler::getInstance()->getVar('visitStatisticsIsCrawler');

        if ($isCrawler === null) {
            $isCrawler = (new CrawlerDetect())->isCrawler();
            SessionHandler::getInstance()->register('visitStatisticsIsCrawler', $isCrawler);
        }

        $pageID = (int) !empty(WCF::getActivePage()->pageID) ? WCF::getActivePage()->pageID : 0;

        WCF::getTPL()->assign([
            'visitStatisticsRequestURL' => $this->removeQueryParameters($_SERVER['REQUEST_URI']),
            'visitStatisticsHideTitle' => Visitor::hideTitle(),
            'visitStatisticsIsCrawler' => $isCrawler,
            'visitStatisticsPageID' => $pageID,
            'visitStatisticsPageObjectID' => (int) (!empty($_REQUEST['id']) ? $_REQUEST['id'] : null),
            'visitStatisticsSkipTracking' => Visitor::skipTracking(),
        ]);
    }

    /**
     * Remove additional query parameters.
     *
     * @param   string      $requestURI
     * @return  string
     */
    private function removeQueryParameters($requestURI)
    {
        $parts = array_filter(array_map(function ($part) {
            if (
                \str_starts_with($part, 's=')
                || \str_starts_with($part, '?s=')
                || \str_starts_with($part, 't=')
                || \str_starts_with($part, '?t=')
            ) {
                return false;
            }

            return preg_replace('/(\?|&)(s|t)\=([^&?]+)/', '', $part);
        }, explode('&', $requestURI)));

        return implode('&', $parts);
    }
}
