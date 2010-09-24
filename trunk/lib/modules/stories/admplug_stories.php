<?php
/**
 * admplug_stories.php - Story editor plugin for the admin module.
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
if (eregi('admplug_stories.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global variables we use to load ourselves into.
global $admplugins;

$admplugins['stories'] = new admplugin_stories();

// The admplugin_stories class.
class admplugin_stories{

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
var $Active;
var $Submitter;
var $Approved;
var $PostDate;
var $ExpireDate;
var $Categories;

/*
** admplugin_stories - Constructor.  This handles the setup of the various
**                     admin functions of this plugin.
*/

function admplugin_stories()
{
    // Set our name.
    $this->pluginName       = "stories";
    $this->handler          = "story";

    // Get the template file name.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts["dirname"] . "/stories_admplug_";
    // Clear our globals
    $this->ChunkID          = "";
    $this->ChunkName        = "Story" . date("ymd") . "01";
    $this->Perms            = 644;
    $this->Active           = 1;
    $this->Chunk            = "";
    $this->Title            = "";
    $this->Submitter        = "";
    $this->Approved         = 1;
    $this->PostDate         = date("Y-m-d H:i:s");
    $this->ExpireDate       = "9999-12-31 23:59:59";
    $this->Categories       = array();

} // admplugin_storiess constructor

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
        case "create":
            $this->create();
            break;

        default:
            $this->listChunks();
            break;
    }
}

/*
** listChunks - Lists all of the stories that are contained in the ACMS
*/

function listChunks()
{
    global $app, $ACMS_BASE_URI, $ACMSRewrite;
    $tpl = new acmsTemplate($this->templatePath . "list.tpl");
    $stories = array();

    // Do our query and get our blocks.
    $sql = "select Chunks.ChunkID, Chunks.ChunkName, Chunks.Chunk, Chunks.Perms, Chunks.Active, Chunks_story.Title, Chunks_story.PostDate from Chunks, Chunks_story where Chunks.Handler = 'story' and Chunks.ChunkID = Chunks_story.ChunkID order by Chunks.ChunkName";
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - List Stories", "No stories were found.");
        return;
    }

    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - List Stories", "No stories were found.");
        return;
    }

    while ($curRow = mysql_fetch_array($result)) {
        // Create the URL to edit the chunk
        $editURL = "<a href=\"";
        if ($ACMSRewrite) {
            $editURL .= "/admin/EditChunk/" . $curRow["ChunkID"];
        } else {
            $editURL .= "$ACMS_BASE_URI?page=admin/EditChunk/" . $curRow["ChunkID"];
        }
        $editURL .= "\">" . $curRow["ChunkName"] . "</a>";

        array_push($stories, array(
                    "ChunkID"   => $curRow["ChunkID"],
                    "ChunkName" => $editURL,
                    "Title"     => $curRow["Title"],
                    "Perms"     => $curRow["Perms"],
                    "PostDate"  => $curRow["PostDate"]
                    ));
    }
    $tpl->assign("Stories", $stories);
    return $tpl->get();
}


/*
** edit - Handles editing of the specified story based on its ChunkID
*/

