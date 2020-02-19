<?php
namespace wcf\data\visitor;
use wcf\action\AJAXProxyAction;
use wcf\action\BackgroundQueuePerformAction;
use wcf\data\DatabaseObject;
use wcf\page\AttachmentPage;
use wcf\page\ConversationPage;
use wcf\page\MediaPage;
use wcf\system\WCF;
use function filter_var;
use function http_response_code;
use function preg_match;
use const FILTER_SANITIZE_STRING;

/**
 * The User Visitor class.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitors
 * 
 * @property-read	integer		$visitorID unique ID of the visitor
 * @property-read	string		$requestURI requested URL of the visit
 * @property-read	boolean		$isRegistered `1` if the visit was from a registered user; otherwise `0`
 * @property-read	integer		$time unix timestamp where the request has been performed 
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
	 * Hide the titles for certain requests.
	 * 
	 * @return	bool
	 */
	public static function hideTitle() {
		// hide title of conversations
		if (WCF::getActivePage()->controller === ConversationPage::class) {
			return true;
		}
		
		return false;
	}
	
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
		
		// skip if there is a 403 or 404
		if (http_response_code() === 403 || http_response_code() === 404) {
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
		if (WCF::getActivePage()->controller === AJAXProxyAction::class) {
			return true;
		}
		
		// skip if it's an attachment request
		if (WCF::getActivePage()->controller === AttachmentPage::class) {
			return true;
		}
		
		// skip if it's an media request
		if (WCF::getActivePage()->controller === MediaPage::class) {
			return true;
		}
		
		// skip if it's an background queue request
		if (WCF::getActivePage()->controller === BackgroundQueuePerformAction::class) {
			return true;
		}
		
		// user group option
		if (WCF::getSession()->getPermission('user.profile.visitor.exclude')) {
			return true;
		}
		
		return false;
	}
}
