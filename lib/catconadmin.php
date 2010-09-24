<?php
/**
 * catconadmin.php - Categorized Content module administration hooks.
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
if (eregi('catconadmin.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

require_once("chunklib.php");
require_once("acmsmodule.php");

// The global variables we use to load ourselves into.
global $admplugins;

//$admplugins['catcon'] = new admplugin_stories();

// The admplugin_stories class.
class admplugin_catcon extends acmsModule {

// This needs to be set so the admin module knows who we are.
var $pluginName;
// This tells the admin panel what handler we will also work for.
var $handler;

// Class Variables
var $templatePath;
var $modName;
var $items;
var $db;
var $catConID;
var $showList = 1;

/*
** admplugin_catcon - Constructor.  This handles the setup of the various
**                    admin functions of this plugin.
*/

function admplugin_catcon($mod)
{
    global $app;
    $this->acmsModule();
    // Set our name.
    $this->pluginName       = "catcon";
    $this->handler          = "";

    $this->modName = $mod;

    // Get the template file name.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $app->templatePath . "/catconadm_";

    $this->items = array();
    $this->db = $app->newDBConnection();
    $this->catConID = -1;
} // admplugin_catcon constructor

/*
** menu - Returns our menu.
*/

function menu($expand) 
{
    $retVal ="";
    $retVal .= "<a href=\"/admin/" . $this->pluginName . "\">CatCon: " . $this->pluginName . "</a>";
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
    
    if (isset($app->request["act"])) {
        // Make sure that for every action we have a catconid set.
        $sql = "select * from catcon where CatConID = " . mysql_escape_string($app->request["catconid"]) . " and ModName = '" . $this->modName . "'";
        $result = mysql_query($sql, $this->db);
        if ($result && mysql_num_rows($result)) {
            $this->catConID = $app->request["catconid"];
        }

        if ($this->catConID >= 0) {
            switch ($app->request["act"]) {
                case "mvup":
                    $this->moveItemUp();
                    break;
                
                case "mvdn":
                    $this->moveItemDown();
                    break;

                case "edit":
                    $this->editItem();
                    break;

                case "add":
                    $this->addItem();
                    break;

                case "delete":
                    $this->deleteItem();
                    break;

                default:
                    break;
            }
        }
    }

    switch ($args) {
        case "create":
            $app->addBlock(10, CONTENT_ZONE, "CatCon: Default", "Create Boo!");
            break;

        default:
            if ($this->showList) $this->listItems();
            break;
    }
}

/** listItems - Lists all of the catcon items for the specified type.
  */
function listItems()
{
    global $app;
    $this->loadItems("/". $this->modName);
    //echo "<pre>"; print_r($this->items); echo "</pre>";
    $tpl = new acmsTemplate($this->templatePath . "list.tpl");
    $tpl->assign("Items", $this->items);
    $app->addBlock(10, CONTENT_ZONE, "CatCon (" . $this->pluginName . ") Admin: List", $tpl->get());
}

/** loadItems - Loads all of the items for this module into memory.
  */
function loadItems($path, $parentID = 0, $level = 0, $parentIDX = 0)
{
    global $app;
    $retVal = 0;
    $sql = "select CatConID, ParentID, Tag, Label, TextBrief, TextChunk, TextFooter, TextList from catcon where ModName = '" . $this->modName . "' and ParentID = $parentID order by Level";
    $result = mysql_query($sql, $this->db);
    if ($result && mysql_num_rows($result)) {
        $retVal = mysql_num_rows($result);
        //$parent["childcount"] = $retVal;
        $tmpRay = array();
        while($curRow = mysql_fetch_array($result)) {
            array_push($tmpRay, $curRow);
        }
        foreach($tmpRay as $curRow) {
            $curRow["path"] = substr($path . "/" . $curRow["Tag"], strlen($this->modName) + 1);
            $curRow["level"]  = $level;
            $tmpNum = array_push($this->items, $curRow);
            $childCount = $this->loadItems($path . "/" . $curRow["Tag"], $curRow["CatConID"], $level + 1, $tmpNum-1);
            $this->items[$tmpNum-1]["childcount"] = $childCount;
        }
    }
    return $retVal;
}

