<?php

namespace wcf\system\cache\builder;

use DateTime;
use DateTimeZone;
use wcf\system\WCF;
use wcf\util\DateUtil;

use const TIME_NOW;
use const TIMEZONE;

/**
 * Caches visitor related statistics.
 *
 * @author  Matthias Kittsteiner
 * @copyright   2022 KittMedia
 * @license Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package com.kittmedia.wcf.visitstatistics
 */
class VisitorCacheBuilder extends AbstractCacheBuilder
{
    /**
     * @inheritDoc
     */
    protected $maxLifetime = 600;

    /**
     * Statistics
     * @var     mixed[]
     */
    protected $statistics = [
        'countAverage' => 0,
        'countLastMonth' => 0,
        'countLastWeek' => 0,
        'countLastYear' => 0,
        'countThisMonth' => 0,
        'countThisWeek' => 0,
        'countThisYear' => 0,
        'countToday' => 0,
        'countTotal' => 0,
        'countYesterday' => 0,
        'trends' => []
    ];

    /**
     * @inheritDoc
     */
    protected function rebuild(array $parameters)
    {
        $this->calculateTodayStatistics();
        $this->calculateLastMonthStatistics();
        $this->calculateLastWeekStatistics();
        $this->calculateLastYearStatistics();
        $this->calculateThisMonthStatistics();
        $this->calculateThisWeekStatistics();
        $this->calculateThisYearStatistics();
        $this->calculateTotalStatistics();
        $this->calculateYesterdayStatistics();
        $this->calculateAverageStatistics();
        $this->calculateTrendingStatistics();

        $this->statistics['rebuildTime'] = TIME_NOW;

        return $this->statistics;
    }

    /**
     * Calculate statistics for average.
     */
    protected function calculateAverageStatistics()
    {
        if (empty($this->statistics['countTotal'])) {
            return;
        }

        // get first date
        $sql = "SELECT      date
                FROM        wcf1_visitor_daily
                ORDER BY    date ASC";
        $statement = WCF::getDB()->prepare($sql, 1);
        $statement->execute();

        // get day difference
        $firstDate = new DateTime($statement->fetchColumn(), new DateTimeZone(TIMEZONE));
        $today = DateUtil::getDateTimeByTimestamp(TIME_NOW);
        $diffDays = $firstDate->diff($today);

        // calculate average
        if ($diffDays->days) {
            // add 1 day for today
            $this->statistics['countAverage'] = round($this->statistics['countTotal'] / ($diffDays->days + 1), 2);
        }
        else {
            $this->statistics['countAverage'] = $this->statistics['countTotal'];
        }
    }

    /**
     * Calculate statistics for last month.
     */
    protected function calculateLastMonthStatistics()
    {
        // get last month's count
        $sql = "SELECT  SUM(counter)
                FROM    wcf1_visitor_daily
                WHERE   MONTH(date) = MONTH(CURDATE() - INTERVAL 1 MONTH)
                AND     YEAR(date) = YEAR(CURDATE() - INTERVAL 1 MONTH)";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $this->statistics['countLastMonth'] = (int) $statement->fetchColumn();
    }

