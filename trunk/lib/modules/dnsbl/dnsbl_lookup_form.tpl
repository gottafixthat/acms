<form method="post">
<input type="hidden" name="Action"  value="{$Action}">
Enter the IP address to lookup in the {$DnsblName} database (e.g. {$ExampleIP}).
<p>
<center>
<input type="text" name="Oct0" value="" size="3" maxlength="3"><b>.</b>
<input type="text" name="Oct1" value="" size="3" maxlength="3"><b>.</b>
<input type="text" name="Oct2" value="" size="3" maxlength="3"><b>.</b>
<input type="text" name="Oct3" value="" size="3" maxlength="3">
<p>
<input type="Submit" name="Lookup" value="{$Action}">
<input type="Submit" name="Cancel" value="Cancel">
</center>

</form>
