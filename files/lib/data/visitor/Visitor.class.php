<?php

namespace wcf\data\visitor;

use wcf\action\AJAXProxyAction;
use wcf\action\BackgroundQueuePerformAction;
use wcf\data\DatabaseObject;
use wcf\data\package\PackageCache;
use wcf\data\page\Page;
use wcf\page\AttachmentPage;
use wcf\page\ConversationPage;
use wcf\page\MediaPage;
use wcf\system\WCF;

use function http_response_code;
use function is_object;
use function preg_match;
use function strpos;

/**
 * The User Visitor class.
 *
 * @author  Matthias Kittsteiner
 * @copyright   2022 KittMedia
 * @license Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package com.kittmedia.wcf.visitstatistics
 *
 * @property-read   integer     $visitorID unique ID of the visitor
 * @property-read   string      $requestURI requested URL of the visit
 * @property-read   boolean     $isRegistered `1` if the visit was from a registered user; otherwise `0`
 * @property-read   integer     $time unix timestamp where the request has been performed
 */
class Visitor extends DatabaseObject
{
    /**
     * @var array A list with user agents we want to skip
     */
    private static $blockList = [
        'bot',
        'slurp',
        'crawler',
        'spider',
        'curl',
        'facebook',
        'fetch',
        'wget'
    ];

    /**
     * @inheritDoc
     */
    protected static $databaseTableName = 'visitor';

    /**
     * @inheritDoc
     */
    protected static $databaseTableIndexName = 'visitorID';

    /**
     * Hide the titles for certain requests.
     *
     * @param   null|Page   $page
     * @return  bool
     */
    public static function hideTitle(?Page $page = null)
    {
        if ($page === null) {
            $page = WCF::getActivePage();
        }

        // hide title of conversations
        if (
            PackageCache::getInstance()->getPackageID('com.woltlab.wcf.conversation') !== null
            && $page
            && $page->controller === ConversationPage::class
        ) {
            return true;
        }

        return false;
    }

    /**
     * Skip tracking for certain visitors.
     *
     * @return  bool
     */
    public static function skipTracking()
    {
        // skip if there is no user agent
        if (empty($_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        // skip if there is a 403 or 404
        if (http_response_code() === 403 || http_response_code() === 404) {
            return true;
        }

        // skip if the user is identified as spider
        if (WCF::getSession()->spiderID) {
            return true;
        }

        // skip if the user agent lacks general information
        if (!preg_match('/(?:Windows|Macintosh|Linux|iPhone|iPad)/', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        }

        // skip basic bot user agents
        foreach (self::$blockList as $identifier) {
            if (strpos($_SERVER['HTTP_USER_AGENT'], $identifier) !== false) {
                return true;
            }
        }

        // skip if we cannot be sure that it's a properly registered page
        if (!is_object(WCF::getActivePage())) {
            return true;
        }

        // skip if it's an ajax request
        if (WCF::getActivePage()->controller === AJAXProxyAction::class) {
            return true;
        }

        // skip if it's an attachment request
        if (WCF::getActivePage()->controller === AttachmentPage::class) {
            return true;
        }

        // skip if it's a media request
        if (WCF::getActivePage()->controller === MediaPage::class) {
            return true;
        }

        // skip if it's a background queue request
        if (WCF::getActivePage()->controller === BackgroundQueuePerformAction::class) {
            return true;
        }

        // user group option
        if (!WCF::getSession()->getPermission('user.profile.visitor.include')) {
            return true;
        }

        return false;
    }
}
