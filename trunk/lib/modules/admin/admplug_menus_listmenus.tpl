<table width="100%">
<tr class="articlesubheader">
  <td>Chunk ID</td>
  <td>Menu Name</td>
  <td>Title</td>
  <td>Perms</td>
</tr>
{section name=i loop=$Menus}
<tr>
  <td>{$Menus[i].ChunkID}</td>
  <td>{$Menus[i].ChunkName}</td>
  <td>{$Menus[i].Title}</td>
  <td>{$Menus[i].Perms}</td>
</tr>
{/section}
</table>
