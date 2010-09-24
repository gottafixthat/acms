<table width="100%">
{section name=item loop=$Categories}
<tr>
  <td align="left"  valign="top">{$Categories[item].LevelOpen}{$Categories[item].EditLink}<br>{$Categories[item].Description}{$Categories[item].LevelClose}<br></td>
  <td align="right" valign="top">{$Categories[item].IconParsed}</td>
</tr>
{/section}
</table>
