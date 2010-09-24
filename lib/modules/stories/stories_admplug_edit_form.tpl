<form method="post">
<input type="hidden" name="ChunkID" value="{$ChunkID}">
<input type="hidden" name="Action"  value="{$Action}">
<table width="100%">
<tr>
  <td valign="middle" align="right">Story Name:</td>
  <td><input type="text" name="ChunkName" size="40" value="{$ChunkName}"></td>
  <td valign="middle" align="right">Perms:</td>
  <td valign="middle" align="left"><input type="text" name="Perms" value="{$Perms}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Post Date:</td>
  <td><input type="text" name="PostDate" size="40" value="{$PostDate}"></td>
  <td valign="top"    align="right">Categories:</td>
  <td valign="top"    align="left" rowspan="3"><select multiple size="3" name="Categories[]">{$Categories}</select></td>
</tr>
<tr>
  <td valign="middle" align="right">Expiration Date:</td>
  <td><input type="text" name="ExpireDate" size="40" value="{$ExpireDate}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Story Title:</td>
  <td><input type="text" name="Title" size="40" value="{$Title}"></td>
</tr>
<tr>
  <td valign="top" align="right">Story:<p>(Use the "@@page@@" tag to separate the headline from the main story.)</td>
  <td colspan="3">{$ChunkArea}</td>
</tr>
</table>
<br>
<center>
<input type="Submit" name="Preview" value="Preview">
<input type="Submit" name="Save" value="Save">
<input type="Submit" name="Cancel" value="Cancel">
</center>

</form>
