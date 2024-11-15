{include file='header' pageTitle='wcf.acp.visitor.title'}

{include file='__visitorSystemStats' assign='visitorSystemStatsTemplate'}

<script data-relocate="true" src="{@$__wcf->getPath()}js/3rdParty/flot/jquery.flot.js"></script>
<script data-relocate="true" src="{@$__wcf->getPath()}js/3rdParty/flot/jquery.flot.time.js"></script>
<script data-relocate="true" src="{@$__wcf->getPath()}js/3rdParty/flot/jquery.flot.resize.js"></script>
<script data-relocate="true" src="{@$__wcf->getPath()}js/3rdParty/flot/jquery.flot.stack.js"></script>
<script data-relocate="true" src="{@$__wcf->getPath()}js/KM.ACP.Stat.VisitorChart.js?v={$assetVersion}"></script>
<script data-relocate="true">
	require(['Language'], function(Language) {
		Language.addObject({
			'wcf.acp.stat.timeFormat.daily': '{jslang}wcf.acp.stat.timeFormat.daily{/jslang}',
			'wcf.acp.stat.noData': '{jslang}wcf.acp.stat.noData{/jslang}',
			'wcf.acp.visitor.noVisit.browsers': '{jslang}wcf.acp.visitor.noVisit.browsers{/jslang}',
			'wcf.acp.visitor.noVisit.systems': '{jslang}wcf.acp.visitor.noVisit.systems{/jslang}',
			'wcf.acp.visitor.title.browsers': '{jslang}wcf.acp.visitor.title.browsers{/jslang}',
			'wcf.acp.visitor.title.systems': '{jslang}wcf.acp.visitor.title.systems{/jslang}',
		});
		
		new KM.ACP.Stat.VisitorChart('{@$visitorSystemStatsTemplate|encodeJS}');
	});
</script>

<style>
	.dataGridList {
		display: grid;
		grid-template-columns: 1fr;
		margin-bottom: 30px;
	}
	
	.dataGridList > .lastMonth {
		grid-row: 6;
	}
	
	.dataGridList > .lastWeek {
		grid-row: 4;
	}
	
	.dataGridList > .yesterday {
		grid-row: 2;
	}
	
	@media (min-width: 545px) {
		.dataGridList {
			grid-template-columns: repeat(2, auto);
		}
	}
	
	@media (min-width: 768px) {
		.dataGridList {
			grid-template-columns: repeat(5, auto);
		}
		
		.dataGridList > .lastMonth,
		.dataGridList > .lastWeek,
		.dataGridList > .yesterday {
			grid-row: auto;
		}
	}
	
	@media (min-width: 545px) and (max-width: 767px) {
		.dataGridList > .lastMonth {
			grid-column: 2;
			grid-row: 3;
		}
		
		.dataGridList > .lastWeek {
			grid-column: 2;
			grid-row: 2;
		}
		
		.dataGridList > .yesterday {
			grid-column: 2;
			grid-row: 1;
		}
	}
	
	dl.dataList {
		display: flex;
		flex-wrap: wrap;
	}
	
	.dataGridList > .dataList {
		margin-bottom: 10px;
	}
	
	dl.dataList > dt {
		flex: 0 0 100%;
		float: none;
		margin: 0;
		order: 2;
		text-align: center;
	}
	
	dl.dataList > dt::after {
		content: none;
	}
	
	dl.dataList > dd {
		align-items: center;
		display: flex;
		flex: 0 0 100%;
		flex-wrap: wrap;
		float: none;
		font-size: 34px;
		justify-content: center;
		text-align: center;
	}
	
	@media (min-width: 1440px) {
		dl.dataList > dd {
			text-align: right;
		}
	}
	
	@media (min-width: 1024px) {
		.flexSection {
			align-items: flex-start;
			display: flex;
			flex-wrap: nowrap;
			justify-content: space-between;
		}
		
		.flexSection > .section {
			flex: 0 0 calc(50% - 20px);
		}
		
		.flexSection > .section:first-child + .section {
			margin-top: 0;
		}
	}
	
	.trend {
		align-items: center;
		display: flex;
		flex-direction: column;
		font-size: 12px;
		margin-left: 10px;
	}
	
	.trend.negative,
	.trend.negative .icon {
		color: #a94442;
	}
	
	.trend.neutral,
	.trend.neutral .icon {
		color: #7d8287;
	}
	
	.trend.positive,
	.trend.positive .icon {
		color: #3c763d;
	}
	
	.trend > span {
		flex: 0 0 100%;
	}
	
	@media (min-width: 768px) {
		.number {
			flex: 0 0 100%;
		}
		
		.trend {
			margin-left: 0;
		}
	}
	
	@media (min-width: 1440px) {
		.number {
			flex: none;
		}
		
		.trend {
			margin-left: 10px;
		}
	}
