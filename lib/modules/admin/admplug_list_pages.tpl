<table width="100%">
<tr class="articlesubheader">
  <td>Page ID</td>
  <td>Page Name</td>
  <td>Link Text</td>
  <td>Title</td>
  <td>Owner</td>
  <td>Group</td>
  <td>Perms</td>
  <td>A</td>
  <td>P</td>
</tr>
{section name=item loop=$Pages}
<tr>
  <td>{$Pages[item].ChunkID}</td>
  <td>{$Pages[item].ChunkName}</td>
  <td>{$Pages[item].LinkText}</td>
  <td>{$Pages[item].Title}</td>
  <td>{$Pages[item].Owner}</td>
  <td>{$Pages[item].Group}</td>
  <td>{$Pages[item].Perms}</td>
  <td>{$Pages[item].Active}</td>
  <td>{$Pages[item].ShowPersistant}</td>
</tr>
{/section}
</table>
