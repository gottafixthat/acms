<?php
/**
 * auth_php3.php - An authentication plugin for checking against a POP3
 *                 server.
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

function pop3_checkLogin($username, $password)
{
    global $ACMSCfg;
    $retVal = false;
	$serverName = $ACMSCfg['auth_pop3']['POP3Server'];

    if (isset($username) && isset($password) && strlen($username) && strlen($password)) {
        // Both a username and password were given.  Try to authenticate.
        
        // Logins can be letters, numbers, underscores and dashes only
        $login = ereg_replace("[^a-zA-Z0-9\._-]", "", $username);
        $pw   = $password;

		$fp = fsockopen("$serverName",'110', &$errno, &$errstr);
		if(!$fp) {
			return $retVal;
		}

		// Block and wait for a reply
		socket_set_blocking($fp,1);
		$banner = fgets($fp,1024);

		fwrite($fp,"USER $username\r\n");
		$reply = fgets($fp,512);

		// If the username is OK send the password
		// If the username is not OK don't bother
		if(!(eregi("^\+OK",$reply))) {
			$retVal = false;
		} else {
			fwrite($fp,"PASS $password\r\n");
			$reply = fgets($fp,512);
			if(eregi("^\+OK",$reply)) {
				$retVal = true;
			} else {
				$retVal = false;
			}
		}
		// Clean up the mess
		fwrite($fp,"QUIT\r\n");
		fclose($fp);
    }

    return $retVal;
}
?>
