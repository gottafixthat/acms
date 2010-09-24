<?php
/**
 * mod_services.php - A wrapper to the catcon class to display categorized
 *                    services.
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
if (eregi('mod_services.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

global $INCLUDE_DIR;

require_once("catconmodule.php");

// The global handlers variable that we will load ourselves into.
global $handlers, $modules, $app;

// The module class.
class servicesModule extends catconModule {

var $templatePath;

/*
** servicesModule - Constructor.  This handles the setup of the various 
**                  internal functions.
*/

function servicesModule()
{
    global $app;
    $this->catconModule("services", "Services");
    // FIXME: There should be an easy way to override templates with a theme.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts["dirname"] . "/";
    //$app->setPageTitle("Services");
} // servicesModule

};  // servicesModule class

$modules['services'] = new servicesModule();
?>
