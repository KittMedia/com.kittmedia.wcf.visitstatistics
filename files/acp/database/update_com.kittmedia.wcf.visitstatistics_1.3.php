<?php

/**
 * Add named database table indexes.
 *
 * @author Matthias Kittsteiner
 * @copyright 2023 KittMedia
 * @license Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package com.kittmedia.wcf.visitstatistics
 */

use wcf\system\database\table\index\DatabaseTableIndex;
use wcf\system\database\table\PartialDatabaseTable;

$blueprint = [
    PartialDatabaseTable::create('wcf1_visitor')
        ->indices([
            DatabaseTableIndex::create('')
                ->columns(['time'])
                ->type(DatabaseTableIndex::DEFAULT_TYPE)
        ]),
    PartialDatabaseTable::create('wcf1_visitor_daily')
        ->indices([
            DatabaseTableIndex::create('')
                ->columns(['date', 'isRegistered'])
                ->type(DatabaseTableIndex::UNIQUE_TYPE)
        ]),
    PartialDatabaseTable::create('wcf1_visitor_url')
        ->indices([
            DatabaseTableIndex::create('')
                ->columns(['requestURI'])
                ->type(DatabaseTableIndex::DEFAULT_TYPE)
        ])
];
$data = [];

foreach ($blueprint as $blueprintTable) {
    $data[] = PartialDatabaseTable::create($blueprintTable->getName())
        ->indices(array_map(static function ($index) {
            assert($index instanceof DatabaseTableIndex);

            return DatabaseTableIndex::create($index->getName())
                ->columns($index->getColumns())
                ->type($index->getType())
                ->drop();
        }, $blueprintTable->getIndices()));
}

return $data;
