{literal}
	<h2 class="sectionTitle">{$systemTitle}</h2>
	
	{if $systemData}
		<table class="table">
			<thead>
				<tr>
					<th>{lang}wcf.acp.visitor.name{/lang}</th>
					<th class="columnDigits columnPercentage" width="100">{lang}wcf.acp.visitor.percentage{/lang}</th>
					<th class="columnDigits" width="100">{lang}wcf.acp.visitor.count{/lang}</th>
				</tr>
			</thead>
			
			<tbody>
				{foreach from=$systemData item='data'}
					<tr>
						<td>{$data.label}</td>
						<td class="columnDigits columnPercentage">{$data.percentage}&thinsp;%</td>
						<td class="columnDigits">{$data.data}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	{else}
		<p class="info">{$systemNoData}</p>
	{/if}
{/literal}
