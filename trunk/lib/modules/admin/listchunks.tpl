<table width="100%">
<tr class="articlesubheader">
  <td>Chunk ID</td>
  <td>ChunkName</td>
  <td>Handler</td>
  <td>Owner</td>
  <td>Group</td>
  <td>Perms</td>
  <td>Action</td>
</tr>
{section name=item loop=$Chunks}
<tr>
  <td>{$Chunks[item].ChunkID}</td>
  <td>{$Chunks[item].ChunkName}</td>
  <td>{$Chunks[item].Handler}</td>
  <td>{$Chunks[item].Owner}</td>
  <td>{$Chunks[item].Group}</td>
  <td>{$Chunks[item].Perms}</td>
  <td>{$Chunks[item].DelURL}</td>
</tr>
{/section}
</table>
