{include file='header' pageTitle='wcf.acp.visitor.title'}

<script data-relocate="true" src="{@$__wcf->getPath()}js/3rdParty/flot/jquery.flot.js"></script>
<script data-relocate="true" src="{@$__wcf->getPath()}js/3rdParty/flot/jquery.flot.time.js"></script>
<script data-relocate="true" src="{@$__wcf->getPath()}js/3rdParty/flot/jquery.flot.resize.js"></script>
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
	.dataFlexList,
	dl.dataList {
		display: flex;
		flex-wrap: wrap;
	}
	
	.dataFlexList {
		margin-bottom: 30px;
	}
	
	.dataFlexList > .dataList {
		flex: 0 0 100%;
	}
	
	@media (min-width: 768px) {
		.dataFlexList > .dataList {
			flex: 0 1 auto;
		}
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
		<div class="dataFlexList">
			<dl class="dataList plain">
				<dt>{lang}wcf.acp.visitor.today{/lang}</dt>
				<dd>{$countToday}</dd>
			</dl>
			<dl class="dataList plain">
				<dt>{lang}wcf.acp.visitor.yesterday{/lang}</dt>
				<dd>{$countYesterday}</dd>
			</dl>
			<dl class="dataList plain">
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
						<th>{lang}wcf.acp.visitor.count{/lang}</th>
					</tr>
				</thead>
				<tbody>
					{content}
						{foreach from=$requestList item=visitor}
							<tr>
								<td><a href="{$visitor->host}{$visitor->requestURI}">{$visitor->title}</a></td>
								<td>{$visitor->requestCount}</td>
							</tr>
						{/foreach}
					{/content}
				</tbody>
			</table>
		{hascontentelse}
			<p>{lang}wcf.acp.visitor.noVisit{/lang}</p>
		{/hascontent}
	</section>
</div>

<div id="chartTooltip" class="balloonTooltip active" style="display: none"></div>

{include file='footer'}
