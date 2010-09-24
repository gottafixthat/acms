<table width="100%" cellpadding="1" cellspacing="0">
<tr class="articlesubheader">
  <td class="articlesubheader">Label</td>
  <td class="articlesubheader">Tag</td>
  <td class="articlesubheader">Brief/Link Text</td>
  <td class="articlesubheader" align="right">Action</td>
</tr>
{section name=i loop=$Items}
{assign var='depth' value=$Items[i].level*12}
<tr class="{cycle values="listoddrow,listevenrow"}">
  <td><span style="padding-left: {$depth}px">{$Items[i].Label}</span></td>
  <td>{$Items[i].Tag}</td>
  <td>{$Items[i].TextBrief}</td>
  <td align="right">
    <a href="{$MyURI}?act=add&catconid={$Items[i].CatConID}">Add</a>
    <a href="{$MyURI}?act=edit&catconid={$Items[i].CatConID}">Edit</a>
    <a href="{$MyURI}?act=delete&catconid={$Items[i].CatConID}" onClick="return confirm('There is no way to undo this action.\nAre you sure you want to delete this item?')">Delete</a>
    <a href="{$MyURI}?act=mvup&catconid={$Items[i].CatConID}">Up</a>
    <a href="{$MyURI}?act=mvdn&catconid={$Items[i].CatConID}">Down</a>
  </td>
</tr>
{/section}
</table>
