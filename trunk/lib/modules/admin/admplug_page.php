<?php
/**
 * admplug_page.php - The Page editor plugin for the admin module.
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
if (eregi('admplug_page.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global variables we use to load ourselves into.
global $admplugins;

$admplugins['page'] = new admplugin_page();

// The admplugin_page class.
class admplugin_page {

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
var $LinkText;
var $Title;
var $Perms;
var $Active;
var $ShowPersistant;
var $Chunk;

/*
** admplugin_page - Constructor.  This handles the setup of the various admin
**                  functions of this plugin.
*/

function admplugin_page()
{
    // Set our name.
    $this->pluginName       = "page";
    $this->handler          = "page";

    // Get the template file name.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts['dirname'] . "/admplug_";
    // Clear our globals
    $this->ChunkID          = "";
    $this->ChunkName        = "";
    $this->LinkText         = "";
    $this->Title            = "";
    $this->Perms            = 644;
    $this->Active           = 1;
    $this->ShowPersistant   = 1;
    $this->Chunk            = "";

} // admplugin_page constructor

/*
** menu - Returns our menu.
*/

function menu($expand) 
{
    // FIXME:  This shouldn't be hard-coded like this.
    $retVal = "";
    //$retVal .= ":<a href=\"/admin/page/ListPages\">Pages</a>\n";
    //$retVal .= ":<a href=\"/admin/page/CreatePage\">Create Page</a>\n";
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
        case "CreatePage":
        case "create":
            $this->create();
            break;

        case "EditPage":
            $this->edit($parms);
            break;

        case "ListPages":
        default:
            $this->listPages();
            break;
    }
}

/*
** listChunks - Lists all of the pages that are contained in the ACMS
*/

function listChunks()
{
    global $app, $ACMS_BASE_URI, $ACMSRewrite;
    $tpl = new acmsTemplate($this->templatePath . "list_pages.tpl");

    // Do our query and get our blocks.
    $sql = "select Chunks.ChunkID, Chunks.ChunkName, Chunks_page.LinkText, Chunks_page.Title, Chunks.Perms, Chunks.Active, Chunks_page.ShowPersistant, Chunks.Chunk from Chunks, Chunks_page  where Chunks.Handler = 'page' and Chunks_page.ChunkID = Chunks.ChunkID order by Chunks.ChunkName";
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - List Pages", "No Pages were found.", "", array('TitleNav' => $createLink));
        return;
    }

    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - List Pages", "No Pages were found.");
        return;
    }

    $pages = array();

    while ($curRow = mysql_fetch_array($result)) {
        // Create the URL to edit the chunk
        $editURL = "<a href=\"";
        if ($ACMSRewrite) {
            $editURL .= "/admin/page/EditPage/" . $curRow["ChunkID"];
        } else {
            $editURL .= "$ACMS_BASE_URI?page=admin/page/EditPage/" . $curRow["ChunkID"];
        }
        $editURL .= "\">" . $curRow["ChunkName"] . "</a>";

        array_push($pages, array(
                    "ChunkID"           => $curRow["ChunkID"],
                    "ChunkName"         => $editURL,
                    "LinkText"          => $curRow["LinkText"],
                    "Title"             => $curRow["Title"],
                    "Perms"             => $curRow["Perms"],
                    "Active"            => $curRow["Active"],
                    "ShowPersistant"    => $curRow["ShowPersistant"],
                    "Chunk"             => $curRow["Chunk"]
                    ));
    }
    $tpl->assign("Pages", $pages);
    return $tpl->get();
}

/*
** listPages - Lists the pages in its own block.
*/

function listPages()
{
    global $app;
    $app->addBlock(10, CONTENT_ZONE, "Admin - List Pages", $this->listChunks());
}


/*
** edit - Handles editing of the specified Page ID.
*/

