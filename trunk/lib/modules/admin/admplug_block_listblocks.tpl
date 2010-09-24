<table width="100%">
<tr class="articlesubheader">
  <td>Chunk ID</td>
  <td>Name</td>
  <td>Owner</td>
  <td>Group</td>
  <td>Perms</td>
  <td>Weight</td>
  <td>Zone</td>
  <td>Active</td>
  <td>Persistant</td>
</tr>
{section name=item loop=$Blocks}
<tr>
  <td>{$Blocks[item].ChunkID}</td>
  <td>{$Blocks[item].ChunkName}</td>
  <td>{$Blocks[item].Owner}</td>
  <td>{$Blocks[item].Group}</td>
  <td>{$Blocks[item].Perms}</td>
  <td>{$Blocks[item].Weight}</td>
  <td>{$Blocks[item].Zone}</td>
  <td>{$Blocks[item].Active}</td>
  <td>{$Blocks[item].Persistant}</td>
</tr>
{/section}
</table>
