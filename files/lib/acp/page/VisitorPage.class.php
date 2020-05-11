<?php
namespace wcf\acp\page;
use wcf\data\page\PageCache;
use wcf\data\user\online\UserOnline;
use wcf\data\user\User;
use wcf\data\visitor\VisitorList;
use wcf\page\MultipleLinkPage;
use wcf\system\cache\builder\VisitorCacheBuilder;
use wcf\system\event\listener\VisitorListener;
use wcf\system\language\LanguageFactory;
use wcf\system\page\handler\IOnlineLocationPageHandler;
use wcf\system\WCF;
use function preg_replace;

/**
 * Shows the visitor page in admin control panel.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitors
 */
class VisitorPage extends MultipleLinkPage {
	/**
	 * @inheritDoc
	 */
	public $activeMenuItem = 'wcf.acp.menu.link.visitor';
	
	/**
	 * Data including statistics for today, yesterday and total
	 * @var	array
	 */
	public $data = [];
	
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
	 * @inheritDoc
	 */
	public function readData() {
		parent::readData();
		
		$this->data = VisitorCacheBuilder::getInstance()->getData();
		$user = new User(WCF::getUser()->getUserID(), null);
		$userOnline = new UserOnline($user);
		
		// prepare additional data
		foreach ($this->data['requestList'] as &$request) {
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
				continue;
			}
			
			if (!empty($request->pageObjectID)) {
				/** @var int $userOnline */
				$userOnline->pageObjectID = $request->pageObjectID;
			}
			
			$page = PageCache::getInstance()->getPage($request->pageID);
			$title = $this->getTitle($page, $userOnline);
			
			if (!empty($title)) {
				$request->title = preg_replace(VisitorListener::REGEX_FILTER_HTML, "$1", $title);
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign([
			'countAverage' => $this->data['countAverage'],
			'countLastMonth' => $this->data['countLastMonth'],
			'countLastWeek' => $this->data['countLastWeek'],
			'countThisMonth' => $this->data['countThisMonth'],
			'countThisWeek' => $this->data['countThisWeek'],
			'countToday' => $this->data['countToday'],
			'countTotal' => $this->data['countTotal'],
			'countYesterday' => $this->data['countYesterday'],
			'rebuildTime' => $this->data['rebuildTime'],
			'requestList' => (!empty($this->data['requestList']) ? $this->data['requestList'] : [])
		]);
	}
	
	/**
	 * Get the location title of a page.
	 * 
	 * @param	PageCache	$page
	 * @param	UserOnline	$userOnline
	 * @return	string
	 */
	private function getTitle($page, $userOnline) {
		if ($page !== null) {
			if ($page->getHandler() !== null && $page->getHandler() instanceof IOnlineLocationPageHandler) {
				// refer to page handler
				return $page->getHandler()->getOnlineLocation($page, $userOnline);
				
			}
			else if ($page->isVisible() && $page->isAccessible()) {
				return $page->getTitle();
			}
		}
		
		return '';
	}
}
