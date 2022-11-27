<?php

use wcf\system\database\table\column\FloatDatabaseTableColumn;
use wcf\system\database\table\column\IntDatabaseTableColumn;
use wcf\system\database\table\column\MediumtextDatabaseTableColumn;
use wcf\system\database\table\column\NotNullVarchar255DatabaseTableColumn;
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
			NotNullVarchar255DatabaseTableColumn::create('browserName'),
			IntDatabaseTableColumn::create('browserVersion')
				->length(10),
			NotNullVarchar255DatabaseTableColumn::create('osName'),
			FloatDatabaseTableColumn::create('osVersion')
		]),
	PartialDatabaseTable::create('wcf1_visitor_daily')
		->columns([
			MediumtextDatabaseTableColumn::create('additionalData')
		]),
];
