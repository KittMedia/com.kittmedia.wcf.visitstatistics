{include file='header' pageTitle='wcf.acp.visitor.title'}

<script data-relocate="true" src="{@$__wcf->getPath()}js/3rdParty/flot/jquery.flot.js"></script>
<script data-relocate="true" src="{@$__wcf->getPath()}js/3rdParty/flot/jquery.flot.time.js"></script>
<script data-relocate="true" src="{@$__wcf->getPath()}js/3rdParty/flot/jquery.flot.resize.js"></script>
<script data-relocate="true" src="{@$__wcf->getPath()}js/3rdParty/flot/jquery.flot.stack.js"></script>
<script data-relocate="true" src="{@$__wcf->getPath()}js/KM.ACP.Stat.VisitorChart.js"></script>
<script data-relocate="true">
	require(['Language'], function(Language) {
		Language.addObject({
			'wcf.acp.stat.timeFormat.daily': '{lang}wcf.acp.stat.timeFormat.daily{/lang}',
			'wcf.acp.stat.noData': '{lang}wcf.acp.stat.noData{/lang}',
		});
		
		new KM.ACP.Stat.VisitorChart();
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
			display: inline-grid;
			grid-template-columns: repeat(4, auto);
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
		flex: 0 0 100%;
		float: none;
		font-size: 34px;
		text-align: center;
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
				<dd>{$countToday}</dd>
			</dl>
			<dl class="dataList plain thisWeek">
				<dt>{lang}wcf.acp.visitor.thisWeek{/lang}</dt>
				<dd>{$countThisWeek}</dd>
			</dl>
			<dl class="dataList plain thisMonth">
				<dt>{lang}wcf.acp.visitor.thisMonth{/lang}</dt>
				<dd>{$countThisMonth}</dd>
			</dl>
			<dl class="dataList plain average">
				<dt>{lang}wcf.acp.visitor.average{/lang}</dt>
				<dd>Ø {$countAverage}</dd>
			</dl>
			<dl class="dataList plain yesterday">
				<dt>{lang}wcf.acp.visitor.yesterday{/lang}</dt>
				<dd>{$countYesterday}</dd>
			</dl>
			<dl class="dataList plain lastWeek">
				<dt>{lang}wcf.acp.visitor.lastWeek{/lang}</dt>
				<dd>{$countLastWeek}</dd>
			</dl>
			<dl class="dataList plain lastMonth">
				<dt>{lang}wcf.acp.visitor.lastMonth{/lang}</dt>
				<dd>{$countLastMonth}</dd>
			</dl>
			<dl class="dataList plain total">
				<dt>{lang}wcf.acp.visitor.total{/lang}</dt>
				<dd>{$countTotal}</dd>
			</dl>
		</div>
		
		<div id="chart" style="height: 400px"></div>
	</section>
	
	<section class="section">
		<h2 class="sectionTitle">{lang}wcf.acp.visitor.url.title{/lang}</h2>
		
		{hascontent}
			<table class="table">
				<thead>
					<tr>
						<th>{lang}wcf.acp.visitor.visitedUrls{/lang}</th>
						<th width="100">{lang}wcf.acp.visitor.language{/lang}</th>
						<th width="100">{lang}wcf.acp.visitor.count{/lang}</th>
					</tr>
				</thead>
				<tbody>
					{content}
						{foreach from=$requestList item=visitor}
							<tr>
								<td><a href="{$visitor->host}{$visitor->requestURI}">{@$visitor->title}</a></td>
								<td>{$visitor->language}</td>
								<td>{$visitor->requestCount}</td>
							</tr>
						{/foreach}
					{/content}
				</tbody>
			</table>
		{hascontentelse}
			<p class="info">{lang}wcf.acp.visitor.noVisit{/lang}</p>
		{/hascontent}
	</section>
</div>

<div id="chartTooltip" class="balloonTooltip active" style="display: none"></div>

{include file='footer'}
