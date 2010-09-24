<?php
/**
 * admplug_block.php - The block editor plugin for the admin module.
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
if (eregi('admplug_block.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global variables we use to load ourselves into.
global $admplugins;

$admplugins['block'] = new admplugin_block();

// The admplugin_block class.
class admplugin_block {

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
var $Perms;
var $Persistant;
var $Zone;
var $Active;
var $Weight;
var $Title;
var $TitleNav;
var $Footer;
var $FooterNav;
var $Chunk;

/*
** admplugin_block - Constructor.  This handles the setup of the various admin
**                   functions of this plugin.
*/

function admplugin_block()
{
    // Set our name.
    $this->pluginName       = "block";
    $this->handler          = "block";

    // Get the template file name.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts["dirname"] . "/admplug_block_";
    // Clear our globals
    $this->ChunkID          = "";
    $this->ChunkName        = "";
    $this->Perms            = 0;
    $this->Persistant       = 0;
    $this->Zone             = 0;
    $this->Active           = 0;
    $this->Weight           = 0;
    $this->Title            = "";
    $this->TitleNav         = "";
    $this->Footer           = "";
    $this->FooterNav        = "";
    $this->Chunk            = "";

} // admplugin_block constructor

/*
** menu - Returns our menu.
*/

function menu($expand) 
{
    // FIXME:  This shouldn't be hard-coded like this.
    $retVal = "";
    //$retVal = ":<a href=\"/admin/block/ListBlocks\">Blocks</a>\n";
    //$retVal .= ":<a href=\"/admin/block/CreateBlock\">Create Block</a>\n";
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
        case "CreateBlock":
        case "create":
            $this->create();
            break;

        case "EditBlock":
            $this->edit($parms);
            break;

        case "ListBlocks":
        default:
            $this->listBlocks();
            break;
    }
}

/*
** listChunks - Lists all of the blocks that are contained in the ACMS
*/

function listChunks()
{
    global $app, $ACMS_BASE_URI, $ACMSRewrite;
    $tpl = new acmsTemplate($this->templatePath . "listblocks.tpl");

    // Do our query and get our blocks.
    $sql = "select Chunks.ChunkID, Chunks.ChunkName, Chunks.Weight, Chunks.Active, Chunks_block.Zone, Chunks_block.Persistant, Chunks.UserID, Chunks.GroupID, Chunks.Perms from Chunks, Chunks_block where Chunks_block.ChunkID = Chunks.ChunkID order by Chunks.ChunkName";
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - List Blocks", "No blocks were found.");
        return;
    }

    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - List Blocks", "No blocks were found.");
        return;
    }

    $blocks = array();
    $rows = array();
    while ($curRow = mysql_fetch_array($result)) {
        // Load them all into memory
        array_push($rows, $curRow);
    }
    foreach($rows as $curRow) {
        // Create the URL to edit the chunk
        $editURL = "<a href=\"";
        if ($ACMSRewrite) {
            $editURL .= "/admin/block/EditBlock/" . $curRow["ChunkID"];
        } else {
            $editURL .= "$ACMS_BASE_URI?page=admin/block/EditBlock/" . $curRow["ChunkID"];
        }
        $editURL .= "\">" . $curRow["ChunkName"] . "</a>";

        array_push($blocks, array(
                    "ChunkID"       => $curRow["ChunkID"],
                    "ChunkName"     => $editURL,
                    "Weight"        => $curRow["Weight"],
                    "Zone"          => $curRow["Zone"],
                    "Active"        => $curRow["Active"],
                    "Persistant"    => $curRow["Persistant"],
                    "Owner"         => $app->getUser($curRow["UserID"]),
                    "Group"         => $app->getGroup($curRow["GroupID"]),
                    "Perms"         => $curRow["Perms"]
                    ));
    }
    $tpl->assign("Blocks", $blocks);
    return $tpl->get();
}

/*
** listBlocks - Lists the blocks in its own block.
*/

function listBlocks()
{
    global $app;
    $app->addBlock(10, CONTENT_ZONE, "Admin - List Blocks", $this->listChunks());
}

/*
** edit - Handles editing of the specified Chunk ID.
*/

