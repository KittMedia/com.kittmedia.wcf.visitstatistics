<?php
namespace wcf\system\box;
use wcf\system\cache\builder\VisitorCacheBuilder;
use wcf\system\WCF;
use function array_merge;
use function count;
use function is_array;
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
		$templateData = array_merge(VisitorCacheBuilder::getInstance()->getData(), ['position' => $this->getBox()->position]);
		
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
