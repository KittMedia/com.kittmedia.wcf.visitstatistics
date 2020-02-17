<?php
namespace wcf\data\visitor;
use kpps\system\cache\builder\VisitorCacheBuilder;
use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit user visits.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitors
 * 
 * @method	Visitor		getDecoratedObject()
 */
class VisitorEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = Visitor::class;
	
	/**
	 * @inheritDoc
	 */
	public static function resetCache() {
		VisitorCacheBuilder::getInstance()->reset();
	}
}
