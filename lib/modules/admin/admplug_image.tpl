<!-- BEGIN ListImages -->
<table width="100%">
<tr class="articlesubheader">
  <td>Chunk ID</td>
  <td>Image Name</td>
  <td>Filename</td>
  <td>Perms</td>
  <td>Action</td>
</tr>
<!-- BEGIN ListImagesItem -->
<tr>
  <td>{ChunkID}</td>
  <td>{ChunkName}</td>
  <td>{Filename}</td>
  <td>{Perms}</td>
  <td>{DelURL}</td>
</tr>
<!-- END ListImagesItem -->
</table>
<!-- END ListImages -->

<!-- BEGIN image_edit_form -->
<form enctype="multipart/form-data" method="post">
<input type="hidden" name="ChunkID" value="{ChunkID}">
<input type="hidden" name="Action"  value="{Action}">
<table width="100%">
<tr>
  <td valign="middle" align="right">Image Name:</td>
  <td><input type="text" name="ChunkName" size="40" value="{ChunkName}"></td>
  <td valign="middle" align="right">Perms:</td>
  <td valign="middle" align="left"><input type="text" name="Perms" value="{Perms}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Display Filename:</td>
  <td><input type="text" name="Filename" size="40" value="{Filename}"></td>
  <td valign="middle" align="right">Store in DB:</td>
  <td><input type="checkbox" name="StoreInDB" {StoreInDBChecked}></td>
</tr>
</tr>
<tr>
  <td valign="middle" align="right">Mime Type:</td>
  <td><input type="text" name="MimeType" size="40" value="{MimeType}"></td>
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
<td align="left"  valign="top">{Chunk}</td>
</tr>
</table>

</form>

<!-- END image_edit_form -->

<!-- BEGIN image_create_form -->
<form method="post">
<input type="hidden" name="Action"  value="{Action}">
<table width="100%">
<tr>
  <td valign="middle" align="right">Image Name:</td>
  <td><input type="text" name="ChunkName" size="40" value="{ChunkName}"></td>
  <td valign="middle" align="right">Active:</td>
  <td valign="middle" align="left"><input type="checkbox" name="Active" {Active}></td>
</tr>
<tr>
  <td valign="middle" align="right">Link Text:</td>
  <td><input type="text" name="LinkText" size="40" value="{LinkText}"></td>
  <td valign="middle" align="right">Perms:</td>
  <td valign="middle" align="left"><input type="text" name="Perms" value="{Perms}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Page Title:</td>
  <td><input type="text" name="Title" size="40" value="{Title}"></td>
  <td valign="middle" align="right">Show Persistant Blocks:</td>
  <td valign="middle" align="left"><input type="checkbox" name="ShowPersistant" {ShowPersistant}></td>
</tr>
</table>
<p>


<center>
<input type="Submit" name="Save" value="Save">
<input type="Submit" name="Cancel" value="Cancel">
</center>
</form>

<!-- END image_create_form -->

