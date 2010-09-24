<?php
/**
 * mod_search.php - The module that handles ACMS search requests.
 *
 * This is a wrapper for PhpDig
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
if (eregi('mod_search.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

global $INCLUDE_DIR;
require_once($INCLUDE_DIR . "phpdiglib.php");

// The global handlers variable that we will load ourselves into.
global $handlers, $modules, $app;

$modules['search'] = new searchModule();
    

// The module class.
class searchModule {

var $templatePath;

/*
** searchModule - Constructor.  This handles the setup of the various 
**                  internal functions.
*/

function searchModule()
{
    // FIXME: There should be an easy way to override templates with a theme.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts["dirname"] . "/search_";
} // aspstatsModule

/*
** exec - Entry point.  This is the function that gets called by the ACMS
**        kernel when the module is loaded.  It should check for arguments
**        and then call the appropriate internal function.
*/

function exec($action, $args = array())
{
    global $app;

    $retVal = "";

    //echo "<pre>"; print_r($args); echo "</pre>";
    //echo "<pre>"; print_r($action); echo "</pre>";
    $searchStr = "";
    if (isset($args)) {
        if (is_array($args)) {
            $searchStr = array_pop($args);
        } else {
            $searchStr = $args;
        }
    }
    // Check to see if they tried to load us directly, which means we 
    // check for a search query, and display it.
    // doesn't support.
    if (eregi("^/search/", $_SERVER['REQUEST_URI'])) {
        //echo "<pre>"; print_r($_REQUEST); echo "</pre>";
        if (isset($_REQUEST["search"])) {
            $this->doSearch($_REQUEST["search"]);
            return;
        } else if (isset($searchStr) && strlen($searchStr)) {
            $this->doSearch($searchStr);
            return;
        } else {
        }
    }

    switch ($action) {
        case "form":
        default:
            break;
    }

    return $retVal;
}   // exec

/*
** doSearch - Includes the required PhpDig files and does the search.
*/

function doSearch($searchStr)
{
    global $INCLUDE_DIR, $relative_script_path, $app, $siteConfig;
    $phpDigPath = $INCLUDE_DIR . "phpdig";
    $relative_script_path = $phpDigPath;

    $no_connect = 0;

    // Get our site name.
    $sName = "http://" . $_SERVER['SERVER_NAME'] . "/";
    $sNumber = 0;

    // Get the site number from the phpdig database
    $sql = "select site_id from phpdig_sites where site_url = '" . mysql_escape_string($sName) . "'";
    $result = mysql_query($sql, $app->acmsDB);
    if ($result) {
        if (mysql_num_rows($result)) {
            $curRow = mysql_fetch_array($result);
            $sNumber = $curRow["site_id"];
        }
    }

    $digger = new phpDigClass();
    if ($sNumber) {
        $digger->site = $sNumber;
    }

    /*
    require_once($phpDigPath . "/includes/config.php");
    include_once("$relative_script_path/includes/connect.php");
    include_once("$relative_script_path/libs/search_function.php");
    */


    $app->setSessVar("LastSearch", $searchStr);
    $app->sysChunkVars["LastSearch"] = $searchStr;
    $app->setPageTitle("Search Results for '$searchStr'");

    $tmpRay = $digger->search($app->acmsDB, $searchStr);
    //$tmpRay = phpdigSearch($app->acmsDB, $searchStr);
    // echo "<pre>"; print_r($tmpRay); echo "</pre>";

    if (!is_array($tmpRay['results']) || !count($tmpRay['results']) || !strlen(trim($searchStr))) {
        //echo "<pre>"; print_r($tmpRay); echo "</pre>";
        $altText = "";
        if (isset($tmpRay['leven_query']) && strlen($tmpRay['leven_query'])) {
            $altText  = "<p>Did you mean <a href=\"/search/" . urlencode($tmpRay['leven_query']) . "\">";
            $altText .= "<i>" . $tmpRay['leven_query'] . "</i>";
            $altText .= "</a>?";
        }
        $tpl = new acmsTemplate($this->templatePath . "no_matches.tpl");
        $tpl->assign("Suggestion", $altText);
        $app->addBlock(10, CONTENT_ZONE, "Search Results for '$searchStr'", $tpl->get());
        return;
    }

    $tpl = new acmsTemplate($this->templatePath . "results.tpl");
    $results = array();

    foreach ($tmpRay['results'] as $match) {
        $text = substr($match["text"], 4);
        $text = preg_replace("/" . $siteConfig['SiteName'] . ":/is", "", $text);
        $text = trim($text);
        $title = preg_replace("/" . $siteConfig['SiteName'] . ":/is", "", $match["link_title"]);
        $title = trim($title);
        array_push($results, array(
                    "LinkURL"       => $match["complete_path"],
                    "LinkTitle"     => $title,
                    "LinkSnippet"   => $text,
                    "Weight"        => $match["weight"]
                    ));
    }
    $tpl->assign("Results", $results);
    $app->addBlock(10, CONTENT_ZONE, "Search Results for '$searchStr'", $tpl->get());


}


};  // searchModule class

?>