function edit($ChunkID)
{
    global $app;

    // Check our request variables to see if we need to load variables.
    if (!isset($_REQUEST['Action'])) {
        if (!$this->loadBlock($ChunkID)) return;
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
** editBlock - This loads the block editor, which is surprisingly similar to
**             the text chunk editor, but has quite a few more variables.
*/

function editBlock($ChunkID)
{
    global $app;

    // Check our request variables to see if we need to load variables.
    if (!isset($_REQUEST['Action'])) {
        if (!$this->loadBlock($ChunkID)) return;
        $this->showEditForm();
        return;
    }

    // If we made it here, there is an action that has been set that we
    // need to look at.  In other words, the user hit a button on the
    // form.
    $this->getFormVars();

    //echo "Hello!";
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
** getFormVars - Gets the form variables and loads them into our class
**               variables.  We can clean them here if we want/need to.
*/

function getFormVars()
{
    global $app;
    $this->ChunkID          = $_REQUEST["ChunkID"];
    $this->ChunkName        = stripslashes($_REQUEST["ChunkName"]);
    if (isset($_REQUEST["Persistant"]) && strlen($_REQUEST["Persistant"])) {
        $this->Persistant  = 1;
    } else {
        $this->Persistant  = 0;
    }
    if ($_REQUEST["Zone"] >= 0 && $_REQUEST["Zone"] < count($app->zoneBlocks)) {
        $this->Zone    = $_REQUEST["Zone"];
    } else {
        $this->Zone    = 2;        // Center block
    }
    if (isset($_REQUEST["Active"]) && strlen($_REQUEST["Active"])) {
        $this->Active = 1;
    } else {
        $this->Active = 0;
    }
    $this->Weight      = stripslashes($_REQUEST["Weight"]);
    $this->Perms       = stripslashes($_REQUEST["Perms"]);
    $this->Title       = stripslashes($_REQUEST["Title"]);
    $this->TitleNav    = stripslashes($_REQUEST["TitleNav"]);
    $this->Footer      = stripslashes($_REQUEST["Footer"]);
    $this->FooterNav   = stripslashes($_REQUEST["FooterNav"]);
    $this->Chunk       = stripslashes($_REQUEST["Chunk"]);
}



/*
** loadBlock - Loads the specified chunk into our class variables.
*/

function loadBlock($ChunkID)
{
    global $app;

    $cc = new chunkClass("block");
    if (!$cc->fetchID($ChunkID, "block", "write", 0)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Block", "2 Unable to load the specified block.");
        return false;
    }

    $this->ChunkID          = $cc->get("ChunkID");
    $this->ChunkName        = $cc->get("ChunkName");
    $this->Persistant       = $cc->get("Persistant");
    $this->Zone             = $cc->get("Zone");
    $this->Weight           = $cc->get("Weight");
    $this->Active           = $cc->get("Active");
    $this->Perms            = $cc->get("Perms");
    $this->Title            = $cc->get("Title");
    $this->TitleNav         = $cc->get("TitleNav");
    $this->Footer           = $cc->get("Footer");
    $this->FooterNav        = $cc->get("FooterNav");
    $this->Chunk            = $cc->get("Chunk");

    return true;
}

/*
** showEditForm - Displays the block edit form, filling in any variables
**                     that we need.
*/

function showEditForm($action = "edit")
{
    global $app;

    $tpl = new acmsTemplate($this->templatePath . "edit_form.tpl");
    $tpl->assign("Action",             $action);
    $tpl->assign("ChunkID",            $this->ChunkID);
    $tpl->assign("ChunkName",          $this->ChunkName);
    // Set the checkbox for persistant
    if ($this->Persistant) {
        $tpl->assign("Persistant","checked");
    } else {
        $tpl->assign("Persistant","");
    }

    // Create the options for the Block Zone
    $bzopt = "";
    $i = 0;
    foreach($app->zoneBlocks as $zoneName) {
        $bzopt .= "<option value=\"$i\"";
        if ($i == $this->Zone) {
            $bzopt .= " selected";
        }
        $bzopt .= ">$zoneName</option>";
        $i++;
    };
    /*
    for ($i = 0; $i < 5; $i++) {
        $bzopt .= "<option";
        if ($i == $this->Zone) {
            $bzopt .= " selected";
        }
        $bzopt .= ">$i</option>";
    }
    */
    $tpl->assign("Zone",          $bzopt);
    
    // Set the checkbox for active
    if ($this->Active) {
        $tpl->assign("Active","checked");
    } else {
        $tpl->assign("Active","");
    }

    $tpl->assign("Weight",        $this->Weight);
    $tpl->assign("Perms",         $this->Perms);
    $tpl->assign("Title",         $this->Title);
    $tpl->assign("TitleNav",      $this->TitleNav);
    $tpl->assign("Footer",        $this->Footer);
    $tpl->assign("FooterNav",     $this->FooterNav);
    $tpl->assign("Chunk",         $this->Chunk);
    $tpl->assign("ChunkArea",     getChunkFormField($this->Chunk));

    $title = "Edit Block";
    if (!strcmp($action, "create")) $title = "Create Block";

    $app->addBlock(10, CONTENT_ZONE, $title, $tpl->get());
}

/*
** processEdit - The user hit either Save or Cancel on the edit block 
**                    form this function does the appropriate thing with 
**                    that.
*/

function processEdit()
{
    global $app;

    if (isset($_REQUEST["Cancel"])) {
        if (strlen($_REQUEST["Cancel"])) {
            // They hit cancel, abort.
            $this->refreshToBlockList();
            // We shouldn't return from that, but just in case...
            exit;
        }
    }

    // Okay, they must want to save.
    // Our form variables have already been loaded, so verify that we have
    // a chunk ID.  If we don't, then its an insert.  Otherwise, make sure
    // there is no name collision.

    $errText = "";

    // We'll do some basic validation first.
    if (!strlen($this->ChunkName)) {
        $errText .= "<li>All blocks must have a unique name</li>";
    }

    if (!strlen($this->Chunk)) {
        $errText .= "<li>Blocks must have some content</li>";
    }

    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the block.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Block - Error", $errText);
        $this->showEditForm();
        return;
    }
    
    if (!$this->ChunkID) {
        // Okay, we're doing an insert.  We just need to check that
        // there are no chunks with this name already.
    } else {
        // Make sure we don't have a chunk with this name already.
        $sql = "select ChunkID from Chunks where ChunkName = '" . mysql_escape_string($this->ChunkName) . "' and ChunkID <> " . mysql_escape_string($this->ChunkID);
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Block - Error", "1 Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        if (mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Block - Error", "A block already exists in the database with the name '" . $this->ChunkName . "'.  Chunk names must be unique.");
            $this->showEditForm();
            return;
        }

        // If we made it here, we're good to replace the database entry.
        $cc = new chunkClass();
        $cc->fetchID($this->ChunkID, "block", "write", 0);
        $cc->set("ChunkName",       $this->ChunkName);
        $cc->set("Persistant",      $this->Persistant);
        $cc->set("Zone",            $this->Zone);
        $cc->set("Weight",          $this->Weight);
        $cc->set("Active",          $this->Active);
        $cc->set("Perms",           $this->Perms);
        $cc->set("Title",           $this->Title);
        $cc->set("TitleNav",        $this->TitleNav);
        $cc->set("Footer",          $this->Footer);
        $cc->set("FooterNav",       $this->FooterNav);
        $cc->set("Chunk",           $this->Chunk);
        if (!$cc->update()) {
            $app->addBlock(0, CONTENT_ZONE, "Save Block - Error", "2 Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }

        // We saved, return to the block list.
        $this->refreshToBlockList();

    }
    
    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the block.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Block - Error", $errText);
        $this->showEditForm();
        return;
    }
}
    
/*
** refreshToBlockList - Uses a location directive to return the user to the 
**                      ListBlocks page.
*/

function refreshToBlockList()
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
                $this->processCreate();
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
** processCreate - The user hit either Save or Cancel on the create form.
**                 this function does the appropriate thing with that.
*/

function processCreate()
{
    global $app;

    if (isset($_REQUEST["Cancel"]) && strlen($_REQUEST["Cancel"])) {
        // The user hit cancel, abort
        $this->refreshToBlockList();
        // We shouldn't return from listPages, but just in case....
        exit;
    }

    // Our form variables have already been loaded, so verify that we have
    // a Chunk ID.  If we don't, then its an insert.  Otherwise, make sure
    // there is no name collision.

    $errText = "";

    // We'll do some basic validation first.
    if (!strlen($this->ChunkName)) {
        $errText .= "<li>All blocks must have a unique name</li>";
    }

    if (!strlen($this->Title)) {
        $errText .= "<li>All blocks must have a title of some sort</li>";
    }

    // Make sure we don't have a chunk with this name already.
    $sql = "select ChunkID from Chunks where ChunkName = '" . mysql_escape_string($this->ChunkName) . "'";
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $errText .= "<li>Error while communicationg with the database.  Please try again later.";
    } else if (mysql_num_rows($result)) {
        $errText .= "<li>A chunk already exists in the database with the name '" . $this->ChunkName . "'.  Page/chunk names must be unique.";
    }

    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the block.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Page - Error", $errText);
        $this->showEditForm("create");
        return;
    }

    $cc = new chunkClass("block");
    $cc->set("ChunkName",       $this->ChunkName);
    $cc->set("Persistant",      $this->Persistant);
    $cc->set("Zone",            $this->Zone);
    $cc->set("Weight",          $this->Weight);
    $cc->set("Active",          $this->Active);
    $cc->set("Perms",           $this->Perms);
    $cc->set("Title",           $this->Title);
    $cc->set("TitleNav",        $this->TitleNav);
    $cc->set("Footer",          $this->Footer);
    $cc->set("FooterNav",       $this->FooterNav);
    $cc->set("Chunk",           $this->Chunk);
    if (!$cc->insert()) {
        $app->addBlock(0, CONTENT_ZONE, "Save Block - Error", "Error while communicationg with the database.  Please try again later.");
        $this->showEditForm("create");
        return;
    }

    $this->refreshToBlockList();
}

};  // admplugin_block class


?>
