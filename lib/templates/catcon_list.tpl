{* Show the path that took them to this location *}
{assign var='tmpcounter' value='0'}
<div class="catconNav">
{section name=i loop=$Items}
{if $Items[i].showpath}
{if $tmpcounter} &raquo; {/if}
<a href="{$Items[i].path}">{$Items[i].Label}</a> 
{assign var='tmpcounter' value='$tmpcounter+1}
{/if}
{/section}
</div>

{$DispItem.TextChunk}

{if count($Children)}
<p>
{section name=i loop=$Children}
<div class="catconChildContent">
<div class="catconChildLink"><a class="catconChildLink" href="{$Children[i].path}">{$Children[i].TextBrief}</a></div>
{$Children[i].TextList}
</div>
{/section}
{/if}
<p>

{$DispItem.TextFooter}

