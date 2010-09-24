
<!-- BEGIN AboutDNSBL -->

<table width="100%">
<tr>
  <td>{DnsblName} {DnsblDesc}</td>
</tr>
</table>

<!-- END AboutDNSBL -->

<!-- BEGIN LookupResults -->

<table width="100%">
<tr>
  <td><b>{DnsblName}</b>: {Results}</td>
</tr>
<tr>
  <td>{TXTRecord}</td>
</tr>
</table>

<!-- END LookupResults -->

<!-- BEGIN dnsbl_lookup_form -->
<form method="post">
<input type="hidden" name="Action"  value="{Action}">
Enter the IP address to lookup in the {DnsblName} database (e.g. 199.245.214.1).
<br>
<center>
<input type="text" name="Oct0" value="" size="3" maxlength="3"><b>.</b>
<input type="text" name="Oct1" value="" size="3" maxlength="3"><b>.</b>
<input type="text" name="Oct2" value="" size="3" maxlength="3"><b>.</b>
<input type="text" name="Oct3" value="" size="3" maxlength="3"><br>
<input type="Submit" name="Lookup" value="{Action}">
<input type="Submit" name="Cancel" value="Cancel">
</center>

</form>
<!-- END dnsbl_lookup_form -->

