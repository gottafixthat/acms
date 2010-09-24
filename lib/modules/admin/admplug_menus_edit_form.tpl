<form method="post">
<input type="hidden" name="ChunkID" value="{$ChunkID}">
<input type="hidden" name="Action"  value="{$Action}">
<table width="100%">
<tr>
  <td valign="middle" align="right">Menu Name:</td>
  <td><input type="text" name="ChunkName" size="40" value="{$ChunkName}"></td>
  <td valign="middle" align="right">Perms:</td>
  <td valign="middle" align="left"><input type="text" name="Perms" value="{$Perms}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Menu Title:</td>
  <td><input type="text" name="Title" size="40" value="{$Title}"></td>
</tr>
<tr>
  <td valign="top" align="right">Menu Content:</td>
  <td colspan="3"><textarea name="Chunk" rows="15" cols="70">{$Chunk}</textarea></td>
</tr>
</table>
<br>
<center>
<input type="Submit" name="Save" value="Save">
<input type="Submit" name="Cancel" value="Cancel">
</center>

</form>
