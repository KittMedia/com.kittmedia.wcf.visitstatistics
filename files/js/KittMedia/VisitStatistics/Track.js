/**
 * Handles user tracking.
 * 
 * @since	1.3.0
 *
 * @author	Matthias Kittsteiner
 * @copyright	2022 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 */
define(['Ajax', 'Core'], function(Ajax, Core) {
	"use strict";
	
	return {
		/**
		 * Initializes the tracking functionality.
		 * 
		 * @param	{object}	parameters
		 */
		init: function(parameters) {
			Ajax.api(this, {
				parameters: parameters,
				showLoadingOverlay: false
			});
		},
		
		/**
		 * @inheritDoc
		 */
		_ajaxSetup: function() {
			return {
				data: {
					actionName: 'track',
					className: 'wcf\\data\\visitor\\VisitorAction',
				}
			}
		}
	}
});
