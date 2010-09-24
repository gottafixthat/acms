<form method="post">
<input type="hidden" name="Action" value="{$Action}">
<input type="hidden" name="CategoryID" value="{$CategoryID}">
<table>
<tr>
  <td align="right">Title:</td>
  <td align="left" ><input type="text" name="Title" value="{$Title}" size="40"></td>
</tr>
<tr>
  <td align="right">Parent Category:</td>
  <td align="left" ><select name="ParentID">{$ParentSel}</select></td>
<tr>
  <td align="right">Icon Tag:</td>
  <td align="left" ><input type="text" name="IconTag" value="{$IconTag}" size="40"></td>
</tr>
<tr>
  <td align="right" valign="top">Description:</td>
  <td align="left"  valign="top"><textarea name="Description" rows=10 cols=72 wrap>{$Description}</textarea></td>
</tr>
</table>
<center><input type="submit" name="Save" value="Save"> <input type="submit" name="Cancel" value="Cancel"> {$DeleteButton}</center>
</form>
