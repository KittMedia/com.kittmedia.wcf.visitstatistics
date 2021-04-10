<?php
namespace wcf\system\box;
use wcf\system\cache\builder\VisitorCacheBuilder;
use wcf\system\WCF;
use wcf\util\StringUtil;
use function array_merge;
use function count;
use function ucfirst;

/**
 * Implementation of IBoxController for visits.
 *  
 * @author	Matthias Kittsteiner
 * @copyright	2021 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 * @since	1.2.0
 */
class VisitStatisticsVisitsBoxController extends AbstractDatabaseObjectListBoxController {
	/**
	 * @inheritDoc
	 */
	protected $conditionDefinition = 'com.kittmedia.wcf.visitstatistics.box.visits.condition';
	
	/**
	 * @inheritDoc
	 */
	protected function loadContent() {
		$this->content = $this->getTemplate();
	}
	
	/**
	 * @inheritDoc
	 */
	protected function getObjectList() {}
	
	/**
	 * @inheritDoc
	 */
	protected function getTemplate() {
		$conditions = $this->getBox()->getConditions();
		$data = VisitorCacheBuilder::getInstance()->getData();
		$data['countAverage'] = StringUtil::formatNumeric($data['countAverage']);
		$data['countLastMonth'] = StringUtil::formatNumeric($data['countLastMonth']);
		$data['countLastWeek'] = StringUtil::formatNumeric($data['countLastWeek']);
		$data['countThisMonth'] = StringUtil::formatNumeric($data['countThisMonth']);
		$data['countThisWeek'] = StringUtil::formatNumeric($data['countThisWeek']);
		$data['countToday'] = StringUtil::formatNumeric($data['countToday']);
		$data['countTotal'] = StringUtil::formatNumeric($data['countTotal']);
		$data['countYesterday'] = StringUtil::formatNumeric($data['countYesterday']);
		$templateData = array_merge(
			$data,
			[
				'hideAverage' => false,
				'hideLastMonth' => false,
				'hideLastWeek' => false,
				'hideThisMonth' => false,
				'hideThisWeek' => false,
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
	public function hasContent() {
		return true;
	}
}
