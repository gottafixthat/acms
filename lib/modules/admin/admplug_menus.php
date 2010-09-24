<?php
/**
 * admplug_menus.php - Menu editor plugin for the admin module.
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
if (eregi('admplug_menus.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global variables we use to load ourselves into.
global $admplugins;

$admplugins['menus'] = new admplugin_menus();

// The admplugin_menus class.
class admplugin_menus {

// This needs to be set so the admin module knows who we are.
var $pluginName;
// This tells the admin panel what handler we will also work for.
var $handler;

// Class Variables
var $templatePath;

// Variables we use.  These could be obtained via globals or from the
// databse.

var $Action;
var $ChunkID;
var $ChunkName;
var $Title;
var $Chunk;
var $Perms;

/*
** admplugin_menus - Constructor.  This handles the setup of the various admin
**                  functions of this plugin.
*/

function admplugin_menus()
{
    // Set our name.
    $this->pluginName       = "menus";
    $this->handler          = "menu";

    // Get the template file name.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts["dirname"] . "/admplug_menus_";
    // Clear our globals
    $this->ChunkID          = "";
    $this->ChunkName        = "";
    $this->Title            = "";
    $this->Chunk            = "";
    $this->Perms            = 0;

} // admplugin_menus constructor

/*
** menu - Returns our menu.
*/

function menu($expand) 
{
    $retVal ="";
    // FIXME:  This shouldn't be hard-coded like this.
    //$retVal .= ":<a href=\"/admin/menus/ListMenus\">Menus</a>\n";
    //$retVal .= ":<a href=\"/admin/menus/CreateMenu\">Create Menu</a>\n";
    //$retVal .= ":::<a href=\"/admin/menus/Grr\">Create Menu</a>\n";
    return $retVal;
}

/*
** exec - This gets called by the admin module when it is passing control
**        to this plugin.
*/

function exec($args)
{
    global $app;
    $parms = "";
    if (strpos($args, "/") !== FALSE) {
        list($args, $parms) = split("/", $args, 2);
    }

    switch ($args) {
        case "CreateMenu":
        case "create":
            $this->create();
            break;

        case "EditMenu":
            $this->edit($parms);
            break;

        case "ListMenus":
        default:
            $this->listMenus();
            break;
    }
}

/*
** listChunks - Lists all of the menus that are contained in the ACMS
*/

function listChunks()
{
    global $app, $ACMS_BASE_URI, $ACMSRewrite;
    $tpl = new acmsTemplate($this->templatePath . "listmenus.tpl");

    // Do our query and get our blocks.
    $sql = "select ChunkID, ChunkName, Chunk, Perms, Title, Active from Chunks where Handler = 'menu' order by ChunkName";
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - List Menus", "No Menus were found.");
        return;
    }

    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - List Menus", "No Menus were found.");
        return;
    }

    $menus = array();
    while ($curRow = mysql_fetch_array($result)) {
        // Create the URL to edit the chunk
        $editURL = "<a href=\"";
        if ($ACMSRewrite) {
            $editURL .= "/admin/menus/EditMenu/" . $curRow["ChunkID"];
        } else {
            $editURL .= "$ACMS_BASE_URI?page=admin/menus/EditMenu/" . $curRow["ChunkID"];
        }
        $editURL .= "\">" . $curRow["ChunkName"] . "</a>";
        array_push($menus, array(
                    "ChunkID" => $curRow["ChunkID"],
                    "ChunkName" => $editURL,
                    "Title" => $curRow["Title"],
                    "Perms" => $curRow["Perms"]
                    ));

    }
    $tpl->assign("Menus", $menus);
    return $tpl->get();
}

/*
** listMenus - Displays all of the menus in its own listing.
*/

function listMenus()
{
    global $app;
    $app->addBlock(10, CONTENT_ZONE, "Admin - List Menus", $this->listChunks());
}

/*
** edit - Handles editing of the specified menu based on its ChunkID
*/

function edit($ChunkID)
{
    global $app;

    // Check our request variables to see if we need to load variables.
    if (!isset($_REQUEST['Action'])) {
        if (!$this->loadMenu($ChunkID)) return;
        $this->showEditForm();
        return;
    }

    // If we made it here, there is an action that has been set that we
    // need to look at.  In other words, the user hit a button on the
    // form.
    $this->getFormVars();

    switch($_REQUEST['Action']) {
        case "edit":
            // We came from the edit form.  Do something with what the 
            // user gave us.
            $this->processEdit();
            break;

        default:
            $this->showEditForm();
            break;
    }
}

/*
** showEditForm - Displays the edit form, filling in any variables that
**                we need.
*/

function showEditForm()
{
    global $app;

    $tpl = new acmsTemplate($this->templatePath . "edit_form.tpl");
    $tpl->assign("Action",         "edit");
    $tpl->assign("ChunkID",        $this->ChunkID);
    $tpl->assign("ChunkName",      $this->ChunkName);
    $tpl->assign("Chunk",          $this->Chunk);
    $tpl->assign("Title",          $this->Title);
    $tpl->assign("Perms",          $this->Perms);

    $app->addBlock(10, CONTENT_ZONE, "Edit Menu", $tpl->get());
}

/*
** loadMenu - Loads the specified menu into our class variables.
*/