function edit($ChunkID)
{
    global $app;

    // Check our request variables to see if we need to load variables.
    if (!isset($_REQUEST['Action'])) {
        if (!$this->loadPage($ChunkID)) return;
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

    $tpl = new acmsTemplate($this->templatePath . "page_edit_form.tpl");

    // Get the list of blocks that are on this page.
    // Track the blocks that are already on the page so we can provide
    // them with a drop down list of blocks to add.
    $incBlocks = array();
    $bList = "";
    // Get the list of blocks on the page.
    $sql = "select PageBlocks.PageBlockID, Chunks.ChunkID, Chunks.ChunkName, Chunks_block.Persistant, Chunks_block.Zone, Chunks.Weight, Chunks.Active, Chunks.Perms from PageBlocks, Chunks, Chunks_block where PageBlocks.PageChunkID = " . $this->ChunkID . " and Chunks.ChunkID = PageBlocks.BlockChunkID and Chunks_block.ChunkID = Chunks.ChunkID order by Chunks.ChunkName";
    $result = mysql_query($sql, $app->acmsDB);
    $blockList = array();
    if ($result) {
        if (mysql_num_rows($result)) {
            while ($block = mysql_fetch_array($result)) {
                if (strlen($bList)) $bList .= ",";
                $bList .= "'" . mysql_escape_string($block["ChunkName"]) . "'";
                array_push($blockList, array(
                            "BlockID"           => $block["ChunkID"],
                            "BlockName"         => $block["ChunkName"],
                            "BlockPersistant"   => $block["Persistant"],
                            "BlockZone"         => $block["Zone"],
                            "BlockWeight"       => $block["Weight"],
                            "BlockPerms"        => $block["Perms"]
                            ));
            }
        }
    }
    $tpl->assign("Blocks", $blockList);

    // Create the options for the add block list.
    if (strlen($bList)) {
        $bList = " and ChunkName not in ($bList) ";
    }
    $sql = "select ChunkName from Chunks where Handler = 'block' $bList order by ChunkName";
    $bSel = "";
    $result = mysql_query($sql, $app->acmsDB);
    if ($result) {
        if (mysql_num_rows($result)) {
            while($blocks = mysql_fetch_array($result)) {
                $bSel .= "<option>" . $blocks["ChunkName"] . "</option>";
            }
        }
    }
    
    $tpl->assign("Action",         "edit");
    $tpl->assign("BlockList",      $bSel);
    $tpl->assign("ChunkID",        $this->ChunkID);
    $tpl->assign("ChunkName",      $this->ChunkName);
    $tpl->assign("LinkText",       $this->LinkText);
    $tpl->assign("Title",          $this->Title);
    $tpl->assign("Perms",          $this->Perms);
    $tmpAct = "";
    if ($this->Active) $tmpAct = "checked";
    $tpl->assign("Active",         $tmpAct);
    $tmpPers = "";
    if ($this->ShowPersistant) $tmpPers = "checked";
    $tpl->assign("ShowPersistant", $tmpPers);
    $tpl->assign("Chunk",          $this->Chunk);
    $tpl->assign("ChunkArea",      getChunkFormField($this->Chunk));

    $app->addBlock(10, CONTENT_ZONE, "Edit Page", $tpl->get());
}

/*
** loadPage - Loads the specified page into our class variables.
*/

function loadPage($ChunkID)
{
    global $app;

    $cc  = new chunkClass();
    if (!$cc->fetchID($ChunkID, "page")) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Page", "Unable to load the specified page.");
        return false;
    }

    $this->ChunkID          = $cc->get("ChunkID");
    $this->ChunkName        = $cc->get("ChunkName");
    $this->LinkText         = $cc->get("LinkText");
    $this->Title            = $cc->get("Title");
    $this->Perms            = $cc->get("Perms");
    $this->Active           = $cc->get("Active");
    $this->ShowPersistant   = $cc->get("ShowPersistant");
    $this->Chunk            = preg_replace("/<\/form>/si", "&lt;form&gt", $cc->get("Chunk"));
    $this->Chunk            = preg_replace("/<\/textarea>/si", "&lt;textarea&gt", $cc->get("Chunk"));

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
    $this->LinkText         = stripslashes($_REQUEST["LinkText"]);
    $this->Title            = stripslashes($_REQUEST["Title"]);
    $this->Chunk            = stripslashes($_REQUEST["Chunk"]);
    $this->Perms            = $_REQUEST["Perms"];
    if (isset($_REQUEST["Active"])) $this->Active = 1;
    else $this->Active = 0;
    if (isset($_REQUEST["ShowPersistant"])) $this->ShowPersistant= 1;
    else $this->ShowPersistant = 0;
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
        $this->refreshToListPages();
        // We shouldn't return from listPages, but just in case....
        exit;
    }

    // Check to see if there was another action, such as Add.

    if (isset($_REQUEST["Add"])) {
        $this->addPageBlock();
        $this->showEditForm();
        return;
    }
    
    if (isset($_REQUEST["Remove"])) {
        $this->removePageBlock();
        $this->showEditForm();
        return;
    }

    if (isset($_REQUEST["Preview"])) {
        $app->addBlock(5, CONTENT_ZONE, "Preview - " . $this->Title, $app->parseChunk($this->Chunk));
        $this->showEditForm();
        return;
    }
    
    // Okay, if we made it here, they must want to save.
    // Our form variables have already been loaded, so verify that we have
    // a Chunk ID.  If we don't, then its an insert.  Otherwise, make sure
    // there is no name collision.

    $errText = "";

    // We'll do some basic validation first.
    if (!strlen($this->ChunkName)) {
        $errText .= "<li>All pages must have a unique name</li>";
    }

    if (!strlen($this->LinkText)) {
        $errText .= "<li>All pages must have some text for including in link references</li>";
    }

    if (!strlen($this->Title)) {
        $errText .= "<li>All pages must have a title of some sort</li>";
    }

    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the page.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Page - Error", $errText);
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
            $app->addBlock(0, CONTENT_ZONE, "1 Save Page - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        if (mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Page - Error", "A page already exists in the database with the name '" . $this->ChunkName . "'.  Page/chunk names must be unique.");
            $this->showEditForm();
            return;
        }

        // If we made it here, we're good to replace the database entry.
        $cc = new chunkClass();
        $cc->fetchID($this->ChunkID);
        $cc->set("ChunkName",        $this->ChunkName);
        $cc->set("LinkText",         $this->LinkText);
        $cc->set("Title",            $this->Title);
        $cc->set("Active",           $this->Active);
        $cc->set("Perms",            $this->Perms);
        $cc->set("ShowPersistant",   $this->ShowPersistant);
        $cc->set("Chunk",            $this->Chunk);

        if (!$cc->update()) {
            $app->addBlock(0, CONTENT_ZONE, "Save Page - Error", "2 Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        // We saved, return to the page list.
        $this->refreshToListPages();

    }
    
    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the page.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Page - Error", $errText);
        $this->showEditForm();
        return;
    }
    

}

