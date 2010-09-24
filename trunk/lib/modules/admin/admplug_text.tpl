<!-- BEGIN edit_form -->
<form method="post">
<input type="hidden" name="ChunkID" value="{ChunkID}">
<input type="hidden" name="Action"  value="{Action}">
<table width="100%">
<tr>
  <td valign="middle" align="right">Chunk Name:</td>
  <td><input type="text" name="ChunkName" width="40" value="{ChunkName}"></td>
  <td valign="middle" align="right">Permissions:</td>
  <td><input type="text" name="Perms" width="10" value="{Perms}"></td>
</tr>
<tr>
  <td valign="top" align="right">Chunk Content:</td>
  <td colspan="3">{ChunkArea}</td>
</tr>
</table>

<center>
<input type="Submit" name="Preview" value="Preview">
<input type="Submit" name="Save" value="Save">
<input type="Submit" name="Cancel" value="Cancel">
</center>
</form>

<!-- END edit_form -->

