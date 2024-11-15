{if !OFFLINE || $__wcf->session->getPermission('admin.general.canViewPageDuringOfflineMode')}
	{if MODULE_USER_VISITOR && $__wcf->session->getPermission('user.profile.visitor.include') && $visitStatisticsIsCrawler|isset && !$visitStatisticsIsCrawler}
		<script data-relocate="true" async>
			require(['KittMedia/VisitStatistics/Track'], function(Track) {
				Track.init({
					url: '{if $canonicalURL|isset && $canonicalURL}{$canonicalURL|encodeJS}{else}{$visitStatisticsRequestURL|encodeJS}{/if}',
					title: '{if $visitStatisticsHideTitle}{lang}wcf.visitor.hidden{/lang}{else}{if $pageTitle}{@$pageTitle|encodeJS}{/if}{/if}',
					pageID: {$visitStatisticsPageID},
					pageObjectID: {$visitStatisticsPageObjectID},
					skip: {if $visitStatisticsSkipTracking}true{else}false{/if},
					hideURL: {if $visitStatisticsHideTitle}true{else}false{/if}
				});
			});
		</script>
	{/if}
{/if}
