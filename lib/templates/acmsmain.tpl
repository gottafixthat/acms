<html>
<head>
<!-- ACMS v{$ACMSVersion}, (C)2004-2010 Cheetah Information Systems, http://www.cheetahis.com/ -->
<title>{$PageTitle}</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<link rel="stylesheet" href="{$StyleSheet}" type="text/css">
<link rel="shortcut icon" href="/favicon.ico">
<link rel="icon" href="/favicon.ico">
<script language="javascript" src="/static/lib/acms.js"></script>
<script type="text/javascript" src="/static/lib/overlib.js"><!-- overLIB (c) Erik Bosrup --></script>
{$ExtraHeaders}
</head>
<body {$BodyParms}>
<div style="border-top: 2px solid #061b54; border-bottom: 3px solid #547cba"></div>
<div id="overDiv" style="position:absolute; visibility:hidden; z-index:1000;"></div>
<table cellpadding="0" cellspacing="0" width="100%" border="0" align="center">
<tr>
<td align="left" valign="bottom" width="20%">
{section name=headers loop=$HeaderBlocks}
{$HeaderBlocks[headers].Data}
{/section}
</td>
<td width="60%" valign="bottom" align="left" nowrap><img style="padding: 3px" src="/static/img/site-logo.jpg" border="0"></td>
</tr>
</table>
{* How many columns *}
{assign var='columncount' value='1'}
{if count($InfoBlocks) gt 0 && $Printable eq 0}{assign var='columncount' value=$columncount+1}{/if}
{if count($ExtraBlocks) gt 0}{assign var='columncount' value=$columncount+1}{/if}
<table cellpadding="0" cellspacing="0" width="100%" border="0" align="center" class="maintable">
{if count($SecHeadBlocks) gt 0}
<tr><td align="right" colspan="{$columncount}" class="secondaryheader">
{section name=secheads loop=$SecHeadBlocks}{$SecHeadBlocks[secheads].Data}{/section}
</td></tr>
{/if}
<tr>
{if count($InfoBlocks) gt 0 && $Printable eq 0}
{assign var='curblock' value='1'}
<td class="infoblocks" valign="top" width="20%">
{section name=info loop=$InfoBlocks}
{$InfoBlocks[info].Data}
{if $curblock lt count($InfoBlocks)}
<hr class="infohr">
{*<p>
<div class="infobardiv"></div>*}
{/if}
{assign var='curblock' value=$curblock+1}
{/section}
</td>
{/if}
<td valign="top">
{section name=content loop=$ContentBlocks}
{$ContentBlocks[content].Data}<p>
{/section}
</td>
</tr>
{if count($FooterBlocks) gt 0}
<tr><td colspan="{$columncount}" class="footer">
{section name=footers loop=$FooterBlocks}
{$FooterBlocks[footers].Data}
{/section}
</td></tr>
{/if}
</table>
</body>
</html>
