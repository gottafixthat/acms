<table width="100%">
<tr class="articlesubheader">
  <td>Chunk ID</td>
  <td>Story Name</td>
  <td>Title</td>
  <td>Perms</td>
  <td>Post Date</td>
</tr>
<!-- BEGIN ListStoriesItem -->
{section name=item loop=$Stories}
<tr>
  <td>{$Stories[item].ChunkID}</td>
  <td>{$Stories[item].ChunkName}</td>
  <td>{$Stories[item].Title}</td>
  <td>{$Stories[item].Perms}</td>
  <td>{$Stories[item].PostDate}</td>
</tr>
{/section}
<!-- END ListStoriesItem -->
</table>
