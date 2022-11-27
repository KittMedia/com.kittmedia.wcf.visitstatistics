<?php
use wcf\system\database\table\column\DateDatabaseTableColumn;
use wcf\system\database\table\column\FloatDatabaseTableColumn;
use wcf\system\database\table\column\IntDatabaseTableColumn;
use wcf\system\database\table\column\NotNullVarchar255DatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\column\TinyintDatabaseTableColumn;
use wcf\system\database\table\DatabaseTable;
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
	DatabaseTable::create('wcf1_visitor_daily_system')
		->columns([
			ObjectIdDatabaseTableColumn::create('systemID'),
			DateDatabaseTableColumn::create('date'),
			NotNullVarchar255DatabaseTableColumn::create('browserName'),
			IntDatabaseTableColumn::create('browserVersion')
				->length(10),
			NotNullVarchar255DatabaseTableColumn::create('osName'),
			FloatDatabaseTableColumn::create('osVersion'),
			IntDatabaseTableColumn::create('counter')
				->length(10),
			TinyintDatabaseTableColumn::create('isRegistered')
				->defaultValue(0)
				->length(1)
				->notNull()
		])
];
