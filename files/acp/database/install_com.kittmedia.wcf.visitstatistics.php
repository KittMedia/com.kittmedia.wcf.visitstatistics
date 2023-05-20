<?php

/**
 * Installs the database layout.
 *
 * @author  Matthias Kittsteiner
 * @copyright   2022 KittMedia
 * @license Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package com.kittmedia.wcf.visitstatistics
 */

use wcf\system\database\table\column\DateDatabaseTableColumn;
use wcf\system\database\table\column\FloatDatabaseTableColumn;
use wcf\system\database\table\column\IntDatabaseTableColumn;
use wcf\system\database\table\column\NotNullVarchar191DatabaseTableColumn;
use wcf\system\database\table\column\NotNullVarchar255DatabaseTableColumn;
use wcf\system\database\table\column\ObjectIdDatabaseTableColumn;
use wcf\system\database\table\column\TinyintDatabaseTableColumn;
use wcf\system\database\table\DatabaseTable;
use wcf\system\database\table\index\DatabaseTableForeignKey;
use wcf\system\database\table\index\DatabaseTableIndex;

return [
    DatabaseTable::create('wcf1_visitor')
        ->columns([
            ObjectIdDatabaseTableColumn::create('visitorID'),
            NotNullVarchar191DatabaseTableColumn::create('requestURI'),
            NotNullVarchar255DatabaseTableColumn::create('title'),
            NotNullVarchar255DatabaseTableColumn::create('host'),
            TinyintDatabaseTableColumn::create('isRegistered')
                ->defaultValue(0)
                ->length(1)
                ->notNull(),
            IntDatabaseTableColumn::create('languageID')
                ->length(10),
            IntDatabaseTableColumn::create('pageID')
                ->length(10),
            IntDatabaseTableColumn::create('pageObjectID')
                ->length(10),
            IntDatabaseTableColumn::create('time')
                ->length(10)
                ->notNull(),
            NotNullVarchar255DatabaseTableColumn::create('browserName'),
            IntDatabaseTableColumn::create('browserVersion')
                ->length(10),
            NotNullVarchar255DatabaseTableColumn::create('osName'),
            FloatDatabaseTableColumn::create('osVersion')
        ])
        ->indices([
            DatabaseTableIndex::create('time')
                ->columns(['time'])
                ->type(DatabaseTableIndex::DEFAULT_TYPE)
        ])
        ->foreignKeys([
            DatabaseTableForeignKey::create()
                ->columns(['languageID'])
                ->referencedColumns(['languageID'])
                ->referencedTable('wcf1_language')
                ->onDelete('SET NULL'),
            DatabaseTableForeignKey::create()
                ->columns(['pageID'])
                ->referencedColumns(['pageID'])
                ->referencedTable('wcf1_page')
                ->onDelete('SET NULL')
        ]),
    DatabaseTable::create('wcf1_visitor_daily')
        ->columns([
            ObjectIdDatabaseTableColumn::create('visitID'),
            DateDatabaseTableColumn::create('date')
                ->notNull(),
            IntDatabaseTableColumn::create('counter')
                ->length(10),
            TinyintDatabaseTableColumn::create('isRegistered')
                ->defaultValue(0)
                ->length(1)
                ->notNull()
        ])
        ->indices([
            DatabaseTableIndex::create('date')
                ->columns(['date', 'isRegistered'])
                ->type(DatabaseTableIndex::UNIQUE_TYPE)
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
        ]),
    DatabaseTable::create('wcf1_visitor_url')
        ->columns([
            ObjectIdDatabaseTableColumn::create('visitID'),
            NotNullVarchar191DatabaseTableColumn::create('requestURI'),
            NotNullVarchar255DatabaseTableColumn::create('title'),
            NotNullVarchar255DatabaseTableColumn::create('host'),
            IntDatabaseTableColumn::create('counter')
                ->length(10),
            TinyintDatabaseTableColumn::create('isRegistered')
                ->defaultValue(0)
                ->length(1)
                ->notNull(),
            IntDatabaseTableColumn::create('languageID')
                ->length(10),
            IntDatabaseTableColumn::create('pageID')
                ->length(10),
            IntDatabaseTableColumn::create('pageObjectID')
                ->length(10)
        ])
        ->indices([
            DatabaseTableIndex::create('requestURI')
                ->columns(['requestURI'])
                ->type(DatabaseTableIndex::DEFAULT_TYPE)
        ])
];
