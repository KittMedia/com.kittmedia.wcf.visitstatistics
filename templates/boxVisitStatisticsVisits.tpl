{if $position == 'sidebarLeft' || $position == 'sidebarRight'}
	<dl class="plain dataList">
		{if !$hideToday}
			<dt>{lang}wcf.visitor.visits.today{/lang}</dt>
			<dd>{$countToday}</dd>
		{/if}
		{if !$hideYesterday}
			<dt>{lang}wcf.visitor.visits.yesterday{/lang}</dt>
			<dd>{$countYesterday}</dd>
		{/if}
		{if !$hideThisWeek}
			<dt>{lang}wcf.visitor.visits.thisWeek{/lang}</dt>
			<dd>{$countThisWeek}</dd>
		{/if}
		{if !$hideLastWeek}
			<dt>{lang}wcf.visitor.visits.lastWeek{/lang}</dt>
			<dd>{$countLastWeek}</dd>
		{/if}
		{if !$hideThisMonth}
			<dt>{lang}wcf.visitor.visits.thisMonth{/lang}</dt>
			<dd>{$countThisMonth}</dd>
		{/if}
		{if !$hideLastMonth}
			<dt>{lang}wcf.visitor.visits.lastMonth{/lang}</dt>
			<dd>{$countLastMonth}</dd>
		{/if}
		{if !$hideAverage}
			<dt>{lang}wcf.visitor.visits.average{/lang}</dt>
			<dd>{$countAverage} {lang}wcf.visitor.visits.average.perDay{/lang}</dd>
		{/if}
		{if !$hideTotal}
			<dt>{lang}wcf.visitor.visits.total{/lang}</dt>
			<dd>{$countTotal}</dd>
		{/if}
		<dt>{lang}wcf.visitor.lastUpdated{/lang}</dt>
		<dd>{@$rebuildTime|time}</dd>
	</dl>
{else}
	<ul class="inlineList dotSeparated">
		{if !$hideToday}<li>{$countToday} {lang}wcf.visitor.visits.today{/lang}</li>{/if}
		{if !$hideYesterday}<li>{$countYesterday} {lang}wcf.visitor.visits.yesterday{/lang}</li>{/if}
		{if !$hideThisWeek}<li>{$countThisWeek} {lang}wcf.visitor.visits.thisWeek{/lang}</li>{/if}
		{if !$hideLastWeek}<li>{$countLastWeek} {lang}wcf.visitor.visits.lastWeek{/lang}</li>{/if}
		{if !$hideThisMonth}<li>{$countThisMonth} {lang}wcf.visitor.visits.thisMonth{/lang}</li>{/if}
		{if !$hideLastMonth}<li>{$countLastMonth} {lang}wcf.visitor.visits.lastMonth{/lang}</li>{/if}
		{if !$hideAverage}<li>Ø {$countAverage} {lang}wcf.visitor.visits.average.perDay{/lang}</li>{/if}
		{if !$hideTotal}<li>{$countTotal} {lang}wcf.visitor.visits.total{/lang}</li>{/if}
	</ul>
	<p>{lang}wcf.visitor.lastUpdated{/lang}: {@$rebuildTime|time}</p>
{/if}
