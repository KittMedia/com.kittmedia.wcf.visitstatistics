<?php

namespace wcf\system\box;

use wcf\system\cache\builder\VisitorCacheBuilder;
use wcf\system\WCF;

/**
 * Implementation of IBoxController for visits.
 *
 * @author  Matthias Kittsteiner
 * @copyright   2021 KittMedia
 * @license Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package com.kittmedia.wcf.visitstatistics
 * @since   1.2.0
 */
class VisitStatisticsVisitsBoxController extends AbstractDatabaseObjectListBoxController
{
    /**
     * @inheritDoc
     */
    protected $conditionDefinition = 'com.kittmedia.wcf.visitstatistics.box.visits.condition';

    /**
     * @inheritDoc
     */
    protected function loadContent()
    {
        $this->content = $this->getTemplate();
    }

    /**
     * @inheritDoc
     */
    protected function getObjectList()
    {
    }

    /**
     * @inheritDoc
     */
    protected function getTemplate()
    {
        $conditions = $this->getBox()->getControllerConditions();
        $data = VisitorCacheBuilder::getInstance()->getData();
        $data['countAverage'] = $data['countAverage'];
        $data['countLastMonth'] = $data['countLastMonth'];
        $data['countLastWeek'] = $data['countLastWeek'];
        $data['countLastYear'] = $data['countLastYear'];
        $data['countThisMonth'] = $data['countThisMonth'];
        $data['countThisWeek'] = $data['countThisWeek'];
        $data['countThisYear'] = $data['countThisYear'];
        $data['countToday'] = $data['countToday'];
        $data['countTotal'] = $data['countTotal'];
        $data['countYesterday'] = $data['countYesterday'];
        $templateData = array_merge(
            $data,
            [
                'hideAverage' => false,
                'hideLastMonth' => false,
                'hideLastWeek' => false,
                'hideLastYear' => false,
                'hideThisMonth' => false,
                'hideThisWeek' => false,
                'hideThisYear' => false,
                'hideToday' => false,
                'hideTotal' => false,
                'hideYesterday' => false,
                'position' => $this->getBox()->position
            ]
        );

        if (count($conditions)) {
            $conditionData = reset($conditions)->conditionData;

            if (!empty($conditionData['types'])) {
                foreach ($conditionData['types'] as $condition => $value) {
                    $templateData['hide' . ucfirst($condition)] = $value;
                }
            }
        }

        return WCF::getTPL()->fetch(
            'boxVisitStatisticsVisits',
            'wcf',
            $templateData,
            true
        );
    }

    /**
     * @inheritDoc
     */
    public function hasContent()
    {
        return true;
    }
}
