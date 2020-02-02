<?php
namespace wcf\data\user\visitor;
use wcf\data\DatabaseObject;
use wcf\system\WCF;
use function filter_var;
use function preg_match;
use function strpos;
use const FILTER_SANITIZE_STRING;

/**
 * The User Visitor class.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitors
 */

class Visitor extends DatabaseObject {
	/**
	 * @inheritdoc
	 */
	protected static $databaseTableName = 'visitor';
	
	/**
	 * @inheritdoc
	 */
	protected static $databaseTableIndexName = 'visitorID';
	
	/**
	 * Skip tracking for certain visitors.
	 * 
	 * @return	bool
	 */
	public static function skipTracking() {
		// skip if there is no user agent
		if (empty($_SERVER['HTTP_USER_AGENT'])) {
			return true;
		}
		
		// skip if there is no valid user agent
		if (!filter_var($_SERVER['HTTP_USER_AGENT'], FILTER_SANITIZE_STRING)) {
			return true;
		}
		
		// skip if the user is identified as spider
		if (WCF::getSession()->spiderID) {
			return true;
		}
		
		// skip if the user agent lacks general information
		if (!preg_match( '/(?:Windows|Macintosh|Linux|iPhone|iPad)/', $_SERVER['HTTP_USER_AGENT'])) {
			return true;
		}
		
		// skip if it's an ajax request
		if (strpos(WCF::getSession()->requestURI, 'ajax-proxy/') !== false) {
			return true;
		}
		
		// skip if it's an attachment|media request
		if (strpos(WCF::getSession()->requestURI, 'attachment/') !== false) {
			return true;
		}
		
		// skip if it's an background queue request
		if (strpos(WCF::getSession()->requestURI, 'background-queue-perform/') !== false) {
			return true;
		}
		
		return false;
	}
}
