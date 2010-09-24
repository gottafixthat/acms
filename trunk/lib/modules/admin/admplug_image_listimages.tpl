<table width="100%">
<tr class="articlesubheader">
  <td>Chunk ID</td>
  <td>Image Name</td>
  <td>Filename</td>
  <td>Perms</td>
  <td>Action</td>
</tr>
{section name=item loop=$Images}
<tr>
  <td>{$Images[item].ChunkID}</td>
  <td>{$Images[item].ChunkName}</td>
  <td>{$Images[item].Filename}</td>
  <td>{$Images[item].Perms}</td>
  <td>{$Images[item].DelURL}</td>
</tr>
{/section}
</table>
