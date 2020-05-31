<?php
use wcf\system\event\listener\VisitStatisticsDailyCleanUpCronjobListener;

/**
 * Update old statistics manually.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitors
 */
$cron = new VisitStatisticsDailyCleanUpCronjobListener();
$parameters = [];
$cron->execute(null, null, null, $parameters);