function edit($ChunkID)
{
    global $app;

    // Check our request variables to see if we need to load variables.
    if (!isset($_REQUEST['Action'])) {
        if (!$this->loadStory($ChunkID)) return;
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

    // Create the selection list for the categories.
    $categories = $app->getCategoryList();
    //echo "<pre>"; print_r($categories); echo "</pre>";
    $catSel = "";
    foreach($categories as $cat) {
        $isSel = "";
        if ($this->ChunkID) {
            foreach($this->Categories as $tmpcat) {
                if ($tmpcat == $cat["CategoryID"]) {
                    $isSel .= "selected";
                }
            }
        }
        $catSel .= "<option value=\"" . $cat["CategoryID"] . "\" $isSel>";
        if ($cat["Level"] > 0) {
            for($i = 0; $i < $cat["Level"]; $i++) {
                $catSel .= "&nbsp;";
            }
        }
        $catSel .= $cat["Title"];
        $catSel .= "</option>\n";
    }


    $tpl = new acmsTemplate($this->templatePath . "edit_form.tpl");
    $tpl->assign("Action",         "edit");
    $tpl->assign("ChunkID",        $this->ChunkID);
    $tpl->assign("ChunkName",      $this->ChunkName);
    $tpl->assign("Chunk",          $this->Chunk);
    $tpl->assign("ChunkArea",      getChunkFormField($this->Chunk));
    $tpl->assign("Title",          $this->Title);
    $tpl->assign("Perms",          $this->Perms);
    $tpl->assign("PostDate",       $this->PostDate);
    if (!strcmp("9999-12-31 23:59:59", $this->ExpireDate)) {
        $tpl->assign("ExpireDate",     "Never");
    } else {
        $tpl->assign("ExpireDate",     $this->ExpireDate);
    }
    $tpl->assign("Categories",     $catSel);

    $app->addBlock(10, CONTENT_ZONE, "Edit Story", $tpl->get());
}

/*
** loadStory - Loads the specified story into our class variables.
*/

function loadStory($ChunkID)
{
    global $app;

    $cc = new chunkClass("story");

    if (!$cc->fetchID($ChunkID)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Story", "Unable to load the specified story.");
        return false;
    }

    $this->ChunkID          = $cc->get("ChunkID");
    $this->ChunkName        = $cc->get("ChunkName");
    $this->Title            = $cc->get("Title");
    $this->Chunk            = $cc->get("Chunk");
    $this->Perms            = $cc->get("Perms");
    $this->PostDate         = $cc->get("PostDate");
    $this->ExpireDate       = $cc->get("ExpireDate");
    $this->Categories       = $cc->getCategories();

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

    // Extract the categories.
    $this->Categories = array();
    foreach($_REQUEST["Categories"] as $tmpCat) {
        array_push($this->Categories, $tmpCat);
    }


    // Make the dates free-form by using strtotime
    $this->PostDate         = date("Y-m-d H:i:s", strtotime($_REQUEST['PostDate']));
    // Expire date is special.  If it is empty or "0", then 
    // We never expire.
    $tmpExp = stripslashes($_REQUEST["ExpireDate"]);
    if (!strcmp("0", $tmpExp) || !strcasecmp("never", $tmpExp) || !strlen($tmpExp)) {
        $this->ExpireDate       = "9999-12-31 23:59:59";
    } else {
        $this->ExpireDate       = date("Y-m-d H:i:s", strtotime($_REQUEST['ExpireDate']));
    }
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
        $this->refreshToListChunks();
        // We shouldn't return from listPages, but just in case....
        exit;
    }

    // Check to see if there was another action, such as Add.
    if (isset($_REQUEST["Preview"]) && strlen($_REQUEST["Preview"])) {
        // Preview the data
        $app->addBlock(0, CONTENT_ZONE, "Story Preview - " . $this->Title, $app->parseChunk(str_replace("@@page@@", "^^::''New Page''::^^", $this->Chunk)));
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
        $errText .= "<li>All stories must have a unique name</li>";
    }

    if (!strlen($this->Chunk)) {
        $errText .= "<li>All stories must have a headline.</li>";
    }

    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the story.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Story - Error", $errText);
        $this->showEditForm();
        return;
    }

    /*
    echo "<pre>"; print_r($_REQUEST); echo "</pre>";
    echo "<pre>"; print_r($this->Categories); echo "</pre>";
    $this->showEditForm();
    return;
    */
    
    if (!$this->ChunkID) {
        // Okay, we're doing an insert.  We just need to check that
        // there are no chunks with this name already.
        $sql = "select ChunkID from Chunks where ChunkName = '" . mysql_escape_string($this->ChunkName) . "'";
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Story - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        if (mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Story - Error", "A chunk already exists in the database with the name '" . $this->ChunkName . "'.  Chunk names must be unique.");
            $this->showEditForm();
            return;
        }
        
        // Use the chunkClass to insert the story
        $cc = new chunkClass("story");
        $cc->set("ChunkName",   $this->ChunkName);
        $cc->set("Title",       $this->Title);
        $cc->set("Chunk",       $this->Chunk);
        $cc->set("Perms",       $this->Perms);
        $cc->set("PostDate",    $this->PostDate);
        $cc->set("ExpireDate",  $this->ExpireDate);
        if (!$cc->insert()) {
            $app->addBlock(0, CONTENT_ZONE, "Save Story - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        $cc->categorize($this->Categories);
        // We saved, return to the page list.
        $this->refreshToListChunks();

    } else {
        // Make sure we don't have a story with this name already.
        $sql = "select ChunkID from Chunks where ChunkName = '" . mysql_escape_string($this->ChunkName) . "' and ChunkID <> " . mysql_escape_string($this->ChunkID);
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Story - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        if (mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Story - Error", "A chunk already exists in the database with the name '" . $this->ChunkName . "'.  Chunk names must be unique.");
            $this->showEditForm();
            return;
        }

        $cc = new chunkClass("story");
        $cc->fetchID($this->ChunkID);
        $cc->set("ChunkName",   $this->ChunkName);
        $cc->set("Title",       $this->Title);
        $cc->set("Chunk",       $this->Chunk);
        $cc->set("Perms",       $this->Perms);
        $cc->set("PostDate",    $this->PostDate);
        $cc->set("ExpireDate",  $this->ExpireDate);
        if (!$cc->update()) {
            $app->addBlock(0, CONTENT_ZONE, "Save Story - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        $cc->categorize($this->Categories);
        // We saved, return to the page list.
        $this->refreshToListChunks();

    }
    
    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the story.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Story - Error", $errText);
        $this->showEditForm();
        return;
    }
}

/*
** refreshToListChunks - Uses a location tag to return the user to the 
**                       admin module's chunk list page.
*/

function refreshToListChunks()
{
    global $app;
    ob_end_clean();
    $retURI = "/admin/ListChunks/story";
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



};  // admplugin_stories class


?>
