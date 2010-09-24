<?php
/**
 * admplug_image.php - Image handler admin plugin.
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
if (eregi('admplug_image.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global variables we use to load ourselves into.
global $admplugins;

$admplugins['image'] = new admplugin_image();

// The admplugin_image class.
class admplugin_image {

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
var $Filename;
var $MimeType;
var $Chunk;
var $Perms;
var $StoreInDB;

/*
** admplugin_image - Constructor.  This handles the setup of the various admin
**                   functions of this plugin.
*/

function admplugin_image()
{
    // Set our name.
    $this->pluginName       = "image";
    $this->handler          = "image";

    // Get the template file name.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts["dirname"] . "/admplug_image_";
    // Clear our globals
    $this->ChunkID          = "";
    $this->ChunkName        = "";
    $this->Filename         = "";
    $this->MimeType         = "";
    $this->Perms            = 644;
    $this->StoreInDB        = 1;

} // admplugin_image constructor

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

        case "EditImage":
            $this->edit($parms);
            break;

        case "ListImages":
        default:
            $this->listImages();
            break;
    }
}

/*
** listChunks - Lists all of the images that are contained in the ACMS
*/

function listChunks()
{
    global $app, $ACMS_BASE_URI, $ACMSRewrite;
    $tpl = new acmsTemplate($this->templatePath . "listimages.tpl");

    // Do our query and get our blocks.
    $sql = "select Chunks.ChunkID, Chunks.ChunkName, Chunks.Perms, Chunks.Active, Chunks_image.Filename from Chunks, Chunks_image where Chunks.Handler = 'image' and Chunks.ChunkID = Chunks_image.ChunkID order by ChunkName";
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - List Images", "No Images were found.");
        return;
    }

    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - List Images", "No Images were found.");
        return;
    }

    $images = array();
    while ($curRow = mysql_fetch_array($result)) {
        // Create the URL to edit the chunk
        $editURL = "<a href=\"";
        if ($ACMSRewrite) {
            $editURL .= "/admin/image/EditImage/" . $curRow["ChunkID"];
        } else {
            $editURL .= "$ACMS_BASE_URI?page=admin/image/EditImage/" . $curRow["ChunkID"];
        }
        $editURL .= "\">" . $curRow["ChunkName"] . "</a>";

        // Create the delete URL
        $delURL = "<a href=\"";
        if ($ACMSRewrite) {
            $delURL .= "/admin/DelChunk/" . $curRow["ChunkID"];
        } else {
            $delURL .= "$ACMS_BASE_URI?page=admin/DelChunk/" . $curRow["ChunkID"];
        }
        $delURL .= "\"";
        $delURL .= " onClick=\"return confirm('There is no way to undo this action.\\nAnd there is no reference checking yet.\\nAre you sure you want to delete the chunk \\'" . $curRow["ChunkName"] . "\\'?')\"";
        $delURL .= ">Delete</a>";
        array_push($images, array(
                    "ChunkID"   => $curRow["ChunkID"],
                    "ChunkName" => $editURL,
                    "Filename"  => $curRow["Filename"],
                    "Perms"     => $curRow["Perms"],
                    "DelURL"    => $delURL
                    ));
    }
    $tpl->assign("Images", $images);
    return $tpl->get();
}

/*
** listImages - Displays all of the images in its own listing.
*/

function listImages()
{
    global $app;
    $app->addBlock(10, CONTENT_ZONE, "Admin - List Images", $this->listChunks());
}

/*
** edit - Handles editing of the specified image based on its ChunkID
*/