</style>

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}wcf.acp.visitor.title{/lang}</h1>
		<p class="contentHeaderDescription">{lang}wcf.acp.visitor.lastUpdated{/lang}: {@$rebuildTime|time}</p>
	</div>
</header>

<div class="section">
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.acp.visitor.overview.title{/lang}</h2>
		<div class="dataGridList">
			<dl class="dataList plain today">
				<dt>{lang}wcf.acp.visitor.today{/lang}</dt>
				<dd>
					<span class="number">{#$countToday}</span>
					<span class="trend {$trends.today.type}">
						{if $trends.today.type == 'negative'}{icon size=24 name='caret-down' type='solid'}{elseif $trends.today.type == 'positive'}{icon size=24 name='caret-up' type='solid'}{else}{icon size=24 name='minus' type='solid'}{/if}
						<span class="percentage">{#$trends.today.percentage} %</span>
					</span>
				</dd>
			</dl>
			<dl class="dataList plain thisWeek">
				<dt>{lang}wcf.acp.visitor.thisWeek{/lang}</dt>
				<dd>
					<span class="number">{#$countThisWeek}</span>
					<span class="trend {$trends.thisWeek.type}">
						{if $trends.thisWeek.type == 'negative'}{icon size=24 name='caret-down' type='solid'}{elseif $trends.thisWeek.type == 'positive'}{icon size=24 name='caret-up' type='solid'}{else}{icon size=24 name='minus' type='solid'}{/if}
						<span class="percentage">{#$trends.thisWeek.percentage} %</span>
					</span>
				</dd>
			</dl>
			<dl class="dataList plain thisMonth">
				<dt>{lang}wcf.acp.visitor.thisMonth{/lang}</dt>
				<dd>
					<span class="number">{#$countThisMonth}</span>
					<span class="trend {$trends.thisMonth.type}">
						{if $trends.thisMonth.type == 'negative'}{icon size=24 name='caret-down' type='solid'}{elseif $trends.thisMonth.type == 'positive'}{icon size=24 name='caret-up' type='solid'}{else}{icon size=24 name='minus' type='solid'}{/if}
						<span class="percentage">{#$trends.thisMonth.percentage} %</span>
					</span>
				</dd>
			</dl>
			<dl class="dataList plain thisYear">
				<dt>{lang}wcf.acp.visitor.thisYear{/lang}</dt>
				<dd>
					<span class="number">{#$countThisYear}</span>
					<span class="trend {$trends.thisYear.type}">
						{if $trends.thisYear.type == 'negative'}{icon size=24 name='caret-down' type='solid'}{elseif $trends.thisYear.type == 'positive'}{icon size=24 name='caret-up' type='solid'}{else}{icon size=24 name='minus' type='solid'}{/if}
						<span class="percentage">{#$trends.thisYear.percentage} %</span>
					</span>
				</dd>
			</dl>
			<dl class="dataList plain average">
				<dt>{lang}wcf.acp.visitor.average{/lang}</dt>
				<dd>Ø {#$countAverage}</dd>
			</dl>
			<dl class="dataList plain yesterday">
				<dt>{lang}wcf.acp.visitor.yesterday{/lang}</dt>
				<dd>
					<span class="number">{#$countYesterday}</span>
					<span class="trend {$trends.yesterday.type}">
						{if $trends.yesterday.type == 'negative'}{icon size=24 name='caret-down' type='solid'}{elseif $trends.yesterday.type == 'positive'}{icon size=24 name='caret-up' type='solid'}{else}{icon size=24 name='minus' type='solid'}{/if}
						<span class="percentage">{#$trends.yesterday.percentage} %</span>
					</span>
				</dd>
			</dl>
			<dl class="dataList plain lastWeek">
				<dt>{lang}wcf.acp.visitor.lastWeek{/lang}</dt>
				<dd>
					<span class="number">{#$countLastWeek}</span>
					<span class="trend {$trends.lastWeek.type}">
						{if $trends.lastWeek.type == 'negative'}{icon size=24 name='caret-down' type='solid'}{elseif $trends.lastWeek.type == 'positive'}{icon size=24 name='caret-up' type='solid'}{else}{icon size=24 name='minus' type='solid'}{/if}
						<span class="percentage">{#$trends.lastWeek.percentage} %</span>
					</span>
				</dd>
			</dl>
			<dl class="dataList plain lastMonth">
				<dt>{lang}wcf.acp.visitor.lastMonth{/lang}</dt>
				<dd>
					<span class="number">{#$countLastMonth}</span>
					<span class="trend {$trends.lastMonth.type}">
						{if $trends.lastMonth.type == 'negative'}{icon size=24 name='caret-down' type='solid'}{elseif $trends.lastMonth.type == 'positive'}{icon size=24 name='caret-up' type='solid'}{else}{icon size=24 name='minus' type='solid'}{/if}
						<span class="percentage">{#$trends.lastMonth.percentage} %</span>
					</span>
				</dd>
			</dl>
			<dl class="dataList plain lastYear">
				<dt>{lang}wcf.acp.visitor.lastYear{/lang}</dt>
				<dd>
					<span class="number">{#$countLastYear}</span>
					<span class="trend {$trends.lastYear.type}">
						{if $trends.lastYear.type == 'negative'}{icon size=24 name='caret-down' type='solid'}{elseif $trends.lastYear.type == 'positive'}{icon size=24 name='caret-up' type='solid'}{else}{icon size=24 name='minus' type='solid'}{/if}
						<span class="percentage">{#$trends.lastYear.percentage} %</span>
					</span>
				</dd>
			</dl>
			<dl class="dataList plain total">
				<dt>{lang}wcf.acp.visitor.total{/lang}</dt>
				<dd>{#$countTotal}</dd>
			</dl>
		</div>
		
		<div id="chart" style="height: 400px"></div>
	</section>
</div>

<div class="section">
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.acp.visitor.settings.title{/lang}</h2>
		
		<dl>
			<dt><label for="startDate">{lang}wcf.acp.visitor.stat.period{/lang}</label></dt>
			<dd>
				<input type="date" id="startDate" name="startDate" value="{$startDate}" data-placeholder="{lang}wcf.acp.visitor.stat.period.start{/lang}" data-disable-clear="true">
				&ndash;
				<input type="date" id="endDate" name="endDate" value="{$endDate}" data-placeholder="{lang}wcf.acp.visitor.stat.period.end{/lang}" data-disable-clear="true">
			</dd>
			<dt><label for="startDate">{lang}wcf.acp.visitor.stat.display{/lang}</label></dt>
			<dd>
				<label><input type="checkbox" id="displayGuests" name="displayGuests" value="{$displayGuests}"{if $displayGuests} checked{/if}> {lang}wcf.acp.visitor.stat.displayGuests{/lang}</label>
				<label><input type="checkbox" id="displayRegistered" name="displayRegistered" value="{$displayRegistered}"{if $displayRegistered} checked{/if}> {lang}wcf.acp.visitor.stat.displayRegistered{/lang}</label>
			</dd>
		</dl>
	</section>
</div>

<div class="formSubmit">
	<button class="button buttonPrimary" id="visitorStatRefreshButton" type="submit">{lang}wcf.global.button.refresh{/lang}</button>
</div>

<div class="section flexSection">
	<section id="browserStats" class="section">
		<h2 class="sectionTitle">{lang}wcf.acp.visitor.browsers{/lang}</h2>
	</section>
	
	<section id="systemStats" class="section">
		<h2 class="sectionTitle">{lang}wcf.acp.visitor.systems{/lang}</h2>
	</section>
</div>

<div class="section flexSection">
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.acp.visitor.url.title.today{/lang}</h2>
		
		{hascontent}
			<table class="table">
				<thead>
					<tr>
						<th>{lang}wcf.acp.visitor.visitedUrls{/lang}</th>
						{if $isMultilingual}<th width="100">{lang}wcf.acp.visitor.language{/lang}</th>{/if}
						<th class="columnDigits" width="100">{lang}wcf.acp.visitor.count{/lang}</th>
					</tr>
				</thead>
				<tbody>
					{content}
						{foreach from=$requestList item=visitor}
							<tr>
								<td><a href="{$visitor->host}{$visitor->requestURI}">{$visitor->title}</a></td>
								{if $isMultilingual}<td>{if $visitor->language}{$visitor->language}{/if}</td>{/if}
								<td class="columnDigits">{#$visitor->requestCount}</td>
							</tr>
						{/foreach}
					{/content}
				</tbody>
			</table>
		{hascontentelse}
			<p class="info">{lang}wcf.acp.visitor.noVisit.today{/lang}</p>
		{/hascontent}
	</section>
	
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.acp.visitor.url.title.all{/lang}</h2>
		
		{hascontent}
			<table class="table">
				<thead>
					<tr>
						<th>{lang}wcf.acp.visitor.visitedUrls{/lang}</th>
						{if $isMultilingual}<th width="100">{lang}wcf.acp.visitor.language{/lang}</th>{/if}
						<th class="columnDigits" width="100">{lang}wcf.acp.visitor.count{/lang}</th>
					</tr>
				</thead>
				<tbody>
					{content}
						{foreach from=$requestListAll item=visitor}
							<tr>
								<td><a href="{$visitor->host}{$visitor->requestURI}">{$visitor->title}</a></td>
								{if $isMultilingual}<td>{$visitor->language}</td>{/if}
								<td class="columnDigits">{#$visitor->requestCount}</td>
							</tr>
						{/foreach}
					{/content}
				</tbody>
			</table>
		{hascontentelse}
			<p class="info">{lang}wcf.acp.visitor.noVisit.all{/lang}</p>
		{/hascontent}
	</section>
</div>

<div id="chartTooltip" class="balloonTooltip active" style="display: none"></div>

{include file='footer'}