/** moveItemUp - Lowers the priority of an item causing it to be moved
  *              up the list.
  */
function moveItemUp()
{
    global $app;
    $sql = "select Level, ParentID from catcon where CatConID = " . $this->catConID;
    $app->writeLog($sql);
    $result = mysql_query($sql, $this->db);
    if (!$result || !mysql_num_rows($result)) return;
    $curRow = mysql_fetch_array($result);
    $pri = $curRow['Level'] - 1;
    if ($pri && $curRow['ParentID']) $this->setItemPriority($this->catConID, $pri);
}

/** moveItemDown - Raises the priority of an item causing it to be moved
  *                down the list.
  */
function moveItemDown()
{
    global $app;
    $sql = "select Level, ParentID from catcon where CatConID = " . $this->catConID;
    $result = mysql_query($sql, $this->db);
    if (!$result || !mysql_num_rows($result)) return;
    $curRow = mysql_fetch_array($result);
    $pri = $curRow['Level'] + 1;
    if ($pri && $curRow['ParentID']) $this->setItemPriority($this->catConID, $pri);
}

/** setItemPriority - Sets the priority of an item.
  */
function setItemPriority($ccID, $newPri)
{
    global $app;
    $db = $app->newDBConnection();

    $maxPri = 1;
    $sql = "select Level, ParentID from catcon where CatConID = $ccID and ModName = '" . $this->modName . "'";
    $app->writeLog($sql);
    $result = mysql_query($sql, $db);
    if (!$result || !mysql_num_rows($result)) return;
    // Load the current priority.
    $curRow = mysql_fetch_array($result);

    // Check to see if the priority is the same, if it is, we can leave.
    $oldPri = $curRow["Level"];
    $parID  = $curRow["ParentID"];
    if ($oldPri == $newPri) return;
    if ($newPri < 1) return;
    if (!$parID) return;

    // Determine the max priority and make sure we're not going over it.
    $sql = "select Level from catcon where ModName = '" . $this->modName . "' and ParentID = $parID";
    $app->writeLog($sql);
    $result = mysql_query($sql, $db);
    if (!$result || !mysql_num_rows($result)) return;
    $maxPri = mysql_num_rows($result);
    if ($newPri > $maxPri) return;

    // Move the item we're working on out of the way.
    $sql = "update catcon set Level = " . $maxPri + 2 . " where CatConID = $ccID";
    $app->writeLog($sql);
    mysql_query($sql, $db);

    // Move the filters around
    if ($oldPri < $newPri) {
        $sql = "update catcon set Level = Level - 1 where ModName = '" . $this->modName . "' and ParentID = $parID and Level >= $oldPri and Level <= $newPri";
    $app->writeLog($sql);
    } else {
        $sql = "update catcon set Level = Level + 1 where ModName = '" . $this->modName . "' and ParentID = $parID and Level >= $newPri and Level <= $oldPri";
    $app->writeLog($sql);
    }
    mysql_query($sql, $db);

    // Put the entry back in place with the new priority.
    $sql = "update catcon set Level = $newPri where CatConID = $ccID";
    $app->writeLog($sql);
    mysql_query($sql, $db);
    
    // Thats it, the item has moved.
}

/** editItem - Handles editing an item.  It will also take care of saving 
  * the edit if the right variables are set.
  */
function editItem()
{
    global $app;
    if (isset($app->request["cancel"])) {
        header("Location: /admin/" . $this->modName);
        exit;
    }
    $rows = 8;
    $editDone = 0;
    $sql = "select * from catcon where CatConID = " . $this->catConID;
    $result = mysql_query($sql, $this->db);
    if (!$result || !mysql_num_rows($result)) return;

    if (isset($app->request["save"])) {
        // Validate the data.
        if ($this->validateFormData()) {
            // Save it.
            $sql = "";
            foreach (array("Tag", "Label", "TextBrief", "TextChunk", "TextFooter", "TextList") as $key) {
                if (strlen($sql)) $sql .= ", ";
                $sql .= $key . " = '" . mysql_escape_string($app->request[$key]) . "'";
            }
            $sql = "update catcon set " . $sql;
            $sql .= " where CatConID = " . $this->catConID;
            $result = mysql_query($sql, $this->db);
            $editDone = 1;
        }
    }

    // Check for an added or removed block
    if (isset($app->request["Add"])) {
        $this->addPageBlock();
    }
    
    if (isset($app->request["Remove"])) {
        $this->removePageBlock();
    }


    if (!$editDone) {
        $this->setupEditForm("editsave");
        $this->showList = 0;
    } else {
        header("Location: /admin/" . $this->modName);
        exit;
    }
}

