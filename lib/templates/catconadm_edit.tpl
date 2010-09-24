<form method="post">
<input type="hidden" name="{$SaveTag}" value="1">

<table width="100%" cellpadding="1" cellspacing="1">
<tr>
  <td align="right" valign="top">Tag:</td>
  <td align="left"  valign="top"><input type="text" name="Tag" value="{$Tag}"></td>
</tr>
<tr>
  <td align="right" valign="top">Label:</td>
  <td align="left"  valign="top"><input type="text" name="Label" value="{$Label}"></td>
</tr>
<tr>
  <td align="right" valign="top">Brief Text:</td>
  <td align="left"  valign="top"><input type="text" size="95" name="TextBrief" value="{$TextBrief}"></td>
</tr>
<tr>
  <td align="right" valign="top"><div style="margin-top: 30px">Main Text:</div></td>
  <td align="left"  valign="top">{$TextChunkEditor}</td>
</tr>
<tr>
  <td align="right" valign="top"><div style="margin-top: 30px">Footer Text:</div></td>
  <td align="left"  valign="top">{$TextFooterEditor}</td>
</tr>
<tr>
  <td align="right" valign="top"><div style="margin-top: 30px">List Item Text:</div></td>
  <td align="left"  valign="top">{$TextListEditor}</td>
</tr>
<tr>
  <td valign="middle" align="right">Available Blocks:</td>
  <td valign="middle" align="left"><select name="BlockList">{$BlockList}</select> <input type="checkbox" name="IncludeChildren"><span style="margin-right:20px">Include children?</span> <input type="submit" name="Add" value="Add"> <input type="Submit" name="Remove" value="Remove Block"></td>
</tr>
</table>
<p>
<table width="100%" cellspacing="0">
<tr class="articlesubheader">
  <td>Sel</td>
  <td>Block ID</td>
  <td>Block Name</td>
  <td>Perms</td>
  <td>Zone</td>
  <td>Weight</td>
  <td>Children</td>
</tr>
<!-- BEGIN block_list_item -->
{section name=item loop=$Blocks}
<tr>
  <td><input type="radio" name="BlockSel" value="{$Blocks[item].BlockID}"></td>
  <td>{$Blocks[item].BlockID}</td>
  <td>{$Blocks[item].BlockName}</td>
  <td>{$Blocks[item].BlockPerms}</td>
  <td>{$Blocks[item].BlockZone}</td>
  <td>{$Blocks[item].BlockWeight}</td>
  <td>{$Blocks[item].IncludeChildren}</td>
</tr>
{/section}
<!-- END block_list_item -->
</table>
<center>
<input type="submit" name="save" value="Save">
<input type="submit" name="cancel" value="Cancel">
</center>
</form>
