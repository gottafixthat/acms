<?php
/**
 * mod_traceroute.php - A simple traceroute utility.
 *
 **************************************************************************
 * Written by R. Marc Lewis, 
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
if (eregi('mod_traceroute.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers, $modules;

$modules['traceroute'] = new tracerouteModule();

// The module class.
class tracerouteModule {

var $templatePath;
var $rawQuery;
var $queryType;
var $traceip;

/*
** tracerouteModule - Constructor.  This handles the setup of the various
**                    internal functions.
*/

function tracerouteModule()
{
    // FIXME: There should be an easy way to override templates with a theme.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts["dirname"] . "/traceroute_";
} // tracerouteModule

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
                $app->writeLog("traceroute:exec - unknown option '$key'");
                break;
        }
    }

	$args = "";

	if (strpos($action, "/") !== FALSE) {
		list($action, $args) = split("/", $action, 2);
	}

	if(empty($action)) {
		if($_POST) {
			$action = $_POST['Action'];
		}
	}

	if(empty($args)) {
		if(isset($_POST['Host'])) {
			$args = $_POST['Host'];
			$this->rawQuery = $args;
		}
	}

	$this->queryType = $this->findQuery($args);

	switch ( $this->queryType ) {
		case "ip":
			$this->iptrace($this->traceip);
			$this->aboutTRACERT();
			break;

		case "host":
			$this->hosttrace($this->traceip);
			$this->aboutTRACERT();
			break;

		case "error":
			$this->badData($args);
			$this->aboutTRACERT();
			break;

		default:
			$this->aboutTRACERT();
			break;
	}

    return $retVal;
}   // exec


function validHost($host)
{
	// Not much point if there's no name
	if(empty($host)) { return; }

	// Resolve the IP, if that succeeds pump this through iptrace and return

	$ipaddr = gethostbyname($host);
	if($ipaddr != "$host") {
		$this->traceip = $ipaddr;
		return true;
	}
	return;
}

function validIP($addr)
{
	if(empty($addr)) { return; }
	$chunks = explode('.',$addr);

	$invalid = eregi_replace("([0-9\.]+)","",$addr);
	if($invalid) { return; }

	foreach($chunks as $octet) {
		if($octet > 255) { return; }
		if(ereg("^0[0-9]+",$octet)) { return; }
	}
	$this->traceip = $addr;
	return true;
}


function findQuery($args)
{
	if(empty($args)) { return; }

	if(ereg("[0-9]{0,3}\.[0-9]{0,3}\.[0-9]{0,3}\.[0-9]{0,3}",$args)) {
		if($this->validIP($args)) {
			return "ip";
		} else {
			return "error";
		}
	} else {
		if($this->validHost($args)) {
			return "host";
		} else {
			return "error";
		}
	}
}

function hosttrace($hostname)
{
	// Redundant I know... sue me.
	$this->iptrace($this->traceip);
}

function iptrace($addr)
{
	global	$app;

	// Valid IP or we wouldn't be here. Just run it.

	$tracert_prog = '/usr/local/sbin/traceroute-nanog -a -w 2 -P -q 3';

	$cmd = escapeshellcmd(sprintf("%s %s",$tracert_prog,$addr));

	$raw_data = `$cmd`;

	$TracertName = $addr;
	$TraceResults = "<pre>" . $raw_data . "</pre>";

	$tpl = new acmsTemplate($this->templatePath . "lookup_results.tpl");
	$tpl->assign("TraceName",       $this->rawQuery);
	$tpl->assign("TraceIP",         $this->traceip);
	$tpl->assign("TraceResults",    $TraceResults);
	$app->addBlock(0, CONTENT_ZONE, "Traceroute Results", $tpl->get());
}

function badData($addr)
{
	global	$app;

	$TracertName = "Error: Invalid data.";
	$TraceResults = "The IP address you entered was invalid or the hostname could not be resolved to an IP address. Please try again.";

	$tpl = new acmsTemplate($this->templatePath . "lookup_results.tpl");
	$tpl->assign("TraceName",       $TracertName);
	$tpl->assign("TraceResults",    $TraceResults);
	$app->addBlock(0, CONTENT_ZONE, "Traceroute Results", $tpl->get());
}


function aboutTRACERT()
{
	global	$app;

	// Create the template and pretend as if I had a clue what this does.
	$abouttpl = new acmsTemplate($this->templatePath . "about.tpl");
	$app->addBlock(1, CONTENT_ZONE, "Traceroute", $abouttpl->get());	

	$tpl = new acmsTemplate($this->templatePath . "lookup_host.tpl");
	$tpl->assign("Action",	"Trace");

	$app->setPageTitle("Traceroute");

	$app->addBlock(2, CONTENT_ZONE, "Trace the route", $tpl->get());
}

};  // tracerouteModule class

?>
