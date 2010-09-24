<form method="post">
<input type="hidden" name="Action"  value="{$Action}">
<table width="100%">
<tr>
  <td valign="middle" align="right">Page Name:</td>
  <td><input type="text" name="ChunkName" size="40" value="{$ChunkName}"></td>
  <td valign="middle" align="right">Active:</td>
  <td valign="middle" align="left"><input type="checkbox" name="Active" {$Active}></td>
</tr>
<tr>
  <td valign="middle" align="right">Link Text:</td>
  <td><input type="text" name="LinkText" size="40" value="{$LinkText}"></td>
  <td valign="middle" align="right">ACL (should be a list):</td>
  <td valign="middle" align="left"><input type="text" name="Perms" value="{$Perms}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Page Title:</td>
  <td><input type="text" name="Title" size="40" value="{$Title}"></td>
  <td valign="middle" align="right">Show Persistant Blocks:</td>
  <td valign="middle" align="left"><input type="checkbox" name="ShowPersistant" {$ShowPersistant}></td>
</tr>
<tr>
  <td valign="top" align="right">Content:</td>
  <td colspan="3">{$ChunkArea}</td>
</tr>
</table>
<p>


<center>
<input type="Submit" name="Preview" value="Preview">
<input type="Submit" name="Save" value="Save">
<input type="Submit" name="Cancel" value="Cancel">
</center>
</form>
