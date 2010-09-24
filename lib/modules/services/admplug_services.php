<?php
/**
 * admplug_services.php - Services administration.
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
if (eregi('admplug_services.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

require_once("catconadmin.php");

// The global variables we use to load ourselves into.
global $admplugins;

// The admplugin_stories class.
class admplugin_services extends admplugin_catcon {

// This needs to be set so the admin module knows who we are.
var $pluginName;
// This tells the admin panel what handler we will also work for.
var $handler;

/*
** admplugin_services - Constructor.  This handles the setup of the various
**                      admin functions of this plugin.
*/

function admplugin_services()
{
    // Set our name.
    $this->admplugin_catcon("services");
    $this->pluginName       = "services";
    $this->handler          = "";
} // admplugin_services constructor

};  // admplugin_stories class

$admplugins['services'] = new admplugin_services();
?>
