<form method="post">
<input type="hidden" name="mailaction" value="send">
<table width="100%">
<tr>
  <td align="right">Recipient:</td>
  <td align="left"><b>{$Recipient}</b></td>
</tr>
<tr>
  <td align="right">Your Name:</td>
  <td align="left"><input type="text" name="sendername" size="70" maxlength="70" value="{$SenderName}"></td>
</tr>
<tr>
  <td align="right">Your Email Address:</td>
  <td align="left"><input type="text" name="senderemail" size="70" maxlength="70" value="{$SenderEmail}"></td>
</tr>
<tr>
  <td align="right">Subject:</td>
  <td align="left"><input type="text" name="subject" size="70" maxlength="70" value="{$Subject}"></td>
</tr>
<tr>
  <td align="right" valign="top">Message:</td>
  <td align="left"  valign="top"><textarea name="message" rows="15" cols="70" wrap>{$Message}</textarea></td>
</table>
<center><input type="submit" name="Send" value="Send"> <input type="submit" name="Cancel" value="Cancel"></center>
