{if $Style eq 'boxed'}
<div class="articlesubheader"><a style="text-decoration: none" href="javascript:toggleItems('{$ChunkName}', 'dnarr-{$ChunkName}', 'rtarr-{$ChunkName}')"><img border="0" src="/static/img/menu-arrow-down.gif" style="{$StyleHidden}" id="dnarr-{$ChunkName}"><img border="0" src="/static/img/menu-arrow-right.gif" id="rtarr-{$ChunkName}" style="{$StyleVisible}">{$ChunkTitle}</a></div>
<span id="{$ChunkName}" style="{$StyleHidden}">^^{$Chunk}^^</span>
{elseif $Style eq 'bluebox'}
^#<img src="/static/img/menu-arrow-right.gif" id="rtarr-{$ChunkName}" style="{$StyleVisible}"><a id="link-{$ChunkName}" style="{$StyleVisible}" href="javascript:toggleItems('{$ChunkName}', 'link-{$ChunkName}', 'rtarr-{$ChunkName}')">{$ChunkTitle}</a>
<span id="{$ChunkName}" style="{$StyleHidden}">{$Chunk}</span>#^
{elseif $Style eq 'blueboxclose'}
<div class="shadedbox" style="padding-left: 0px; padding-right: 0px; padding-bottom: 0px"><table width="100%"><tr><td><span id="link-{$ChunkName}" style="{$StyleVisible}"><a href="javascript:toggleItems('{$ChunkName}', 'link-{$ChunkName}', 'close-{$ChunkName}', 'text-{$ChunkName}', 'down-{$ChunkName}', 'space-{$ChunkName}')"><img border="0" src="/static/img/menu-arrow-right.gif" id="down-{$ChunkID}"><b>{$ChunkTitle}</b></a></span><span id="text-{$ChunkName}" style="{$StyleHidden}"><img border="0" src="/static/img/menu-arrow-spacer.gif" id="space-{$ChunkID}"><b>{$ChunkTitle}</b></span></td><td align="right"><span style="{$StyleHidden}" id="close-{$ChunkName}"><a style="text-decoration: none" href="javascript:toggleItems('{$ChunkName}', 'text-{$ChunkName}', 'link-{$ChunkName}', 'close-{$ChunkName}', 'down-{$ChunkName}', 'space-{$ChunkName}')"><b>X</b></a></span></td></tr></table><div id="{$ChunkName}" style="{$StyleHidden}margin-left: 0px; margin-right: 0px; border-top: 1px solid black; padding-bottom: 0px; margin-bottom: 0px"><div style="padding-left: 0px; padding-right: 0px; padding-bottom: 0px; background-color: #ffffff">{$Chunk}</div></div></div>
{else}
<img src="/static/img/menu-arrow-down.gif" style="{$StyleHidden}" id="dnarr-{$ChunkName}"><img src="/static/img/menu-arrow-right.gif" style="{$StyleVisible}" id="rtarr-{$ChunkName}"><a href="javascript:toggleItems('{$ChunkName}', 'dnarr-{$ChunkName}', 'rtarr-{$ChunkName}')">{$ChunkTitle}</a>
<span id="{$ChunkName}" style="{$StyleHidden}">{$Chunk}</span>
{/if}
