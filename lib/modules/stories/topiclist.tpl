<table width="100%">
{section name=item loop=$Topics}
<tr>
  <td align="left"  valign="top">{$Topics[item].LevelOpen}{$Topics[item].ViewLink}<br>{$Topics[item].Description}{$Topics[item].LevelClose}<br></td>
  <td align="right" valign="top">{$Topics[item].IconParsed}</td>
</tr>
{/section}
</table>
