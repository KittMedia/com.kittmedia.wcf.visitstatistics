<?php
namespace wcf\system\condition;
use wcf\data\condition\Condition;
use wcf\system\WCF;
use wcf\util\ArrayUtil;
use function in_array;
use function is_array;

/**
 * Implementation of ICondition for the type of statistics.
 * 
 * @author	Matthias Kittsteiner
 * @copyright	2021 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 * @since	1.2.0
 */
class VisitStatisticsTypeCondition extends AbstractSingleFieldCondition implements ICondition {
	/**
	 * @inheritDoc
	 */
	protected $fieldName = 'visitStatisticsType';
	
	/**
	 * The field value
	 * @var	array
	 */
	protected $fieldValue = [];
	
	/**
	 * Types to display
	 * @var	string[]
	 */
	protected $types = [
		'average',
		'lastMonth',
		'lastWeek',
		'thisMonth',
		'thisWeek',
		'today',
		'total',
		'yesterday'
	];
	
	/**
	 * @inheritDoc
	 */
	public function getData() {
		$data = [];
		
		if (!empty($this->fieldValue)) {
			$data['types'] = $this->fieldValue;
		}
		
		if (!empty($data)) {
			return $data;
		}
		
		return null;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getFieldElement() {
		$label = WCF::getLanguage()->get( 'wcf.acp.visitor.statisticType' );
		
		return <<<HTML
<dl>
	<dt>{$label}</dt>
	<dd>
		{$this->getOptionElements()}
		{$this->getErrorMessageElement()}
	</dd>
</dl>
HTML;
	}
	
	/**
	 * Return the option elements for the type selection.
	 * 
	 * @return	string
	 */
	protected function getOptionElements() {
		$returnValue = '';
		
		foreach ($this->types as $type) {
			$returnValue .= '<label><input type="checkbox" name="' . $this->fieldName . '[]" value="' . $type . '"' . (!empty($this->fieldValue[$type]) ? ' checked' : '') . '> ' . WCF::getLanguage()->get('wcf.acp.visitor.' . $type) . '</label>';
		}
		
		return $returnValue;
	}
	
	/**
	 * @inheritDoc
	 */
	public function readFormParameters() {
		if (isset($_POST[$this->fieldName]) && is_array($_POST[$this->fieldName])) {
			foreach($_POST[$this->fieldName] as $field) {
				$this->fieldValue[$field] = true;
			}
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function reset() {
		$this->fieldValue = [];
	}
	
	/**
	 * @inheritDoc
	 */
	public function setData(Condition $condition) {
		if (is_array($condition->types)) {
			$this->fieldValue = $condition->types;
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function validate() {
		foreach ($this->fieldValue as $value) {
			if (!in_array($value, $this->types)) {
				$this->errorMessage = 'wcf.global.form.error.noValidSelection';
				
				throw new UserInputException($this->fieldName, 'noValidSelection');
			}
		}
	}
}
