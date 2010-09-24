<?php
/**
 * auth_ldap.php - An authentication plugin for checking against an LDAP
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


function ldap_checkLogin($username, $password)
{
    global $ACMSCfg;

    $retVal = false;

    // Grab our authentication configuration
    $ldapServer = $ACMSCfg['auth_ldap']['LDAPServer'];
    $baseTree   = $ACMSCfg['auth_ldap']['LDAPBase'];

    if (isset($username) && isset($password) && strlen($username) && strlen($password)) {

        // Both a username and password were given.  Try to authenticate.
        
        // Logins can be letters, numbers, underscores and dashes only
        $login = ereg_replace("[^a-zA-Z0-9\._-]", "", $username);
        $dn = "uid=$login,$baseTree";

        $pw   = $password;

        $ds = ldap_connect($ldapServer);
        //echo "LDAP connect result is " . $ds . "</br>";
        if ($ds) {
            //ldap_set_option($ds,LDAP_OPT_PROTOCOL_VERSION,2);
            //echo "Binding...";
            $r = ldap_bind($ds, $dn, $pw);
            if ($r) {
                // If we bind successfully, then we're authenticated.
                $retVal = true;
            }
        }
    } 

    return $retVal;
}
?>