/** addItem - Handles adding an item.  It will also take care of saving 
  * the new entry if the right variables are set.
  */
function addItem()
{
    global $app;
    if (isset($app->request["cancel"])) {
        header("Location: /admin/" . $this->modName);
        exit;
    }
    $rows = 8;
    $editDone = 0;
    $sql = "select * from catcon where CatConID = " . $this->catConID;
    $result = mysql_query($sql, $this->db);
    if (!$result || !mysql_num_rows($result)) return;

    if (isset($app->request["addsave"])) {
        // Validate the data.
        if ($this->validateFormData()) {
            // Save it.
            // Figure out what level they're on.
            $sql = "select CatConID from catcon where ModName = '" . $this->modName . "' and ParentID = " . $this->catConID;
            $result = mysql_query($sql, $this->db);
            $level = mysql_num_rows($result) + 1;
            $sql = "insert into catcon (CatConID, ModName, ParentID, Level, Tag, Label, TextBrief, TextChunk, TextFooter, TextList) values (";
            $sql .= "0, '" . $this->modName . "', " . $this->catConID . ", $level";
            foreach (array("Tag", "Label", "TextBrief", "TextChunk", "TextFooter", "TextList") as $key) {
                $sql .= ", ";
                $sql .= "'" . mysql_escape_string($app->request[$key]) . "'";
            }
            $sql .= ")";
            $app->writeLog($sql);
            $result = mysql_query($sql, $this->db);
            $editDone = 1;
        }
    }

    if (!$editDone) {
        $tmpID = $this->catConID;
        $this->catConID = 0;
        $this->setupEditForm("addsave");
        $this->catConID = $tmpID;
        $this->showList = 0;
    } else {
        header("Location: /admin/" . $this->modName);
        exit;
    }
}

/** setupEditForm - Sets up the add/edit form.
  */
function setupEditForm($saveTag)
{
    global $app;
    if (isset($app->request["cancel"])) {
        header("Location: /admin/" . $this->modName);
        exit;
    }
    $rows = 8;
    $tpl = new acmsTemplate($this->templatePath . "edit.tpl");

    // Get the list of blocks that are on this page.
    // Track the blocks that are already on the page so we can provide
    // them with a drop down list of blocks to add.
    $incBlocks = array();
    $bList = "";
    // Get the list of blocks on the page.
    $sql = "select catcon_blocks.CatConBlockID, catcon_blocks.IncludeChildren, Chunks.ChunkID, Chunks.ChunkName, Chunks_block.Persistant, Chunks_block.Zone, Chunks.Weight, Chunks.Active, Chunks.Perms from catcon_blocks, Chunks, Chunks_block where catcon_blocks.CatConID = " . $this->catConID . " and Chunks.ChunkID = catcon_blocks.BlockChunkID and Chunks_block.ChunkID = Chunks.ChunkID order by Chunks.ChunkName";
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
                            "BlockPerms"        => $block["Perms"],
                            "IncludeChildren"   => $block["IncludeChildren"]
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
    $tpl->assign("BlockList", $bSel);

    if ($this->catConID) {
        $sql = "select * from catcon where CatConID = " . $this->catConID;
        $result = mysql_query($sql, $this->db);
        if (!$result || !mysql_num_rows($result)) return;
        $curRow = mysql_fetch_array($result);

        $tpl->assign("CatConID",    $curRow["CatConID"]);
        $tpl->assign("ModName",     $curRow["ModName"]);
        $tpl->assign("ParentID",    $curRow["ParentID"]);
        $tpl->assign("Tag",         $curRow["Tag"]);
        $tpl->assign("Label",       $curRow["Label"]);
        $tpl->assign("TextBrief",   $curRow["TextBrief"]);
        $tpl->assign("TextChunk",   $curRow["TextChunk"]);
        $tpl->assign("TextFooter",  $curRow["TextFooter"]);
        $tpl->assign("TextList",    $curRow["TextList"]);
        $tpl->assign("TextChunkEditor",  getChunkFormField($curRow["TextChunk"],  $rows, "TextChunk",  "TextChunk"));
        $tpl->assign("TextFooterEditor", getChunkFormField($curRow["TextFooter"], $rows, "TextFooter", "TextFooter"));
        $tpl->assign("TextListEditor",   getChunkFormField($curRow["TextList"],   $rows, "TextList",   "TextList"));

    } else {
        foreach (array("TextChunk", "TextFooter", "TextList") as $key) {
            $tpl->assign($key . "Editor", getChunkFormField("", $rows, $key, $key));
        }
    }

    foreach (array("Tag", "Label", "TextBrief") as $key) {
        if (isset($app->request[$key])) $tpl->assign($key, $app->request[$key]);
    }
    foreach (array("TextChunk", "TextFooter", "TextList") as $key) {
        if (isset($app->request[$key])) $tpl->assign($key . "Editor", getChunkFormField($app->request[$key], $rows, $key, $key));
    }

    $tpl->assign("SaveTag", $saveTag);

    $app->addBlock(10, CONTENT_ZONE, "Edit Item", $tpl->get());
    $this->showList = 0;
}

