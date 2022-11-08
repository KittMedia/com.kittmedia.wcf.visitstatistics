<?php
use wcf\system\database\table\column\IntDatabaseTableColumn;
use wcf\system\database\table\column\VarcharDatabaseTableColumn;
use wcf\system\database\table\PartialDatabaseTable;

/**
 * Updates the database layout during the update from 1.2 to 1.3.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2022 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 */
return [
	PartialDatabaseTable::create('wcf1_visitor')
		->columns([
			VarcharDatabaseTableColumn::create('browserName')
				->length(255)
				->notNull(),
			IntDatabaseTableColumn::create('browserVersion')
				->length(10),
			VarcharDatabaseTableColumn::create('osName')
				->length(255)
				->notNull(),
			IntDatabaseTableColumn::create('osVersion')
				->length(10)
		]),
];
