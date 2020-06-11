<?php
use wcf\system\WCF;

/**
 * Update request URI length.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2011-2020 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 */
$sql = "UPDATE	wcf" . WCF_N . "_visitor
	SET	requestURI = SUBSTRING(requestURI, 1, 191)";

WCF::getDB()->prepareStatement($sql)->execute();

$sql = "UPDATE	wcf" . WCF_N . "_visitor_url
	SET	requestURI = SUBSTRING(requestURI, 1, 191)";

WCF::getDB()->prepareStatement($sql)->execute();