function edit($ChunkID)
{
    global $app;

    // Check our request variables to see if we need to load variables.
    if (!isset($_REQUEST['Action'])) {
        if (!$this->loadImage($ChunkID)) return;
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
    $tpl->assign("Filename",       $this->Filename);
    $tpl->assign("MimeType",       $this->MimeType);
    $tpl->assign("Perms",          $this->Perms);
    if ($this->StoreInDB) {
        $tpl->assign("StoreInDBChecked",   "checked");
    } else {
        $tpl->assign("StoreInDBChecked",   "");
    }
    // If we are editing, show them the current image.
    if ($this->ChunkID) {
        $tpl->assign("Chunk",      $app->parseChunk("^^{{" . $this->ChunkName . "}}^^"));
    } else {
        $tpl->assign("Chunk",      "");
    }

    $app->addBlock(10, CONTENT_ZONE, "Edit Image", $tpl->get("image_edit_form"));
}

/*
** loadImage - Loads the specified image into our class variables.
*/

function loadImage($ChunkID)
{
    global $app;

    $cc = new chunkClass("image");
    if (!$cc->fetchID($ChunkID)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Image", "Unable to load the specified image.");
        return false;
    }

    $this->ChunkID          = $cc->get("ChunkID");
    $this->ChunkName        = $cc->get("ChunkName");
    $this->Filename         = $cc->get("Filename");
    $this->MimeType         = $cc->get("MimeType");
    $this->Perms            = $cc->get("Perms");
    $this->StoreInDB        = $cc->get("StoreInDB");

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
    $this->Filename         = stripslashes($_REQUEST["Filename"]);
    $this->MimeType         = stripslashes($_REQUEST["MimeType"]);
    $this->Perms            = $_REQUEST["Perms"];
    if (isset($_REQUEST["StoreInDB"]) && strlen($_REQUEST["StoreInDB"])) {
        $this->StoreInDB = 1;
    } else {
        $this->StoreInDB = 0;
    }
}

/*
** processEdit - The user hit either Save or Cancel on the edit form.
**               this function does the appropriate thing with that.
*/

function processEdit()
{
    global $app, $siteConfig;

    if (isset($_REQUEST["Cancel"]) && strlen($_REQUEST["Cancel"])) {
        // The user hit cancel, abort
        $this->refreshToListImages();
        // We shouldn't return from listPages, but just in case....
        exit;
    }

    // Check to see if there was another action, such as Add.
    
    // Okay, if we made it here, they must want to save.
    // Our form variables have already been loaded, so verify that we have
    // a Chunk ID.  If we don't, then its an insert.  Otherwise, make sure
    // there is no name collision.

    $errText = "";

    $chunk = "";
    $width = 0;
    $height = 0;

    // Get the upload file.
    if (!is_uploaded_file($_FILES['UploadFile']['tmp_name'])) {
        if (!$this->ChunkID) {
            $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", "You must provide an upload file.");
            $this->showEditForm();
            return;
        }
    } else {
        // Load the entire file into memory and base 64 encode it.
        $fname = $_FILES['UploadFile']['tmp_name'];
        $ufp = fopen($fname, "rb");
        $data = fread($ufp, filesize($fname));
        $chunk = chunk_split(base64_encode($data));
        fclose($ufp);
        list($width, $height, $type, $attr) = getimagesize($fname);
        // Check to see if we're going to copy it into the docroot
        if (!$this->StoreInDB) {
            $dname  = $_SERVER["DOCUMENT_ROOT"] . $siteConfig['ImagePath'];
            $dname .= "/" . $_FILES['UploadFile']['name'];
            if (move_uploaded_file($_FILES['UploadFile']['tmp_name'], $dname)) {
                $chunk = "";
            } else {
                $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", "Error moving '" . $_FILES['UploadFile']['tmp_name'] . "' to '$dname'.");
                $this->showEditForm();
                return;
            }
        }
    }

    if (!strlen($this->Filename)) {
        if (isset($_FILES['UploadFile']['name'])) {
            $this->Filename = $_FILES['UploadFile']['name'];
        } else {
            $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", "No filename was specified.  A filename must be included for some browsers to display images correctly.");
            $this->showEditForm();
            return;
        }
    }
    
    if (!strlen($this->MimeType)) {
        if (isset($_FILES['UploadFile']['type'])) {
            $this->MimeType = $_FILES['UploadFile']['type'];
        } else {
            $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", "No mime type was specified.  A mime type must be included for some browsers to display images correctly.");
            $this->showEditForm();
            return;
        }
    }

    if (!strlen($this->ChunkName)) {
        if (isset($_FILES['UploadFile']['name'])) {
            $parts = pathinfo($_FILES['UploadFile']['name']);
            $tmpnam = substr($parts["basename"], 0, strlen($parts["basename"]) - strlen($parts["extension"])-1);
            $this->ChunkName = $tmpnam;
        } else {
            $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", "No mime type was specified.  A mime type must be included for some browsers to display images correctly.");
            $this->showEditForm();
            return;
        }
    }

    // We'll do some basic validation first.
    if (!strlen($this->ChunkName)) {
        $errText .= "<li>All images must have a unique name</li>";
    }

    // If they are selecting to not save the image in the database, make
    // sure that the image exists and is readable.
    if (!$this->StoreInDB) {
        $dname  = $_SERVER["DOCUMENT_ROOT"] . $siteConfig['ImagePath'];
        $dname .= $_FILES['UploadFile']['name'];
        if (!file_exists($dname) || !is_readable($dname)) {
            $errText = "<li>Unable to read file '$dname'.  A file is required if not storing the image in the database.</li>";
        } else {
            // Remove the chunk from the database to speed up the queries.
            $chunk = "";
        }
    }


    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the image.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", $errText);
        $this->showEditForm();
        return;
    }
    
    if (!$this->ChunkID) {
        // Okay, we're doing an insert.  We just need to check that
        // there are no chunks with this name already.
        $sql = "select ChunkID from Chunks where ChunkName = '" . mysql_escape_string($this->ChunkName) . "'";
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        if (mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", "A chunk already exists in the database with the name '" . $this->ChunkName . "'.  Chunk names must be unique.");
            $this->showEditForm();
            return;
        }

        // Setup our insert
        $cc = new chunkClass("image");
        $cc->set("ChunkName",   $this->ChunkName);
        $cc->set("Handler",     "image");
        $cc->set("Chunk",       $chunk);
        $cc->set("Perms",       $this->Perms);
        $cc->set("Filename",    $this->Filename);
        $cc->set("MimeType",    $this->MimeType);
        $cc->set("Width",       $width);
        $cc->set("Height",      $height);
        $cc->set("StoreInDB",   $this->StoreInDB);
        if (!$cc->insert(false)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        // We saved, return to the page list.
        $this->refreshToListImages();

    } else {
        // Make sure we don't have a image with this name already.
        $sql = "select ChunkID from Chunks where ChunkName = '" . mysql_escape_string($this->ChunkName) . "' and ChunkID <> " . mysql_escape_string($this->ChunkID);
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        if (mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", "A chunk already exists in the database with the name '" . $this->ChunkName . "'.  Chunk names must be unique.");
            $this->showEditForm();
            return;
        }

        // Setup our update.
        $cc = new chunkClass("image");
        $cc->fetchID($this->ChunkID);
        $cc->set("ChunkName",   $this->ChunkName);
        $cc->set("Handler",     "image");
        $cc->set("Chunk",       $chunk);
        $cc->set("Width",       $width);
        $cc->set("Height",      $height);
        $cc->set("Perms",       $this->Perms);
        $cc->set("Filename",    $this->Filename);
        $cc->set("MimeType",    $this->MimeType);
        $cc->set("StoreInDB",   $this->StoreInDB);
        if (!$cc->update(false)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showEditForm();
            return;
        }
        // We saved, return to the page list.
        $this->refreshToListImages();

    }
    
    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the image.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Image - Error", $errText);
        $this->showEditForm();
        return;
    }
}

/*
** refreshToListImages - Uses a location tag to return the user to the 
**                       ListImages page.
*/

function refreshToListImages()
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



};  // admplugin_image class


?>
