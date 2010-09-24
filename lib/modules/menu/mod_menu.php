<?php
/**
 * mod_menu.php - The module that is responsible for handling menus.
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
if (eregi('mod_menu.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers, $modules;

$modules['menu'] = new menuModule();

// The main ACMS class
class menuModule {

/*
** menuModule - Constructor.  This handles the setup of the various internal
**              functions.
*/

function menuModule()
{
} // menuModule

/*
** exec - Entry point.  This is the function that gets called by the ACMS
**        kernel when the module is loaded.  It should check for arguments
**        and then call the appropriate admin section.
*/

function exec($args = "")
{
    global $app;

    $retVal = "";

    if (strlen($args)) $retVal = $this->loadMenu($args);

    return $retVal;
}   // exec

/*
** loadMenu - This function actually loads the requested menu and returns
**            the contents.  Parsing does *not* take place here since the
**            data we return will be sent back to the main parsing routine.
*/

function loadMenu($menuName) 
{
    global  $app;
    $retVal = "";

    $items = array();
    // Load up the menu definition.
    $sql = "select MenuID, ACLID, Spacer from Menus where MenuName = '" . mysql_escape_string($menuName) . "'";
    $result = mysql_query($sql, $app->acmsDB);
    if ($result) {
        if (mysql_num_rows($result)) {
            $menu = mysql_fetch_array($result);
            if ($app->aclRead($menu['ACLID'])) {
                // Okay, we can read the menu, lets start adding items to it.
                $sql = "select MenuItemID, ParentID, Weight, ACLID, ItemType, ItemName, ItemContent from MenuItems where MenuID = " . $menu['MenuID'];
                $result = mysql_query($sql, $app->acmsDB);
                if ($result) {
                    if (mysql_num_rows($result)) {
                        // Okay, walk through and get all of the items.
                        while($curRow = mysql_fetch_array($result)) {
                            array_push($items, $curRow);
                        }
                        // echo "<pre>"; print_r($items); echo "</pre>";

                        // Now, walk through the items one by one.
                        foreach($items as $menuItem) {
                            if ($app->aclRead($menuItem['ACLID'])) {
                                // They can read the item, now, lets
                                // add it to our return string.
                                if (strlen($retVal)) $retVal .= $menu['Spacer'];
                                $retVal .= $menuItem['ItemContent'];
                            }
                        }
                    }
                }
            }
        }
    }

    return $retVal;
}


};  // menuModule class


?>
