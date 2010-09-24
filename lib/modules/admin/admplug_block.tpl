<!-- BEGIN ListBlocks -->

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
<!-- BEGIN ListBlocksItem -->
<tr>
  <td>{ChunkID}</td>
  <td>{ChunkName}</td>
  <td>{Owner}</td>
  <td>{Group}</td>
  <td>{Perms}</td>
  <td>{Weight}</td>
  <td>{Zone}</td>
  <td>{Active}</td>
  <td>{Persistant}</td>
</tr>
<!-- END ListBlocksItem -->
</table>

<!-- END ListBlocks -->


<!-- BEGIN block_edit_form -->
<form method="post">
<input type="hidden" name="ChunkID" value="{ChunkID}">
<input type="hidden" name="Action"  value="{Action}">
<table width="100%">
<tr>
  <td valign="middle" align="right">Chunk/Block Name:</td>
  <td><input type="text" name="ChunkName" size="30" value="{ChunkName}"></td>
  <td valign="middle" align="right">Permsissions:</td>
  <td><input type="text" name="Perms" value="{Perms}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Persistant:</td>
  <td><input type="checkbox" name="Persistant" {Persistant}></td>
  <td valign="middle" align="right">Zone:</td>
  <td><select name="Zone">{Zone}</select></td>
</tr>
<tr>
  <td valign="middle" align="right">Active:</td>
  <td><input type="checkbox" name="Active" {Active}></td>
  <td valign="middle" align="right">Weight:</td>
  <td><input type="text" name="Weight" value="{Weight}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Title:</td>
  <td colspan="3"><input type="text" size="60" name="Title" value="{Title}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Title Navigation:</td>
  <td colspan="3"><input type="text" size="60" name="TitleNav" value="{TitleNav}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Footer:</td>
  <td colspan="3"><input type="text" size="60" name="Footer" value="{Footer}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Footer Navigation:</td>
  <td colspan="3"><input type="text" size="60" name="FooterNav" value="{FooterNav}"></td>
</tr>
<tr>
  <td valign="top" align="right">Content:</td>
  <td colspan="3">{ChunkArea}</td>
</tr>
</table>

<center>
<input type="Submit" name="Save" value="Save">
<input type="Submit" name="Cancel" value="Cancel">
</center>
</form>

<!-- END block_edit_form -->

