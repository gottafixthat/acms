<?php
/**
 * mod_aspstats.php - Handles Advanced Spam Protection stats.
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
if (eregi('mod_aspstats.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers, $modules;

$modules['aspstats'] = new aspstatsModule();

/*! \class aspstatsModule
 *  \brief Advanced Spam Protection Statistics module for ACMS
 *  \author Marc Lewis
 *  \var aspStatsDBHost (ini) The MySQL host to get the stats from
 *  \var aspStatsDBName (ini) The MySQL database name
 *  \var aspStatsDBUser (ini) The MySQL user name
 *  \var aspStatsDBPass (ini) The MySQL password
 *  \warning The various DB variables MUST be set or bad things will happen.
*/
class aspstatsModule {

var $templateFile;
var $templatePath;

var $cacheLife      = 300;      // 5 Minutes
var $virusToday     = 0;
var $virusTotal     = 0;
var $spamToday      = 0;
var $spamTotal      = 0;
var $totalDate      = "";
var $totalDateDisp  = "";

/*
** aspstatsModule - Constructor.  This handles the setup of the various 
**                  internal functions.
*/

function aspstatsModule()
{
    // FIXME: There should be an easy way to override templates with a theme.
    $parts = pathinfo(__FILE__);
    $this->templateFile = $parts["dirname"] . "/aspstats.tpl";
    $this->templatePath = $parts["dirname"] . "/";
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

    // Check to see if they tried to load us directly, which this module
    // doesn't support.
    if (eregi("^/aspstats", $_SERVER['REQUEST_URI'])) {
        ob_end_clean();
        header("Location: /");
        exit;
    }

    switch ($action) {
        case "get":
        default:
            $retVal = $this->getASPStats();
            break;
    }

    return $retVal;
}   // exec

/*
** loadCounts  - Checks our local cache for the counts.  If the data is
**               too old, it gets it from the other server
**               again, otherwise it uses the cache data.
**               If it is unable to connect to the main server, it uses
**               the cached data.
*/

function loadCounts()
{
    global $app, $ACMSCfg;

    $sql = "select VirusToday, VirusTotal, SpamToday, SpamTotal, TotalDate, LastUpdate from mod_aspstats order by LastUpdate desc limit 1";
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $this->loadLiveCounts();
        return;
    }
    if (!mysql_num_rows($result)) {
        $this->loadLiveCounts();
        return;
    }

    // Check the cache time now.
    $curRow = mysql_fetch_array($result);
    $lastUpd = strtotime($curRow["LastUpdate"]);
    $timeDif = time() - $lastUpd;
    if ($timeDif > $this->cacheLife) {
        $this->loadLiveCounts();
        return;
    }

    $this->virusToday   = $curRow["VirusToday"];
    $this->virusTotal   = $curRow["VirusTotal"];
    $this->spamToday    = $curRow["SpamToday"];
    $this->spamTotal    = $curRow["SpamTotal"];
    $this->totalDate    = $curRow["TotalDate"];
    list($year, $month, $day) = split("-", $this->totalDate);
    $this->totalDateDisp = date("F j, Y", strtotime("$month/$day/$year"));
}

/*
** loadLiveCounts - Loads the spam stats from the live server and stores
**                  them in our class variables and in the cache.
*/

function loadLiveCounts()
{
    global $ACMSCfg;
    // Connect to the spam stats database.
    $aspdb = mysql_pconnect($ACMSCfg['mod_aspstats']['aspStatsDBHost'], $ACMSCfg['mod_aspstats']['aspStatsDBUser'], $ACMSCfg['mod_aspstats']['aspStatsDBPass']);
    if (!$aspdb) {
        return;
    }

    if (!@mysql_select_db($ACMSCfg['mod_aspstats']['aspStatsDBName'])) {
        return;
    }

    // We are connected to the spam stats table.
    // Get today's stats.
    $sql = "select sum(xbl) + sum(rbl) + sum(spike) + sum(accessio) + sum(assassin), sum(blargav) from Stats where date = curdate()";
    $res = @mysql_query($sql, $aspdb);
    if (!$res) return;

    $curRow = @mysql_fetch_array($res);
    $this->spamToday  = $curRow[0];
    $this->virusToday = $curRow[1];

    // Now, get the total stats.
    $sql = "select sum(xbl) + sum(rbl) + sum(spike) + sum(accessio) + sum(assassin), sum(blargav) from Stats";
    $res = @mysql_query($sql, $aspdb);
    if (!$res) return;
    if (!@mysql_num_rows($res)) return;
    $curRow = @mysql_fetch_array($res);
    $this->spamTotal  = $curRow[0];
    $this->virusTotal = $curRow[1];
    
    // Now get how long this has been since
    $sql = "select min(date) from Stats";
    $res = @mysql_query($sql, $aspdb);
    if (!$res) return;
    if (!@mysql_num_rows($res)) return;
    $curRow = @mysql_fetch_array($res);
    $this->totalDate  = $curRow[0];

    // Turn the date into something more human friendly
    list($year, $month, $day) = split("-", $this->totalDate);
    $this->totalDateDisp = date("F j, Y", strtotime("$month/$day/$year"));

    // All done.  Close the database connection.
    @mysql_close($aspdb);

    // Store the cache results.
    $this->updateASPCache();
}

/*
** updateASPCache - Updates the cache with the currently loaded counters.
*/

function updateASPCache()
{
    global $app;

    $sql  = "replace into mod_aspstats (InternalID, VirusToday, VirusTotal, SpamToday, SpamTotal, TotalDate, LastUpdate) values (1, ";
    $sql .= $this->virusToday . ", ";
    $sql .= $this->virusTotal . ", ";
    $sql .= $this->spamToday  . ", ";
    $sql .= $this->spamTotal  . ", ";
    $sql .= "'" . $this->totalDate  . "', ";
    $sql .= "'" . date("Y-m-d H:i:s") . "')";

    $result = mysql_query($sql, $app->acmsDB);
}

/*
** getASPStats - Gets the ASP stats from the database and returns the
**               formatted block.
*/

function getASPStats()
{
    $retVal = "";
    $errText = "Unable to obtain Advanced Spam Protection statistics at this
        time.  Please try again later.";
    $template = "aspstats.tpl";

    // Connect to the spam stats database.
    $this->loadCounts();
    if (!strlen($this->totalDateDisp)) {
        // Couldn't get the date from the cache or the live system.
        $section = "asperror.tpl";
    }

    // Now, create our template
    $aspStatsTpl = new acmsTemplate($this->templatePath . $template);
    //$aspStatsTpl->setOption("use_preg", false);
    //$aspStatsTpl->setOption("preserve_data", true);
    //$aspStatsTpl->loadTemplateFile($this->templateFile, true, false);

    //$aspStatsTpl->setCurrentBlock($section);
    $aspStatsTpl->assign("SpamToday",      number_format($this->spamToday));
    $aspStatsTpl->assign("VirusesToday",   number_format($this->virusToday));
    $aspStatsTpl->assign("SpamTotal",      number_format($this->spamTotal));
    $aspStatsTpl->assign("VirusesTotal",   number_format($this->virusTotal));
    $aspStatsTpl->assign("ASPStartDate",   $this->totalDateDisp);
    //$aspStatsTpl->parseCurrentBlock($section);

    return $aspStatsTpl->get();
}

};  // aspstatsModule class

?>
