<?php
/**
 * mod_date.php -  A simple module that returns the current date and time.
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
if (eregi('mod_date.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers, $modules;

$modules['date'] = new dateModule();

// The module class.
class dateModule {

var $templatePath;

/*
** dateModule - Constructor.  This handles the setup of the various
**              internal functions.
*/

function dateModule()
{
} // dateModule

/*
** exec - Entry point.  This is the function that gets called by the ACMS
**        kernel when the module is loaded.  It should check for arguments
**        and then call the appropriate internal function.
*/

function exec($action, $args = array())
{
    global $app;

    $retVal = "";

    $retVal .= "\n<!-- phpdigExclude -->\n";
    $retVal .= date("F j, Y, g:ia T");
    $retVal .= "\n<!-- phpdigInclude -->\n";

    return $retVal;
}

};  // dateModule class

?>
