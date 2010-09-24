<!-- BEGIN ListPages -->
<table width="100%">
<tr class="articlesubheader">
  <td>Chunk ID</td>
  <td>Page Name</td>
  <td>Link Text</td>
  <td>Title</td>
  <td>ACL</td>
  <td>A</td>
  <td>P</td>
</tr>
<!-- BEGIN ListPagesItem -->
<tr>
  <td>{ChunkID}</td>
  <td>{ChunkName}</td>
  <td>{LinkText}</td>
  <td>{Title}</td>
  <td>{Perms}</td>
  <td>{Active}</td>
  <td>{ShowPersistant}</td>
</tr>
<!-- END ListPagesItem -->
</table>
<!-- END ListPages -->

<!-- BEGIN page_edit_form -->
<form method="post" name="pos">
<input type="hidden" name="ChunkID" value="{ChunkID}">
<input type="hidden" name="Action"  value="{Action}">
<table width="100%">
<tr>
  <td valign="middle" align="right">Page Name:</td>
  <td><input type="text" name="ChunkName" size="40" value="{ChunkName}"></td>
  <td valign="middle" align="right">Active:</td>
  <td valign="middle" align="left"><input type="checkbox" name="Active" {Active}></td>
</tr>
<tr>
  <td valign="middle" align="right">Link Text:</td>
  <td><input type="text" name="LinkText" size="40" value="{LinkText}"></td>
  <td valign="middle" align="right">ACL (should be a list):</td>
  <td valign="middle" align="left"><input type="text" name="Perms" value="{Perms}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Page Title:</td>
  <td><input type="text" name="Title" size="40" value="{Title}"></td>
  <td valign="middle" align="right">Show Persistant Blocks:</td>
  <td valign="middle" align="left"><input type="checkbox" name="ShowPersistant" {ShowPersistant}></td>
</tr>
<tr>
  <td valign="top" align="right">Content:</td>
  <td colspan="3">{ChunkArea}</td>
</tr>
<tr>
  <td colspan="4">&nbsp;<br></td>
</tr>
<tr>
  <td valign="middle" align="right">Available Blocks:</td>
  <td valign="middle" align="left"><select name="BlockList">{BlockList}</select><input type="submit" name="Add" value="Add">
  <td valign="middle" align="left"><input type="Submit" name="Remove" value="Remove Block"></td>
</tr>
</table>
<p>

<table width="100%">
<tr class="articlesubheader">
  <td>Sel</td>
  <td>Block ID</td>
  <td>Block Name</td>
  <td>Perms</td>
  <td>Zone</td>
  <td>Weight</td>
  <td>Persistant</td>
</tr>
<!-- BEGIN block_list_item -->
<tr>
  <td><input type="radio" name="BlockSel" value="{BlockID}"></td>
  <td>{BlockID}</td>
  <td>{BlockName}</td>
  <td>{BlockPerms}</td>
  <td>{BlockZone}</td>
  <td>{BlockWeight}</td>
  <td>{BlockPersistant}</td>
</tr>
<!-- END block_list_item -->
</table>


<center>
<input type="Submit" name="Preview" value="Preview">
<input type="Submit" name="Save" value="Save">
<input type="Submit" name="Cancel" value="Cancel">
</center>
</form>

<!-- END page_edit_form -->

<!-- BEGIN page_create_form -->
<form method="post">
<input type="hidden" name="Action"  value="{Action}">
<table width="100%">
<tr>
  <td valign="middle" align="right">Page Name:</td>
  <td><input type="text" name="ChunkName" size="40" value="{ChunkName}"></td>
  <td valign="middle" align="right">Active:</td>
  <td valign="middle" align="left"><input type="checkbox" name="Active" {Active}></td>
</tr>
<tr>
  <td valign="middle" align="right">Link Text:</td>
  <td><input type="text" name="LinkText" size="40" value="{LinkText}"></td>
  <td valign="middle" align="right">ACL (should be a list):</td>
  <td valign="middle" align="left"><input type="text" name="Perms" value="{Perms}"></td>
</tr>
<tr>
  <td valign="middle" align="right">Page Title:</td>
  <td><input type="text" name="Title" size="40" value="{Title}"></td>
  <td valign="middle" align="right">Show Persistant Blocks:</td>
  <td valign="middle" align="left"><input type="checkbox" name="ShowPersistant" {ShowPersistant}></td>
</tr>
<tr>
  <td valign="top" align="right">Content:</td>
  <td colspan="3">{ChunkArea}</td>
</tr>
</table>
<p>


<center>
<input type="Submit" name="Save" value="Save">
<input type="Submit" name="Cancel" value="Cancel">
</center>
</form>

<!-- END page_create_form -->

