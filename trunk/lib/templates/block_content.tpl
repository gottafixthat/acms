<table border="0" width="100%" cols="2" cellspacing="0" cellpadding="0">
{if $Title ne ""}
<tr>
{if $TitleNav eq ""}
  <td colspan="2" class="sectionheader" align="left"  valign="top">{$Title}</td>
{else}
  <td class="sectionheader" align="left"  valign="top">{$Title}</td>
  <td class="sectionheader" align="right" valign="top">{$TitleNav}</td>
{/if}
</tr>
{/if}
<tr>
  <td class="sectionbody" colspan="2">{$Content}</td>
</tr>
{if $Footer ne ""}
<tr>
  <td align="right" class="sectionbody" colspan="2">{$Footer}</td>
</tr>
{/if}
</table>
