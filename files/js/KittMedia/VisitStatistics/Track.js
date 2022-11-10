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
define(['Ajax'], function(Ajax) {
	"use strict";
	
	return {
		/**
		 * Initializes the tracking functionality.
		 * 
		 * @param	{object}	parameters
		 */
		init: function(parameters) {
			Ajax.apiOnce({
				data: {
					actionName: 'track',
					className: 'wcf\\data\\visitor\\VisitorAction',
					parameters: parameters,
				},
				silent: true
			});
		}
	}
});
