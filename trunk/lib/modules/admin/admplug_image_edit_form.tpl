<form enctype="multipart/form-data" method="post">
<input type="hidden" name="ChunkID" value="{$ChunkID}">
<input type="hidden" name="Action"  value="{$Action}">
<table width="100%">
<tr>
  <td valign="middle" align="right">Image Name:</td>
  <td><input type="text" name="ChunkName" size="40" value="{$ChunkName}"></td>
  <td valign="middle" align="right">Perms:</td>
  <td valign="middle" align="left"><input type="text" name="Perms" value="{$Perms}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Display Filename:</td>
  <td><input type="text" name="Filename" size="40" value="{$Filename}"></td>
  <td valign="middle" align="right">Store in DB:</td>
  <td><input type="checkbox" name="StoreInDB" {$StoreInDBChecked}></td>
</tr>
</tr>
<tr>
  <td valign="middle" align="right">Mime Type:</td>
  <td><input type="text" name="MimeType" size="40" value="{$MimeType}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Upload file:</td>
  <td><input type="file" name="UploadFile" size="40" value=""></td>
</tr>
</table>
<br>
<center>
<input type="Submit" name="Save" value="Save">
<input type="Submit" name="Cancel" value="Cancel">
</center>
<hr>
<table>
<tr>
<td align="right" valign="top">Image:</td>
<td align="left"  valign="top">{$Chunk}</td>
</tr>
</table>

</form>