function loadMenu($ChunkID)
{
    global $app;

    $sql = "select ChunkID, ChunkName, Chunk, Title, Active, Perms from Chunks where Handler = 'menu' and ChunkID = " . mysql_escape_string($ChunkID);
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Menu", "Unable to load the specified menu.");
        return false;
    }

    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Menu", "Unable to load the specified menu.");
        return false;
    }

    $menu = mysql_fetch_array($result);
    $this->ChunkID          = $menu["ChunkID"];
    $this->ChunkName        = $menu["ChunkName"];
    $this->Title            = $menu["Title"];
    $this->Chunk            = $menu["Chunk"];
    $this->Perms            = $menu["Perms"];

    return true;
}

/*
** getFormVars - Gets the form variables and loads them into our class
**               variables.  We can clean them here if we want/need to.
*/

function getFormVars()
{
    if (isset($_REQUEST["ChunkID"])) $this->ChunkID     = $_REQUEST["ChunkID"];
    else $this->ChunkID = 0;
    $this->ChunkName        = stripslashes($_REQUEST["ChunkName"]);
    $this->Title            = stripslashes($_REQUEST["Title"]);
    $this->Chunk            = stripslashes($_REQUEST["Chunk"]);
    $this->Perms            = $_REQUEST["Perms"];
}

/*
** processEdit - The user hit either Save or Cancel on the edit form.
**               this function does the appropriate thing with that.
*/

function processEdit()
{
    global $app;

    if (isset($_REQUEST["Cancel"]) && strlen($_REQUEST["Cancel"])) {
        // The user hit cancel, abort
        $this->refreshToListMenus();
        // We shouldn't return from listPages, but just in case....
        exit;
    }

    // Check to see if there was another action, such as Add.
    
    // Okay, if we made it here, they must want to save.
    // Our form variables have already been loaded, so verify that we have
    // a Chunk ID.  If we don't, then its an insert.  Otherwise, make sure
    // there is no name collision.

    $errText = "";

    // We'll do some basic validation first.
    if (!strlen($this->ChunkName)) {
        $errText .= "<li>All menus must have a unique name</li>";
    }

    if (!strlen($this->Chunk)) {
        $errText .= "<li>All menus must some content</li>";
    }

    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the menu.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Menu - Error", $errText);
        $this->showEditForm();
        return;
    }
    
    if (!$this->ChunkID) {
        // Okay, we're doing an insert.  We just need to check that
        // there are no chunks with this name already.
        $sql = "select ChunkID from Chunks where ChunkName = '" . mysql_escape_string($this->ChunkName) . "'";
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Menu - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        if (mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Menu - Error", "A chunk already exists in the database with the name '" . $this->ChunkName . "'.  Chunk names must be unique.");
            $this->showEditForm();
            return;
        }
        // Create our insert query.
        $sql  = "insert into Chunks(ChunkID, ChunkName, Handler, Chunk, Title, Perms) values (0, ";
        $sql .= "'" . mysql_escape_string($this->ChunkName) . "', ";
        $sql .= "'menu',";
        $sql .= "'" . mysql_escape_string($this->Chunk) . "', ";
        $sql .= "'" . mysql_escape_string($this->Title) . "', ";
        $sql .= $this->Perms;
        $sql .= ")";
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Menu - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        // We saved, return to the page list.
        $this->refreshToListMenus();

    } else {
        // Make sure we don't have a menu with this name already.
        $sql = "select ChunkID from Chunks where ChunkName = '" . mysql_escape_string($this->ChunkName) . "' and ChunkID <> " . mysql_escape_string($this->ChunkID);
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Menu - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        if (mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Menu - Error", "A chunk already exists in the database with the name '" . $this->ChunkName . "'.  Chunk names must be unique.");
            $this->showEditForm();
            return;
        }

        // If we made it here, we're good to replace the database entry.
        $sql  = "update Chunks set ";
        $sql .= "ChunkName = '" . mysql_escape_string($this->ChunkName) . "', ";
        $sql .= "Title = '" . mysql_escape_string($this->Title) . "', ";
        $sql .= "Chunk = '" . mysql_escape_string($this->Chunk) . "', ";
        $sql .= "Perms = " . $this->Perms;
        $sql .= " where ChunkID = " . mysql_escape_string($this->ChunkID);
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Menu - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        // We saved, return to the page list.
        $this->refreshToListMenus();

    }
    
    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the menu.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Menu - Error", $errText);
        $this->showEditForm();
        return;
    }
}

/*
** listMenus - Uses a location tag to return the user to the ListMenus
**             page.
*/

function refreshToListMenus()
{
    global $app;
    ob_end_clean();
    $retURI = "/admin/ListChunks";
    if (isset($app->acmsSessVars['EditChunkReturnURI'])) {
        $retURI = $app->acmsSessVars['EditChunkReturnURI'];
        $app->setSessVar("EditChunkReturnURI", "");
    }
    header("Location: $retURI");
    exit;
}

/*
** create - Shows the create page form and handles the input from the
**          user.  Once the user has entered basic page data (title, name,
**          etc) we save and then take them to the edit form.
*/

function create()
{
    global $app;

    // Check our request variables to see if we need to load variables.
    if (!isset($_REQUEST['Action'])) {
        $this->showEditForm();
        return;
    }

    // If we made it here, there is an action that has been set that we
    // need to look at.  In other words, the user hit a button on the
    // form.
    $this->getFormVars();

    switch($_REQUEST['Action']) {
        case "edit":
            // We came from the edit form.  Do something with what the 
            // user gave us.
            $this->processEdit();
            break;

        default:
            $this->showEditForm();
            break;
    }
}



};  // admplugin_menus class


?>
