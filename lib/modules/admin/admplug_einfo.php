<?php
/**
 * admplug_einfo.php - The admin module for the text handler.
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
if (eregi('admplug_einfo.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global variables we use to load ourselves into.
global $admplugins;


// The admplugin_einfo class.
class admplugin_einfo {

// Class Variables
var $templatePath;
var $pluginName;
var $handler;

// Variables we use.  These could be obtained via globals or from the
// databse.

var $Action;
var $ChunkID;
var $ChunkName;
var $ChunkTitle;
var $Chunk;
var $Perms;

/*
** admplugin_einfo - Constructor.  This handles the setup of the various admin
**                  functions of this plugin.
*/

function admplugin_einfo()
{
    // Get the template file name.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts["dirname"] . "/admplug_";
    $this->pluginName   = "einfo";
    $this->handler      = "einfo";
    // Clear our globals
    $this->ChunkID      = "";
    $this->ChunkName    = "";
    $this->ChunkTitle   = "";
    $this->Chunk        = "";
    $this->Perms        = 644;

} // admplugin_einfo constructor

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
        case "create":
            $this->create();
            break;

        case "EditeInfo":
            $this->edit($parms);
            break;

    }
}
/*
** edit - Handles editing of the specified Chunk ID.
*/

function edit($ChunkID)
{
    global $app;

    // Check our request variables to see if we need to load variables.
    if (!isset($_REQUEST['Action'])) {
        if (!$this->loadChunk($ChunkID)) return;
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
** create - Shows the edit form and handles the input from the
**          user.  Once the user has entered the basic data 
**          we save and then take them to the edit form.
*/

function create()
{
    global $app;

    // Action being set determines if we are showing the page for the first
    // time or if we are going to give them a blank page to look at.
    if (isset($_REQUEST['Action'])) {
        // If we made it here, there is an action that has been set that we
        // need to look at.  In other words, the user hit a button on the
        // form.
        $this->getFormVars();

        switch($_REQUEST['Action']) {
            case "create":
                // We came from the edit form.  Do something with what the 
                // user gave us.
                $this->processEdit();
                break;

            default:
                $this->showEditForm("create");
                break;
        }
    } else {
        // No action set, just show them the form.
        $this->showEditForm("create");
    }
}

/*
** showEditForm - Displays the edit form, filling in any variables that
**                we need.
*/

function showEditForm($act = "edit")
{
    global $app;

    $tpl = new acmsTemplate($this->templatePath . "einfo_edit_form.tpl");

    $tpl->assign("Action",       $act);
    $tpl->assign("ChunkID",      $this->ChunkID);
    $tpl->assign("ChunkName",    $this->ChunkName);
    $tpl->assign("Chunk",        $this->Chunk);
    $tpl->assign("ChunkTitle",   $this->ChunkTitle);
    $tpl->assign("ChunkArea",    getChunkFormField($this->Chunk));
    $tpl->assign("Perms",        $this->Perms);

    $app->addBlock(10, CONTENT_ZONE, "Edit eInfo Chunk", $tpl->get());
}

/*
** loadChunk - Loads the specified chunk into our class variables.
*/

function loadChunk($ChunkID)
{
    global $app;

    $cc = new chunkClass("einfo");

    if (!$cc->fetchID($ChunkID)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit eInfo Chunks", "Unable to load the specified chunk.");
        return false;
    }

    $this->ChunkID      = $cc->get("ChunkID");
    $this->ChunkName    = $cc->get("ChunkName");
    $this->Chunk        = $cc->get("Chunk");
    $this->ChunkTitle   = $cc->get("Title");
    $this->Perms        = $cc->get("Perms");

    return true;
}

/*
** getFormVars - Gets the form variables and loads them into our class
**               variables.  We can clean them here if we want/need to.
*/

function getFormVars()
{
    $this->ChunkID      = stripslashes($_REQUEST["ChunkID"]);
    $this->ChunkName    = stripslashes($_REQUEST["ChunkName"]);
    $this->Chunk        = stripslashes($_REQUEST["Chunk"]);
    $this->ChunkTitle   = stripslashes($_REQUEST["ChunkTitle"]);
    $this->Perms        = stripslashes($_REQUEST["Perms"]);
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
        $this->refreshToChunkList();
        // We shouldn't return from listChunks, but just in case....
        exit;
    }

    // Check for a Preview request
    if (isset($_REQUEST["Preview"]) && strlen($_REQUEST["Preview"])) {
        $app->addBlock(5, CONTENT_ZONE, "Preview - " . $this->ChunkName, $app->parseChunk($this->Chunk));
        $this->showEditForm();
        return;
    }

    // Okay, they must want to save.
    // Our form variables have already been loaded, so verify that we have
    // a chunk ID.  If we don't, then its an insert.  Otherwise, make sure
    // there is no name collision.

    $errText = "";

    // We'll do some basic validation first.
    if (!strlen($this->ChunkName)) {
        $errText .= "<li>All chunks must have a unique name</li>";
    }

    if (!strlen($this->Chunk)) {
        $errText .= "<li>Chunks must have some content</li>";
    }

    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the chunk.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Chunk - Error", $errText);
        $this->showEditForm();
        return;
    }
    
    if (!$this->ChunkID) {
        // Okay, we're doing an insert.  We just need to check that
        // there are no chunks with this name already.
        // Make sure we don't have a chunk with this name already.
        $sql = "select ChunkID from Chunks where ChunkName = '" . mysql_escape_string($this->ChunkName) . "'";
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Chunk - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        if (mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Chunk - Error", "A chunk already exists in the database with the name '" . $this->ChunkName . "'.  Chunk names must be unique.");
            $this->showEditForm();
            return;
        }

        // If we made it here, we're good.
        $cc = new chunkClass("einfo");
        $cc->set("ChunkName",       $this->ChunkName);
        $cc->set("Handler",         "einfo");
        $cc->set("Chunk",           $this->Chunk);
        $cc->set("Perms",           $this->Perms);
        $cc->set("Title",           $this->ChunkTitle);
        $cc->insert();

        $this->refreshToChunkList();
        return;

    } else {
        // Make sure we don't have a chunk with this name already.
        $sql = "select ChunkID from Chunks where ChunkName = '" . mysql_escape_string($this->ChunkName) . "' and ChunkID <> " . mysql_escape_string($this->ChunkID);
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Chunk - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        if (mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Chunk - Error", "A chunk already exists in the database with the name '" . $this->ChunkName . "'.  Chunk names must be unique.");
            $this->showEditForm();
            return;
        }

        // Make sure the chunk that we are replacing is a einfo chunk.
        // Don't trust the user input you know.
        $cc = new chunkClass("einfo");
        if (!$cc->fetchID($this->ChunkID)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Chunk - Error", "Unable to locate the chunk being edited.  Try again.");
            $this->showEditForm();
            return;
        }

        $cc->set("ChunkName",       $this->ChunkName);
        $cc->set("Chunk",           $this->Chunk);
        $cc->set("Title",           $this->ChunkTitle);
        $cc->set("Perms",           $this->Perms);
        if (!$cc->update()) {
            $app->addBlock(0, CONTENT_ZONE, "Save Chunk - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        // We saved, return to the chunk list.
        $this->refreshToChunkList();

    }
    
    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the chunk.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Chunk - Error", $errText);
        $this->showEditForm();
        return;
    }
    

}

/*
** listChunks - Uses a location tag to return the user to the ListChunks
**              page.
*/

function refreshToChunkList()
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

};  // admplugin_einfo class

$admplugins['einfo'] = new admplugin_einfo();
?>
