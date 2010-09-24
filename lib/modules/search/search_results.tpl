<table width="100%">
{section name=item loop=$Results}
<tr>
  <td><a href="{$Results[item].LinkURL}">{$Results[item].LinkTitle}</a> ({$Results[item].Weight}%)</td>
</tr>
<tr>
  <td><div class="inset">{$Results[item].LinkSnippet}</div></td>
</tr>
{/section}
</table>
