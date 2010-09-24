<?php
/**
 * mod_dnsbl.php - The SPIKE module to explain what it is and lookup IPs.
 *
 **************************************************************************
 * Written by R. Marc Lewis and Joseph Harris
 *   Copyright 2004, Joseph Harris
 *   Copyright 2004-2010, R. Marc Lewis (marc@CheetahIS.com)
 *   Copyright 2007-2010, Cheetah Information Systems Inc.
 **************************************************************************
 *
 * This file is part of Another Content Management System (ACMS)
 *
 * ACMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * ACMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with ACMS.  If not, see <http://www.gnu.org/licenses/>.
 */

// First, make sure they didn't try loading us directly.
if (eregi('mod_dnsbl.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers, $modules;

$modules['dnsbl'] = new dnsblModule();

// Load the PEAR Net_Dig class
// Net_DNS is far too loaded with bugs to use.
require_once("Net/Dig.php");

// The module class.
class dnsblModule {

var $dnsblNAME     = 'S.P.I.K.E.';
var $dnsblHOST     = 'spike.example.com';
var $dnsblSERVER   = '127.0.0.1';
var $templatePath;

/*
** dnsblModule - Constructor.  This handles the setup of the various internal
**               functions.
*/

function dnsblModule()
{
    global $ACMSCfg;
    // FIXME: There should be an easy way to override templates with a theme.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts["dirname"] . "/dnsbl_";
    // Override our defaults with anything that may be set in
    // the INI file
    if (isset($ACMSCfg['mod_dnsbl']['dnsblName'])) {
        $this->dnsblNAME = $ACMSCfg['mod_dnsbl']['dnsblName'];
    }
    if (isset($ACMSCfg['mod_dnsbl']['dnsblHost'])) {
        $this->dnsblHOST = $ACMSCfg['mod_dnsbl']['dnsblHost'];
    }
    if (isset($ACMSCfg['mod_dnsbl']['dnsblServer'])) {
        $this->dnsblSERVER = $ACMSCfg['mod_dnsbl']['dnsblServer'];
    }

} // dnsblModule

/*
** exec - Entry point.  This is the function that gets called by the ACMS
**        kernel when the module is loaded.  It should check for arguments
**        and then call the appropriate internal function.
*/

function exec($action, $args = array())
{
    global $app;

    $retVal = "";

    foreach($args as $key => $val) {
        switch ($key) {
            case "count":
                $this->count = $val;
                break;

            default:
                $app->writeLog("dnsbl:exec - unknown option '$key'");
                break;
        }
    }

	$args = "";

	if (strpos($action, "/") !== FALSE) {
		list($action, $args) = split("/", $action, 2);
	}

	if(empty($action)) {
		if(isset($_POST['Action'])) {
			$action = $_POST['Action'];
		}
	}

	if(empty($args)) {
		if(isset($_POST['Oct0'])) {
			$args = implode("." , array ( $_POST['Oct0'], $_POST['Oct1'], $_POST['Oct2'], $_POST['Oct3']));
		}
	}

	switch ($action) {
		case "Lookup":
			$this->lookup($args);
			$this->aboutDNSBL();
			break;

		//case "Info":
		//	phpinfo();
		//	break;

		default:
			$this->aboutDNSBL();
			break;
	}

    return $retVal;
}   // exec

function lookup($addr)
{
	global	$app;

	if(empty($addr)) { return; }
	$reverse = "";
	$chunks = explode('.',$addr);

	// FIXME - This should throw an error
	// If it's not valid set all vars and bail

	$invalid = eregi_replace("([0-9\.]+)","",$addr);
	if(empty($invalid)) {
		foreach($chunks as $octet) {
			if($octet > 255) { $invalid = 255; }
			if(ereg("^0[0-9]+",$octet)) { $invalid = 1; }
		}
	}

	if(!empty($invalid)) {

		$ARecord = "Bad IP address submitted";
		$TXTRecord = "Bad IP address submitted";
		$addr = "INVALID IP ADDRESS";
		$reverseADDR = "INVALID IP ADDRESS";

	} else {

		$chunks = explode('.',$addr);
		$reverse = $chunks[3] .".". $chunks[2] .".". $chunks[1] .".". $chunks[0];
		$reverseADDR = "$reverse." . $this->dnsblHOST;

		// Query the DNS here
		$ARecord = "";
		$TXTRecord = "";

		$resolver = new Net_Dig($reverseADDR, $this->dnsblSERVER);
		// $resolver->debug = true;
		$resolver->query_type = 'TXT';
		$txt_record = $resolver->dig($reverseADDR);
		if($txt_record->answer_count > 0) {
			$txt_data = $txt_record->answer[0];
			$TXTRecord = $txt_data->data;
		} else {
			$TXTRecord = "";
		}
		$resolver->query_type = 'A';
		$a_record = $resolver->dig($reverseADDR);
		if($a_record->answer_count > 0) {
			$a_data = $a_record->answer[0];
			$ARecord = "<font color=red><em>Is Blackholed!</em></font>";
		} else {
			$ARecord = "Not listed";
		}
	}

	$tpl = new acmsTemplate($this->templatePath . "lookup_results.tpl");
	$tpl->assign("DnsblName",   $reverseADDR);
	$tpl->assign("QueryIP",     $addr);

	$tpl->assign("Results",     $ARecord);
	$tpl->assign("TXTRecord",	$TXTRecord);
	$app->addBlock(0, CONTENT_ZONE, "Query Results - ($addr)", $tpl->get());	
	
}

function aboutDNSBL()
{
	global	$app, $_SERVER;

	// Create the template and pretend as if I had a clue what this does.
	$abouttpl = new acmsTemplate($this->templatePath . "about.tpl");

	$abouttpl->assign("DnsblName",	$this->dnsblNAME);
	$abouttpl->assign("DnsblDesc",	"is a real-time spam blackhole system. If you were referred to this page, it means that our network has received enough spam from your mail server to warrant temporarily blocking all incoming mail from it.");
	$app->addBlock(1, CONTENT_ZONE, $this->dnsblNAME, $abouttpl->get());	

	$tpl = new acmsTemplate($this->templatePath . "lookup_form.tpl");
	$tpl->assign("Action",	"Lookup");
	$tpl->assign("DnsblName", $this->dnsblNAME);
    $tpl->assign("ExampleIP", $_SERVER['REMOTE_ADDR']);


	$app->setPageTitle($this->dnsblNAME);

	$app->addBlock(2, CONTENT_ZONE, "Address Lookup", $tpl->get());
}

};  // dnsblModule class

?>
