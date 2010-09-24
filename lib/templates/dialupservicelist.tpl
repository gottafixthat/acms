<br>
<table width="100%" cellpadding="3" cellspacing="0">
{*<tr class="articlesubheader">{section name=i loop=$keys}<th align="Left">{$keys[i]}</th>{/section}</tr>*}
<tr>
  <td class="servicePricingHeader" align="left"><b>Service</b></td>
  <td class="servicePricingHeader" align="right"><b>Setup</b></td>
  <td class="servicePricingHeader" align="right"><b>Monthly</b></td>
  <td class="servicePricingHeader" align="center"><b>Mailboxes</b></td>
  <td class="servicePricingHeader" align="left"><b>Notes</b></td>
{section name=j loop=$data}
<tr class="{cycle values=listoddrow,listevenrow}">
  <td nowrap valign="top"><b>{if $data[j].SignupAccount ne ""}<a label="Signup Now" href="/signup?acttype={$data[j].SignupAccount}">{$data[j].Account}</a>{else}{$data[j].Account}{/if}</b></td>
  <td align="right" valign="top">{$data[j].Setup}</td>
  <td align="right" valign="top">{$data[j].Monthly}</td>
  <td align="center" valign="top">{$data[j].Mailboxes}</td>
  <td align="left" valign="top">{$data[j].Notes}</td>
</tr>
{/section}
</table><p>&nbsp;

