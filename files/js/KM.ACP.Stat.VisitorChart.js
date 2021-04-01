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
				endDate: $('#endDateDatePicker').val(),
				startDate: $('#startDateDatePicker').val(),
			}
		});
		$('#visitorStatRefreshButton').click($.proxy(this._refresh, this));
		this._proxy.sendRequest();
	},
	
	_success: function(data) {
		var $minTickSize = [1, 'day'];
		var $timeFormat = WCF.Language.get('wcf.acp.stat.timeFormat.daily');
		var $data = [ ];
		
		for (var $key in data.returnValues) {
			var $row = data.returnValues[$key];
			
			for (var $i = 0; $i < $row.data.length; $i++) {
				$row.data[$i][0] *= 1000;
			}
			
			$data.push($row);
		}
		
		var $lineGap = 1;
		// maximum line width: 40
		var $lineWidth = Math.min(Math.round((document.querySelector('.contentHeader + .section').clientWidth - 150) / $data[0].data.length - $lineGap, 0), 40);
		
		// set maximum/minimum date to prevent data overlapping with chart border
		var $minDate = new Date(Math.min($data[0].data[0][0], $data[1].data[0][0]));
		var $maxDate = new Date($data[0].data[$data[0].data.length - 1][0]);
		$minDate.setHours(-9, -10, 0, 0);
		$maxDate.setHours(13, 0, 0, 0);
		
		var options = {
			colors: [
				"#3a6d9c",
				"#b0c8e0",
			],
			series: {
				stack: true,
				bars: {
					lineWidth: $lineWidth,
					show: true
				},
				points: {
					fill: false,
					lineWidth: 0,
					radius: 0,
					show: true
				}
			},
			grid: {
				hoverable: true
			},
			xaxis: {
				max: $maxDate.getTime(),
				min: $minDate.getTime(),
				minTickSize: $minTickSize,
				mode: "time",
				monthNames: WCF.Language.get('__monthsShort'),
				timeformat: $timeFormat
			},
			yaxis: {
				min: 0,
				tickDecimals: 0,
				tickFormatter: function(val) {
					return WCF.String.addThousandsSeparator(val);
				}
			}
		};
		
		$.plot("#chart", $data, options);
		
		require(['Ui/Alignment'], function (UiAlignment) {
			var span = elCreate('span');
			span.style.setProperty('position', 'absolute', '');
			document.body.appendChild(span);
			$("#chart").on("plothover", function(event, pos, item) {
				if (item) {
					span.style.setProperty('top', item.pageY + 'px', '');
					span.style.setProperty('left', item.pageX + 'px', '');
					$("#chartTooltip").html(item.series.xaxis.tickFormatter(item.datapoint[0], item.series.xaxis) + ', ' + WCF.String.formatNumeric(item.datapoint[1] - item.datapoint[2]) + ' ' + item.series.label).show();
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
