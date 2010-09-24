<br style="clear: all"><div class="infoheader">Latest Announcements</div>
{section name=story loop=$Stories}
{if $Stories[story].expanded ne 0}
{assign var='StyleHidden' value=''}
{assign var='StyleVisible' value='display: none'}
{else}
{assign var='StyleHidden' value='display: none'}
{assign var='StyleVisible' value=''}
{/if}
<img src="/static/img/menu-arrow-down.gif" style="{$StyleHidden}" id="dnarr-{$Stories[story].chunkname}"><img src="/static/img/menu-arrow-right.gif" style="{$StyleVisible}" id="rtarr-{$Stories[story].chunkname}">
<a href="javascript:toggleItems('{$Stories[story].chunkname}', 'dnarr-{$Stories[story].chunkname}', 'rtarr-{$Stories[story].chunkname}')">{$Stories[story].title}</a> <span class="storypostdate">posted by {$Stories[story].author} {$Stories[story].postdate}</span><br><span id="{$Stories[story].chunkname}" style="{$StyleHidden}"><div style="padding-left: 10px; padding-top: 3px; padding-bottom: 8px">{$Stories[story].content}{if $Stories[story].fullstorylink}<div style="padding-top: 4px"><a href="{$Stories[story].fullstorylink}">Read more...</a></div>{/if}</div></span>
{/section}
