<?php
namespace wcf\data\visitor;
use wcf\data\DatabaseObjectList;

/**
 * Represents a list of user visitors.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitors
 * 
 * @method 	Visitor		current()
 * @method 	Visitor[]	getObjects()
 * @method 	Visitor|null	search($objectID)
 */
class VisitorList extends DatabaseObjectList { }