/** deleteItem - Deletes the selected item and moves any of its children
  *              up to its parent.
  */
function deleteItem()
{
    global $app;
    $sql = "select ParentID, Level from catcon where CatConID = " . $this->catConID;
    $result = mysql_query($sql, $this->db);
    if (!$result || !mysql_num_rows($result)) return;
    $curRow = mysql_fetch_array($result);
    $par = $curRow['ParentID'];
    $lvl = $curRow['Level'];
    // We need to re-order the items on the parent side and the children
    // after they have been moved to the new level.
    $sql = "update catcon set Level = Level -1 where ModName = '" . $this->modName . "' and ParentID = $par and Level > $lvl";
    $result = mysql_query($sql, $this->db);
    // Now delete the row.
    $sql = "delete from catcon where CatConID = " . $this->catConID;
    $result = mysql_query($sql, $this->db);
    // Now, move the children to the parent level and renumber them.
    $sql = "select CatConID from catcon where ModName = '" . $this->modName . "' and ParentID = $par";
    $result = mysql_query($sql, $this->db);
    $newLvl = mysql_num_rows($result) + 1;
    $sql = "update catcon set Level = Level + $newLvl, ParentID = $par where ModName = '" . $this->modName . " and ParentID = " . $this->catConID;
    $result = mysql_query($sql, $this->db);
    
    // All finished.
}

/** validateFormData - Makes sure that the stuff the user entered
  * is valid.
  * @return True if it is, false if its not.
  */
function validateFormData()
{
    global $app;
    $retVal = 1;
    return $retVal;
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

    // Finally, make sure that the block isn't already on the page.
    $sql = "select CatConBlockID from catcon_blocks where CatConID = " . $this->catConID . " and BlockChunkID = $blockID";
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
    $incChildren = 0;
    if (isset($app->request["IncludeChildren"])) $incChildren = 1;
    $sql = "insert into catcon_blocks (CatConBlockID, CatConID, BlockChunkID, IncludeChildren) values(0, " . $this->catConID . ", $blockID, $incChildren)";
    $app->writeLog($sql);
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

    $sql = "select CatConBlockID from catcon_blocks where CatConID = " . $this->catConID . " and BlockChunkID = " . mysql_escape_string($_REQUEST["BlockSel"]);
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Remove Page Block", "Unable to load the specified page.");
        return;
    }

    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Remove Page Block", "That block was not found on this page.");
        return;
    }

    $sql = "delete from catcon_blocks where CatConID = " . $this->catConID . " and BlockChunkID = " . mysql_escape_string($_REQUEST["BlockSel"]);
    mysql_query($sql, $app->acmsDB);

    // Simply return.  If there was an error, the page won't be removed
}


};  // admplugin_catcon class


?>
