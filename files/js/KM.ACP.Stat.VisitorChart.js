/**
 * Initialize KM namespace
 */
KM = { };

/**
 * Initialize KM.ACP namespace
 */
KM.ACP = { };

/**
 * Initialize KM.ACP.Stat namespace
 */
KM.ACP.Stat = { };

/**
 * Shows the daily stat chart.
 */
KM.ACP.Stat.VisitorChart = Class.extend({
	init: function() {
		this._proxy = new WCF.Action.Proxy({
			success: $.proxy(this._success, this)
		});
		
		this._refresh();
	},
	
	_refresh: function() {
		this._proxy.setOption('data', {
			className: 'wcf\\data\\visitor\\VisitorAction',
			actionName: 'getData',
			parameters: {
				dateGrouping: 'daily',
			}
		});
		this._proxy.sendRequest();
	},
	
	_success: function(data) {
		var $minTickSize = [1, "day"];
		var $timeFormat = WCF.Language.get('wcf.acp.stat.timeFormat.daily');
		var options = {
			series: {
				lines: {
					show: true
				},
				points: {
					show: true
				}
			},
			grid: {
				hoverable: true
			},
			xaxis: {
				mode: "time",
				minTickSize: $minTickSize,
				timeformat: $timeFormat,
				monthNames: WCF.Language.get('__monthsShort')
			},
			yaxis: {
				min: 0,
				tickDecimals: 0,
				tickFormatter: function(val) {
					return WCF.String.addThousandsSeparator(val);
				}
			}
		};
		
		var $data = [ ];
		
		for (var $key in data.returnValues) {
			var $row = data.returnValues[$key];
			
			for (var $i = 0; $i < $row.data.length; $i++) {
				$row.data[$i][0] *= 1000;
			}
			
			$data.push($row);
		}
		
		$.plot("#chart", $data, options);
		
		require(['Ui/Alignment'], function (UiAlignment) {
			var span = elCreate('span');
			span.style.setProperty('position', 'absolute', '');
			document.body.appendChild(span);
			$("#chart").on("plothover", function(event, pos, item) {
				if (item) {
					span.style.setProperty('top', item.pageY + 'px', '');
					span.style.setProperty('left', item.pageX + 'px', '');
					$("#chartTooltip").html(item.series.xaxis.tickFormatter(item.datapoint[0], item.series.xaxis) + ', ' + WCF.String.formatNumeric(item.datapoint[1]) + ' ' + item.series.label).show();
					UiAlignment.set($("#chartTooltip")[0], span, {
						verticalOffset: 5,
						horizontal: 'center'
					});
				}
				else {
					$("#chartTooltip").hide();
				}
			});
		});
		
		if (!$data.length) {
			$('#chart').append('<p style="position: absolute; font-size: 1.2rem; text-align: center; top: 50%; margin-top: -20px; width: 100%">' + WCF.Language.get('wcf.acp.stat.noData') + '</p>');
		}
	}
});
