<?php
namespace wcf\system\box;
use wcf\system\cache\builder\VisitorCacheBuilder;
use wcf\system\WCF;
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
		$data = VisitorCacheBuilder::getInstance()->getData();
		$conditions = $this->getBox()->getConditions();
		$conditionData = reset($conditions)->conditionData;
		$templateData = [
			'countAverage' => $data['countAverage'],
			'countLastMonth' => $data['countLastMonth'],
			'countLastWeek' => $data['countLastWeek'],
			'countThisMonth' => $data['countThisMonth'],
			'countThisWeek' => $data['countThisWeek'],
			'countToday' => $data['countToday'],
			'countTotal' => $data['countTotal'],
			'countYesterday' => $data['countYesterday'],
			'position' => $this->getBox()->position,
			'rebuildTime' => $data['rebuildTime']
		];
		
		if (!empty($conditionData['types'])) {
			foreach ($conditionData['types'] as $condition => $value) {
				$templateData['hide' . ucfirst($condition)] = $value;
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
