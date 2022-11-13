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
		
		$('#visitorStatRefreshButton').click($.proxy(this._refresh, this));
		
		this._refresh();
	},
	
	_refresh: function() {
		this._proxy.setOption('data', {
			className: 'wcf\\data\\visitor\\VisitorAction',
			actionName: 'getData',
			parameters: {
				dateGrouping: 'daily',
				displayGuests: $('#displayGuests').is(':checked') ? 1 : 0,
				displayRegistered: $('#displayRegistered').is(':checked') ? 1 : 0,
				endDate: $('#endDateDatePicker').val(),
				startDate: $('#startDateDatePicker').val()
			}
		});
		this._proxy.sendRequest();
	},
	
	_success: function(data) {
		var $minTickSize = [1, 'day'];
		var $timeFormat = WCF.Language.get('wcf.acp.stat.timeFormat.daily');
		var $data = [ ];
		
		for (var $key in data.returnValues.visitors) {
			var $row = data.returnValues.visitors[$key];
			
			for (var $i = 0; $i < $row.data.length; $i++) {
				$row.data[$i][0] *= 1000;
			}
			
			$data.push($row);
		}
		
		var options = {
			colors: [
				"#3a6d9c",
				"#b0c8e0",
			],
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
		
		var browserSection = document.getElementById('browserStats');
		var systemSection = document.getElementById('systemStats');
		
		this._generateSystemDataTable(browserSection, data.returnValues.browsers);
		this._generateSystemDataTable(systemSection, data.returnValues.systems);
	},
	
	/**
	 * Generate a new table for system data.
	 * 
	 * @param	{HTMLElement}	element
	 * @param	{Array}		data
	 * @private
	 */
	_generateSystemDataTable: function(element, data) {
		const oldInfo = element.querySelector('.info');
		const oldTable = element.querySelector('table');
		
		if (oldInfo) {
			oldInfo.remove();
		}
		
		if (oldTable) {
			oldTable.remove();
		}
		
		if (!data.length) {
			const info = document.createElement('p');
			
			info.classList.add('info');
			info.textContent = WCF.Language.get('wcf.acp.visitor.noData');
			element.appendChild(info);
			
			return;
		}
		
		const table = document.createElement('table');
		const thead = document.createElement('thead');
		const tbody = document.createElement('tbody');
		const theadRow = document.createElement('tr');
		const theadColumnCount = document.createElement('th');
		const theadColumnName = document.createElement('th');
		const theadColumnPercentage = document.createElement('th');
		
		theadColumnCount.classList.add('columnDigits');
		theadColumnCount.width = 100;
		theadColumnCount.textContent = WCF.Language.get('wcf.acp.visitor.count');
		theadColumnName.textContent = WCF.Language.get('wcf.acp.visitor.name');
		theadColumnPercentage.classList.add('columnDigits');
		theadColumnPercentage.classList.add('columnPercentage');
		theadColumnPercentage.width = 100;
		theadColumnPercentage.textContent = WCF.Language.get('wcf.acp.visitor.percentage');
		theadRow.appendChild(theadColumnName);
		theadRow.appendChild(theadColumnPercentage);
		theadRow.appendChild(theadColumnCount);
		thead.appendChild(theadRow);
		table.appendChild(thead);
		table.classList.add('table');
		
		for (const row of data) {
			const tableRow = document.createElement('tr');
			const columnName = document.createElement('td');
			const columnPercentage = document.createElement('td');
			const columnValue = document.createElement('td');
			
			columnName.textContent = row.label;
			columnPercentage.classList.add('columnDigits');
			columnPercentage.textContent = row.percentage + ' %';
			columnValue.classList.add('columnDigits');
			columnValue.textContent = row.data;
			tableRow.appendChild(columnName);
			tableRow.appendChild(columnPercentage);
			tableRow.appendChild(columnValue);
			tbody.appendChild(tableRow);
		}
		
		table.appendChild(tbody);
		element.appendChild(table);
	}
});
