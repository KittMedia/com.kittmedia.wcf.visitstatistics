<?php
namespace wcf\acp\page;
use wcf\data\visitor\VisitorList;
use wcf\page\MultipleLinkPage;
use wcf\system\cache\builder\VisitorCacheBuilder;
use wcf\system\WCF;

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
	}
	
	/**
	 * @inheritDoc
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign([
			'countToday' => $this->data['countToday'],
			'countTotal' => $this->data['countTotal'],
			'countYesterday' => $this->data['countYesterday'],
			'rebuildTime' => $this->data['rebuildTime'],
			'requestList' => (!empty($this->data['requestList']) ? $this->data['requestList'] : [])
		]);
	}
}
