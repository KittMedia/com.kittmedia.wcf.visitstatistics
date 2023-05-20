<?php

namespace wcf\acp\page;

use DateInterval;
use DateTimeZone;
use wcf\data\package\PackageCache;
use wcf\data\page\PageCache;
use wcf\data\user\online\UserOnline;
use wcf\data\visitor\Visitor;
use wcf\data\visitor\VisitorAction;
use wcf\data\visitor\VisitorList;
use wcf\page\MultipleLinkPage;
use wcf\system\cache\builder\VisitorCacheBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\page\handler\IOnlineLocationPageHandler;
use wcf\system\WCF;
use wcf\util\DateUtil;

use const TIME_NOW;
use const TIMEZONE;

/**
 * Shows the visitor page in admin control panel.
 *
 * @author  Matthias Kittsteiner
 * @copyright   2022 KittMedia
 * @license Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package com.kittmedia.wcf.visitstatistics
 */
class VisitorPage extends MultipleLinkPage
{
    /**
     * @inheritDoc
     */
    public $activeMenuItem = 'wcf.acp.menu.link.visitor';

    /**
     * Data including statistics for today, yesterday and total
     * @var array
     */
    public $data = [];

    /**
     * Whether guest data should be displayed or not
     * @var bool
     */
    public $displayGuests = true;

    /**
     * Whether data of registered users should be displayed or not
     * @var bool
     */
    public $displayRegistered = true;

    /**
     * End date (yyyy-mm-dd)
     * @var string
     */
    public $endDate = '';

    /**
     * @inheritDoc
     */
    public $neededModules = ['MODULE_USER_VISITOR'];

    /**
     * @inheritDoc
     */
    public $neededPermissions = ['admin.management.canViewLog'];

    /**
     * @inheritDoc
     */
    public $objectListClassName = VisitorList::class;

    /**
     * Start date (yyyy-mm-dd)
     * @var string
     */
    public $startDate = '';

    /**
     * @inheritDoc
     */
    public function readData()
    {
        parent::readData();

        $this->data = VisitorCacheBuilder::getInstance()->getData();
        $userOnline = new UserOnline(WCF::getUser());

        // set default values
        $d = DateUtil::getDateTimeByTimestamp(TIME_NOW);
        $d->setTimezone(new DateTimeZone(TIMEZONE));
        $this->endDate = $d->format('Y-m-d');
        $d->sub(new DateInterval('P2M'));
        $this->startDate = $d->format('Y-m-d');

        if (empty($this->data['requestList'])) {
            return;
        }

        // prepare additional data
        if (!empty($this->data['requestList'])) {
            foreach ($this->data['requestList'] as &$request) {
                $request = $this->getRequestData($request, $userOnline);
            }
        }

        if (!empty($this->data['requestListAll'])) {
            foreach ($this->data['requestListAll'] as &$request) {
                $request = $this->getRequestData($request, $userOnline);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function assignVariables()
    {
        parent::assignVariables();

        if (!empty($this->data['requestList'])) {
            foreach ($this->data['requestList'] as &$request) {
                $request->requestCount = $request->requestCount;
            }
        }
        else {
            $this->data['requestList'] = [];
        }

        if (!empty($this->data['requestListAll'])) {
            foreach ($this->data['requestListAll'] as &$request) {
                $request->requestCount = $request->requestCount;
            }
        }
        else {
            $this->data['requestListAll'] = [];
        }

        WCF::getTPL()->assign([
            'assetVersion' => PackageCache::getInstance()->getPackageByIdentifier('com.kittmedia.wcf.visitstatistics')->updateDate,
            'countAverage' => $this->data['countAverage'],
            'countLastMonth' => $this->data['countLastMonth'],
            'countLastWeek' => $this->data['countLastWeek'],
            'countLastYear' => $this->data['countLastYear'],
            'countThisMonth' => $this->data['countThisMonth'],
            'countThisWeek' => $this->data['countThisWeek'],
            'countThisYear' => $this->data['countThisYear'],
            'countToday' => $this->data['countToday'],
            'countTotal' => $this->data['countTotal'],
            'countYesterday' => $this->data['countYesterday'],
            'displayGuests' => $this->displayGuests,
            'displayRegistered' => $this->displayRegistered,
            'endDate' => $this->endDate,
            'isMultilingual' => count(LanguageFactory::getInstance()->getContentLanguages()) > 1 ? true : false,
            'rebuildTime' => $this->data['rebuildTime'],
            'requestList' => $this->data['requestList'],
            'requestListAll' => $this->data['requestListAll'],
            'startDate' => $this->startDate,
            'trends' => $this->data['trends']
        ]);
    }

    /**
     * Get request data like language and title.
     *
     * @since   1.2.0
     *
     * @param   object      $request
     * @param   UserOnline  $userOnline
     * @return  object
     */
    private function getRequestData($request, $userOnline)
    {
        // get language
        if (!empty($request->languageID)) {
            $request->language = LanguageFactory::getInstance()->getLanguage($request->languageID);
        }
        else {
            $request->language = '';
        }

        // get title from user online
        // falls back to stored title
        if (empty($request->pageID)) {
            return $request;
        }

        if (!empty($request->pageObjectID)) {
            $userOnline->pageObjectID = $request->pageObjectID;
        }

        $page = PageCache::getInstance()->getPage($request->pageID);

        if (!Visitor::hideTitle($page)) {
            $title = $this->getTitle($page, $userOnline);
        }

        if (!empty($title)) {
            $request->title = preg_replace(VisitorAction::REGEX_FILTER_HTML, "$1", $title);
        }

        return $request;
    }

    /**
     * Get the location title of a page.
     *
     * @param   PageCache   $page
     * @param   UserOnline  $userOnline
     * @return  string
     */
    private function getTitle($page, $userOnline)
    {
        if ($page !== null) {
            if ($page->getHandler() !== null && $page->getHandler() instanceof IOnlineLocationPageHandler) {
                // refer to page handler
                return $page->getHandler()->getOnlineLocation($page, $userOnline);
            }
            elseif ($page->isVisible() && $page->isAccessible()) {
                return $page->getTitle();
            }
        }

        return '';
    }
}
