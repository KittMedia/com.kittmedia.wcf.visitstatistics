{if MODULE_USER_VISITOR && $__wcf->session->getPermission('user.profile.visitor.include')}
	<script data-relocate="true" async>
		require(['KittMedia/VisitStatistics/Track'], function(Track) {
			Track.init({
				requestURL: '{if $canonicalURL|isset && $canonicalURL}{$canonicalURL}{else}{$visitStatisticsRequestURL}{/if}',
				title: '{if $visitStatisticsHideTitle}{lang}wcf.visitor.hidden{/lang}{else}{if $pageTitle}{@$pageTitle}{/if}{/if}',
				pageID: {$visitStatisticsPageID},
				pageObjectID: {$visitStatisticsPageObjectID},
				skip: {if $visitStatisticsSkipTracking}true{else}false{/if},
				hideURL: {if $visitStatisticsHideTitle}true{else}false{/if}
			});
		});
	</script>
{/if}
