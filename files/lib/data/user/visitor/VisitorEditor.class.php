<?php
namespace wcf\data\user\visitor;
use wcf\data\DatabaseObjectEditor;

/**
 * Provides functions to edit user visits.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitors
 */

class VisitorEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = Visitor::class;
}