    /**
     * Calculate statistics for last week.
     */
    protected function calculateLastWeekStatistics()
    {
        // get last week's count
        $sql = "SELECT  SUM(counter)
                FROM    wcf1_visitor_daily
                WHERE   date >= CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) + 6 DAY
                AND     date < CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) - 1 DAY";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $this->statistics['countLastWeek'] = (int) $statement->fetchColumn();
    }

    /**
     * Calculate statistics for last year.
     *
     * @since   1.3.0
     */
    protected function calculateLastYearStatistics()
    {
        // get last year's count
        $sql = "SELECT  SUM(counter)
                FROM    wcf1_visitor_daily
                WHERE   YEAR(date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $this->statistics['countLastYear'] = (int) $statement->fetchColumn();
    }

    /**
     * Calculate statistics for this month.
     */
    protected function calculateThisMonthStatistics()
    {
        // get this month's count
        $sql = "SELECT  SUM(counter)
                FROM    wcf1_visitor_daily
                WHERE   MONTH(date) = MONTH(CURDATE())
                AND     YEAR(date) = YEAR(CURDATE())
                AND     date < CURDATE()";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $this->statistics['countThisMonth'] = (int) $statement->fetchColumn() + $this->statistics['countToday'];
    }

    /**
     * Calculate statistics for this week.
     */
    protected function calculateThisWeekStatistics()
    {
        // get this week's count
        $sql = "SELECT  SUM(counter)
                FROM    wcf1_visitor_daily
                WHERE   YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1)
                AND     date < CURDATE()";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $this->statistics['countThisWeek'] = (int) $statement->fetchColumn() + $this->statistics['countToday'];
    }

    /**
     * Calculate statistics for this year.
     *
     * @since   1.3.0
     */
    protected function calculateThisYearStatistics()
    {
        // get this year's count
        $sql = "SELECT  SUM(counter)
                FROM    wcf1_visitor_daily
                WHERE   YEAR(date) = YEAR(CURDATE())
                AND     date < CURDATE()";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $this->statistics['countThisYear'] = (int) $statement->fetchColumn() + $this->statistics['countToday'];
    }

    /**
     * Calculate statistics for today.
     */
    protected function calculateTodayStatistics()
    {
        // get today's count
        $sql = "SELECT  COUNT(*)
                FROM    wcf1_visitor
                WHERE   DATE(FROM_UNIXTIME(time)) = CURDATE()";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $this->statistics['countToday'] = (int) $statement->fetchColumn();

        // get the most requested URIs
        $sql = "SELECT      requestURI,
                            title,
                            host,
                            languageID,
                            pageID,
                            pageObjectID,
                            COUNT(*) AS requestCount
                FROM        wcf1_visitor
                WHERE       DATE(FROM_UNIXTIME(time)) = CURDATE()
                GROUP BY    requestURI, title, host, languageID, pageID, pageObjectID
                ORDER BY    requestCount DESC, title";
        $statement = WCF::getDB()->prepare($sql, 20);
        $statement->execute();
        $this->statistics['requestList'] = [];

        while ($row = $statement->fetchArray()) {
            $data = (object) $row;
            $data->requestCount = (int) $data->requestCount;
            $this->statistics['requestList'][] = $data;
        }
    }

    /**
     * Calculate total statistics.
     */
    protected function calculateTotalStatistics()
    {
        // get total count
        $sql = "SELECT		SUM(counter)
			FROM		wcf1_visitor_daily
			WHERE		date < CURDATE()";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $this->statistics['countTotal'] = (int) $statement->fetchColumn() + $this->statistics['countToday'];

        // get the most requested URIs
        $sql = "SELECT      requestURI, title, host, languageID, pageID, pageObjectID, SUM(counter) AS requestCount
                FROM        wcf1_visitor_url
                GROUP BY    requestURI, title, host, languageID, pageID, pageObjectID
                ORDER BY    requestCount DESC, title";
        $statement = WCF::getDB()->prepare($sql, 20);
        $statement->execute();
        $this->statistics['requestListAll'] = [];

        while ($row = $statement->fetchArray()) {
            $data = (object) $row;
            $data->requestCount = (int) $data->requestCount;
            $this->statistics['requestListAll'][] = $data;
        }

        // cumulate overall data with the data from today
        foreach ($this->statistics['requestListAll'] as &$allRequest) {
            foreach ($this->statistics['requestList'] as $request) {
                if (
                    $allRequest->requestURI === $request->requestURI
                    && $allRequest->title === $request->title
                    && $allRequest->languageID === $request->languageID
                    && $allRequest->pageID === $request->pageID
                    && $allRequest->pageObjectID === $request->pageObjectID
                ) {
                    $allRequest->requestCount += $request->requestCount;
                }
            }
        }
    }

    /**
     * Calculate statistics for trending data.
     */
    protected function calculateTrendingStatistics()
    {
        $this->statistics['trends'] = [
            'today' => [
                'type' => 'neutral',
                'percentage' => 0.00
            ],
            'lastMonth' => [
                'type' => 'neutral',
                'percentage' => 0.00
            ],
            'lastWeek' => [
                'type' => 'neutral',
                'percentage' => 0.00
            ],
            'lastYear' => [
                'type' => 'neutral',
                'percentage' => 0.00
            ],
            'thisMonth' => [
                'type' => 'neutral',
                'percentage' => 0.00
            ],
            'thisWeek' => [
                'type' => 'neutral',
                'percentage' => 0.00
            ],
            'thisYear' => [
                'type' => 'neutral',
                'percentage' => 0.00
            ],
            'yesterday' => [
                'type' => 'neutral',
                'percentage' => 0.00
            ]
        ];

        $sql = "SELECT  COUNT(*)
                FROM    wcf1_visitor
                WHERE   DATE(FROM_UNIXTIME(time)) = CURDATE()";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $todayCount = (int) $statement->fetchColumn();

        $yesterdayNow = new DateTime('1 day ago');
        $sql = "SELECT  COUNT(*)
                FROM    wcf1_visitor
                WHERE   DATE(FROM_UNIXTIME(time)) = CURDATE() - INTERVAL 1 DAY
                AND     time < ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$yesterdayNow->getTimestamp()]);
        $yesterdayNowCount = (int) $statement->fetchColumn();

        $lastMonthNow = new DateTime('1 month ago');
        $sql = "SELECT  SUM(counter)
                FROM    wcf1_visitor_daily
                WHERE   MONTH(date) = MONTH(CURDATE() - INTERVAL 1 MONTH)
                AND     YEAR(date) = YEAR(CURDATE() - INTERVAL 1 MONTH)
                AND     date <= ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$lastMonthNow->format('Y-m-d')]);
        $lastMonthCount = (int) $statement->fetchColumn();

        $monthBeforeLastMonthNow = new DateTime('-2 months');
        $sql = "SELECT  SUM(counter)
                FROM    wcf1_visitor_daily
                WHERE   MONTH(date) = MONTH(CURDATE() - INTERVAL 2 MONTH)
                AND     YEAR(date) = YEAR(CURDATE() - INTERVAL 2 MONTH)
                AND     date >= ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$monthBeforeLastMonthNow->format('Y-m-d')]);
        $monthBeforeLastMonthCount = (int) $statement->fetchColumn();

        $lastWeekNow = new DateTime('1 week ago');
        $sql = "SELECT  SUM(counter)
                FROM    wcf1_visitor_daily
                WHERE   date >= CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) + 6 DAY
                AND     date < CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) - 1 DAY
                AND     date <= ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$lastWeekNow->format('Y-m-d')]);
        $lastWeekCount = (int) $statement->fetchColumn();

        $weekBeforeLastWeekNow = new DateTime('-2 weeks');
        $sql = "SELECT  SUM(counter)
                FROM    wcf1_visitor_daily
                WHERE   date >= CURDATE() - INTERVAL DAYOFWEEK(CURDATE()) + 15 DAY
                AND     date <= ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$weekBeforeLastWeekNow->format('Y-m-d')]);
        $weekBeforeLastWeekCount = (int) $statement->fetchColumn();

        $lastYearNow = new DateTime('1 year ago');
        $sql = "SELECT  SUM(counter)
                FROM    wcf1_visitor_daily
                WHERE   YEAR(date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 YEAR))
                AND     date <= ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$lastYearNow->format('Y-m-d')]);
        $lastYearCount = (int) $statement->fetchColumn();

        $yearBeforeLastYearNow = new DateTime('-2 years');
        $sql = "SELECT  SUM(counter)
                FROM    wcf1_visitor_daily
                WHERE   YEAR(date) = YEAR(DATE_SUB(CURDATE(), INTERVAL 2 YEAR))
                AND     date <= ?";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute([$yearBeforeLastYearNow->format('Y-m-d')]);
        $yearBeforeLastYearCount = (int) $statement->fetchColumn();

        $sql = "SELECT  COUNT(*)
                FROM    wcf1_visitor
                WHERE   DATE(FROM_UNIXTIME(time)) = CURDATE() - INTERVAL 1 DAY";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $yesterdayCount = (int) $statement->fetchColumn();

        $sql = "SELECT  COUNT(*)
                FROM    wcf1_visitor
                WHERE   DATE(FROM_UNIXTIME(time)) = CURDATE() - INTERVAL 2 DAY";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $dayBeforeYesterdayCount = (int) $statement->fetchColumn();

        $percentage = $yesterdayNowCount ? 100 / $yesterdayNowCount * $todayCount : 100;
        $this->statistics['trends']['today']['type'] = ($percentage > 105 ? 'positive' : ($percentage < 95 ? 'negative' : 'neutral'));
        $this->statistics['trends']['today']['percentage'] = round($percentage - 100, 2);
        $percentage = $monthBeforeLastMonthCount ? 100 / $monthBeforeLastMonthCount * $lastMonthCount : 100;
        $this->statistics['trends']['lastMonth']['type'] = ($percentage > 105 ? 'positive' : ($percentage < 95 ? 'negative' : 'neutral'));
        $this->statistics['trends']['lastMonth']['percentage'] = round($percentage - 100, 2);
        $percentage = $weekBeforeLastWeekCount ? 100 / $weekBeforeLastWeekCount * $lastWeekCount : 100;
        $this->statistics['trends']['lastWeek']['type'] = ($percentage > 105 ? 'positive' : ($percentage < 95 ? 'negative' : 'neutral'));
        $this->statistics['trends']['lastWeek']['percentage'] = round($percentage - 100, 2);
        $percentage = $yearBeforeLastYearCount ? 100 / $yearBeforeLastYearCount * $lastYearCount : 100;
        $this->statistics['trends']['lastYear']['type'] = ($percentage > 105 ? 'positive' : ($percentage < 95 ? 'negative' : 'neutral'));
        $this->statistics['trends']['lastYear']['percentage'] = round($percentage - 100, 2);
        $percentage = $lastMonthCount ? 100 / $lastMonthCount * $this->statistics['countThisMonth'] : 100;
        $this->statistics['trends']['thisMonth']['type'] = ($percentage > 105 ? 'positive' : ($percentage < 95 ? 'negative' : 'neutral'));
        $this->statistics['trends']['thisMonth']['percentage'] = round($percentage - 100, 2);
        $percentage = $lastWeekCount ? 100 / $lastWeekCount * $this->statistics['countThisWeek'] : 100;
        $this->statistics['trends']['thisWeek']['type'] = ($percentage > 105 ? 'positive' : ($percentage < 95 ? 'negative' : 'neutral'));
        $this->statistics['trends']['thisWeek']['percentage'] = round($percentage - 100, 2);
        $percentage = $lastYearCount ? 100 / $lastYearCount * $this->statistics['countThisYear'] : 100;
        $this->statistics['trends']['thisYear']['type'] = ($percentage > 105 ? 'positive' : ($percentage < 95 ? 'negative' : 'neutral'));
        $this->statistics['trends']['thisYear']['percentage'] = round($percentage - 100, 2);
        $percentage = $dayBeforeYesterdayCount ? 100 / $dayBeforeYesterdayCount * $yesterdayCount : 100;
        $this->statistics['trends']['yesterday']['type'] = ($percentage > 105 ? 'positive' : ($percentage < 95 ? 'negative' : 'neutral'));
        $this->statistics['trends']['yesterday']['percentage'] = round($percentage - 100, 2);
    }

    /**
     * Calculate statistics for yesterday.
     */
    protected function calculateYesterdayStatistics()
    {
        // get yesterday's count
        $sql = "SELECT  COUNT(*)
                FROM    wcf1_visitor
                WHERE   DATE(FROM_UNIXTIME(time)) = CURDATE() - INTERVAL 1 DAY";
        $statement = WCF::getDB()->prepare($sql);
        $statement->execute();
        $this->statistics['countYesterday'] = (int) $statement->fetchColumn();
    }
}
