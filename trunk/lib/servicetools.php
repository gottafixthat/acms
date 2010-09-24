<?php
/**
 * servicetools.php - Various service related tools.
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


/** getDialupNumbers - Looks for local dialup access numbers for the
  * specified NPA/NXX.
  * @return An array of local numbers, or an empty array if there are none.
  */
function getDialupNumbers($npa, $nxx)
{
    global $app, $ACMSCfg;
    $errno = 0;
    $errstr = "";
    $host = $ACMSCfg["mod_servicefinder"]["CallfinderHost"];
    $port = $ACMSCfg["mod_servicefinder"]["CallfinderPort"];
    $mpid = $ACMSCfg["mod_servicefinder"]["MegapopID"];
    $opts = "";
    $delim = 6;  // Ctrl-F
    $maxNumbers = 10;
    if (isset($ACMSCfg["mod_servicefinder"]["MaxNumbers"])) $maxNumbers = $ACMSCfg["mod_servicefinder"]["MaxNumbers"];

    $numbers     = array();
    $sortnumbers = array();

    // Check to see if we have local numbers.
    $db = $app->newDBConnection();
    $sql = "select AccessNumber from dial_npanxx where NPA = $npa and NXX = $nxx";
    $result = mysql_query($sql, $db);
    if ($result && mysql_num_rows($result)) {
        $curRow = mysql_fetch_array($result);
        $number = $curRow['AccessNumber'];
        // Get the city & state its in.
        $loc = "";
        $sql = "select City, State from dial_cities where AccessNumber = $number";
        $result = mysql_query($sql, $db);
        if ($result && mysql_num_rows($result)) {
            $curRow = mysql_fetch_array($result);
            $loc = $curRow["City"] . ", " . $curRow["State"];
        }
        $matchlevel = 2;
        $nnpa = substr($number,0,3);
        $nnxx = substr($number,3,3);
        $nsuf = substr($number,6,4);
        array_push($numbers, array("number" => $number, "npa" => $nnpa, "nxx" => $nnxx, "suffix" => $nsuf, "code" => "", "location" => $loc, "info" => "", "matchlevel" => $matchlevel));
    }
    
    $doMegapop = 0;
    if (isset($ACMSCfg["mod_servicefinder"]["DoMegapop"])) {
        if ($ACMSCfg["mod_servicefinder"]["DoMegapop"]) {
            $doMegapop = 1;
            if (isset($ACMSCfg["mod_servicefinder"]["LocalSkipsMegapop"])) {
                if ($ACMSCfg["mod_servicefinder"]["LocalSkipsMegapop"]) {
                    if (count($numbers)) $doMegapop = 0;
                }
            }
        }
    }
   
    if ($doMegapop) {
        $sock = fsockopen($host, $port, &$errno, &$errstr, 5);
        if (!$sock) return "";  // Unable to connect.

        // Some monkey on crack at Starnet decided that Ctrl-F would be a good
        // delimiter.  *sigh*
        $cmd = sprintf("%d%c%d%c%d%c%s\n", $npa, $delim, $nxx, $delim, $mpid, $delim, $opts);
        fputs($sock, $cmd);

        while (!feof($sock)) {
            $line = trim(fgets($sock, 4096));
            if (strlen($line)) {
                list($number, $pop_code, $loc, $info) = split(chr($delim), $line, 4);
                if (strcmp("000000000", $number)) {
                    $matchlevel = 0;
                    $nnpa = substr($number,0,3);
                    $nnxx = substr($number,3,3);
                    $nsuf = substr($number,6,4);
                    if ($nnpa == $npa) {
                        $matchlevel++;
                        if ($nnxx == $nxx) $matchlevel++;
                    }
                    if (count($numbers) < $maxNumbers) {
                        array_push($numbers, array("number" => $number, "npa" => $nnpa, "nxx" => $nnxx, "suffix" => $nsuf, "code" => $pop_code, "location" => $loc, "info" => $info, "matchlevel" => $matchlevel));
                    }
                }
            }
        }
        fclose($sock);
    }

    // If numbers were found, scan through them and create
    // a match level to hopefully give them the closest NPA/NXX.
    // Default 0, NPA matches score 1, NPA/NXX matches score 2.
    if (count($numbers)) {
        for ($i = 2; $i >= 0; $i--) {
            reset($numbers);
            foreach ($numbers as $pop) {
                if ($pop["matchlevel"] == $i) {
                    array_push($sortnumbers, $pop);
                }
            }
        }
    } 
    return $sortnumbers;
}
?>