/*
** addPageBlock - Obtains the block that the user selected from the list
**                of available blocks and adds it to this page.
*/

function addPageBlock()
{
    global $app;

    if (!isset($_REQUEST["BlockList"])) {
        // The user didn't select a block, return.
        return;
    }

    // Get the BlockID from the name of the block that the user specified.
    $sql = "select ChunkID from Chunks where Handler = 'block' and ChunkName = '" . mysql_escape_string($_REQUEST["BlockList"]) . "'";
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Edit Page - Add Block", "Error accessing the database to add the requested block.  Try again later.");
        return;
    }

    if (mysql_num_rows($result) != 1) {
        $app->addBlock(0, CONTENT_ZONE, "Edit Page - Add Block", "Unable to locate the block with the name '" . $_REQUEST["BlockList"] . "'");
        return;
    }

    // If we made it here, we have a single row that contains
    // our block ID.
    $block = mysql_fetch_array($result);

    $blockID = $block["ChunkID"];

    // Make sure our page ID is good.
    $sql = "select ChunkID from Chunks where Handler = 'page' and ChunkID = " . mysql_escape_string($this->ChunkID);
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Add Page Block", "Unable to load the specified page.");
        return;
    }

    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Add Page Block", "Unable to load the specified page.");
        return;
    }

    // Finally, make sure that the block isn't already on the page.
    $sql = "select PageBlockID from PageBlocks where PageChunkID = " . $this->ChunkID . " and BlockChunkID = $blockID";
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Add Page Block", "Unable to load the specified page.");
        return;
    }

    if (mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Add Page Block", "The specified block is already present on this page.");
        return;
    }


    // Now, create our query
    // Okay, our page exists, and our block ID is good.   Add it.
    $sql = "insert into PageBlocks (PageBlockID, PageChunkID, BlockChunkID) values(0, " . $this->ChunkID . ", $blockID)";
    mysql_query($sql, $app->acmsDB);

    // Simply return.  If there was an error, the page won't be added.
}

/*
** removePageBlock - Removes the selected block from the page.
*/

function removePageBlock()
{
    global $app;

    if (!isset($_REQUEST["BlockSel"])) {
        // The user didn't select a block, return.
        return;
    }

    $sql = "select PageBlockID from PageBlocks where PageChunkID = " . $this->ChunkID . " and BlockChunkID = " . mysql_escape_string($_REQUEST["BlockSel"]);
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Remove Page Block", "Unable to load the specified page.");
        return;
    }

    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Remove Page Block", "That block was not found on this page.");
        return;
    }

    $sql = "delete from PageBlocks where PageChunkID = " . $this->ChunkID . " and BlockChunkID = " . mysql_escape_string($_REQUEST["BlockSel"]);
    mysql_query($sql, $app->acmsDB);

    // Simply return.  If there was an error, the page won't be removed
}

