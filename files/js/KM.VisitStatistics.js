/**
 * Handles user tracking
 *
 * @author	Matthias Kittsteiner
 * @copyright	2022 KittMedia
 * @license	Free <https://shop.kittmedia.com/core/licenses/#licenseFree>
 * @package	com.kittmedia.wcf.visitstatistics
 */
var KM = {};

/**
 * Namespace for visit statistics.
 */
KM.VisitStatistics = Class.extend({
	/**
	 * action proxy
	 * @var	WCF.Action.Proxy
	 */
	_proxy: null,
	
	/**
	 * Initializes the tracking functionality.
	 */
	init: function(parameters) {
		this._proxy = new WCF.Action.Proxy({
			data: {
				actionName: 'track',
				className: 'wcf\\system\\visitor\\VisitorHandler',
				parameters: parameters
			},
			url: 'index.php/AJAXInvoke/?t=' + SECURITY_TOKEN + SID_ARG_2ND
		});
		this._proxy.sendRequest();
	}
});
