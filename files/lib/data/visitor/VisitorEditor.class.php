<?php
namespace wcf\data\visitor;
use wcf\data\DatabaseObjectEditor;
use wcf\data\IEditableCachedObject;
use wcf\system\cache\builder\VisitorCacheBuilder;

/**
 * Provides functions to edit user visits.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 * 
 * @method	Visitor		getDecoratedObject()
 */
class VisitorEditor extends DatabaseObjectEditor implements IEditableCachedObject {
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