/*
** listPages - Uses a location tag to return the user to the ListPages
**              page.
*/

function refreshToListPages()
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
                $this->showCreateForm();
                break;
        }
    } else {
        // No action set, just show them the form.
        $this->showCreateForm();
    }
}

/*
** showCreateForm - Displays the create form, filling in any variables that
**                  we need.
*/

function showCreateForm()
{
    global $app;

    $tpl = new acmsTemplate($this->templatePath . "page_create_form.tpl");

    $tpl->assign("Action",         "create");
    $tpl->assign("ChunkName",      $this->ChunkName);
    $tpl->assign("LinkText",       $this->LinkText);
    $tpl->assign("Title",          $this->Title);
    $tpl->assign("Perms",          $this->Perms);
    $tmpAct = "";
    if ($this->Active) $tmpAct = "checked";
    $tpl->assign("Active",         $tmpAct);
    $tmpPers = "";
    if ($this->ShowPersistant) $tmpPers = "checked";
    $tpl->assign("ShowPersistant", $tmpPers);
    $tpl->assign("Chunk",          $this->Chunk);
    $tpl->assign("ChunkArea",      getChunkFormField($this->Chunk));
    $app->writeLog("Form field = '" . getChunkFormField($this->Chunk) . "'");

    $app->addBlock(10, CONTENT_ZONE, "Create New Page", $tpl->get());
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
        $this->refreshToListPages();
        // We shouldn't return from listPages, but just in case....
        exit;
    }

    if (isset($_REQUEST["Preview"])) {
        $app->addBlock(5, CONTENT_ZONE, "Preview - " . $this->Title, $app->parseChunk($this->Chunk));
        $this->showCreateForm();
        return;
    }

    // Our form variables have already been loaded, so verify that we have
    // a Chunk ID.  If we don't, then its an insert.  Otherwise, make sure
    // there is no name collision.

    $errText = "";

    // We'll do some basic validation first.
    if (!strlen($this->ChunkName)) {
        $errText .= "<li>All pages must have a unique name</li>";
    }

    if (!strlen($this->LinkText)) {
        $errText .= "<li>All pages must have some text for including in link references</li>";
    }

    if (!strlen($this->Title)) {
        $errText .= "<li>All pages must have a title of some sort</li>";
    }

    // Make sure we don't have a chunk with this name already.
    $sql = "select ChunkID from Chunks where ChunkName = '" . mysql_escape_string($this->ChunkName) . "' and ChunkID <> " . mysql_escape_string($this->ChunkID);
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $errText .= "<li>Error while communicationg with the database.  Please try again later.";
    } else if (mysql_num_rows($result)) {
        $errText .= "<li>A page already exists in the database with the name '" . $this->ChunkName . "'.  Page/chunk names must be unique.";
    }

    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the page.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Page - Error", $errText);
        $this->showCreateForm();
        return;
    }

    $cc = new chunkClass("page");
    $cc->set("ChunkName",        $this->ChunkName);
    $cc->set("LinkText",         $this->LinkText);
    $cc->set("Title",            $this->Title);
    $cc->set("Active",           $this->Active);
    $cc->set("Perms",            $this->Perms);
    $cc->set("ShowPersistant",   $this->ShowPersistant);
    $cc->set("Chunk",            $this->Chunk);
    if (!$cc->insert()) {
        $app->addBlock(0, CONTENT_ZONE, "Save Page - Error", "Error while communicationg with the database.  Please try again later.");
        $this->showEditForm();
        return;
    }

    // Okay, we saved, now take them to the page editor so they can finish
    // adding blocks and stuff to this page.
    // Get the new Chunk ID
    $sql = "select ChunkID from Chunks where ChunkName = '" . mysql_escape_string($this->ChunkName) . "'";
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Save Page - Error", "Error while communicationg with the database.  Please try again later.");
        $this->showEditForm();
        return;
    }
    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Save Page - Error", "Error while communicationg with the database.  Please try again later.");
        $this->showEditForm();
        return;
    }

    $chunk = mysql_fetch_array($result);

    // FIXME: Links shouldn't be created like this.
    ob_end_clean();
    header("Location: /admin/page/EditPage/" . $chunk["ChunkID"]);
    exit;
}

};  // admplugin_page class


?>
