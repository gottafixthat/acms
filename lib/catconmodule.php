<?php
/**
 * catconmodule.php - Categorized Content base module.  Does all of the
 *                    heavy lifting for a categorized content module.
 *                    The only thing that a child of this module needs
 *                    to do is set the ModName.
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



require_once("acmsmodule.php");

/**
  * catconModule class.  Base class and common functions that are required
  * for all modules that use the catconModule class.
  *
  * @author     Marc Lewis <marc@cheetahis.com>
  * @version    0.0
  */
class catconModule {

var     $myURI;
var     $templatePath;
var     $modName = "";
var     $items;
var     $subcats;
var     $db;
var     $dispItem;
var     $dispChildren;
var     $pageTitle;

/*
 * Constructor for the catconModule class.
 *
 * @author      Marc Lewis <marc@cheetahis.com>
 */

function catconModule($mod, $pTitle = "")
{
    global $app;
    $this->myURI = $_SERVER['REQUEST_URI'];
    // Make sure that the module we've got is okay.
    if (strlen($mod)) {
        $db = $app->newDBConnection();
        $sql = "select CatConID from catcon where ModName = '" . mysql_escape_string($mod) . "' limit 1";
        $result = mysql_query($sql, $db);
        if ($result && mysql_num_rows($result)) {
            $this->modName = mysql_escape_string($mod);
            // Its valid.
        }
    }
    
    $this->items = array();
    $this->subcats = array();
    $this->dispItem = array();
    $this->dispChildren = array();
    $this->db = $app->newDBConnection();
    if (!strlen($pTitle)) $pTitle = $mod;
    $this->pageTitle = $pTitle;
}

/**
  * exec - Module entry point.  Figures out what to display based on the
  * URI and then displays it.
  */
function exec($action, $args = array())
{
    global $app;
    if (!strlen($this->modName)) {
        $app->writeLog("Call to catconModule with no module name!  Aborting.");
        return;
    }

    /*
    echo "<pre>"; print_r($args); echo "</pre>";
    echo "<pre>"; print_r($action); echo "</pre>";
    echo "<pre>"; print_r($app->getVars); echo "</pre>";
    unset($app->getVars["test3"]);
    echo "<pre>"; print_r($app->createURL()); echo "</pre>";
    */

    if (!strcmp("menu", $action)) {
        $opts = array(
                'Indent'            => "<img src=\"/static/img/menu-arrow-spacer.gif\">",
                'ExpandedIndicator' => "<img src=\"/static/img/menu-arrow-down.gif\">",
                'IndicatorSpacer'   => "<img src=\"/static/img/menu-arrow-spacer.gif\">",
                'ChildIndicator'    => "<img src=\"/static/img/menu-arrow-right.gif\">"
                );
        // Check to see if we need to expand our menu further.
        $retSt = "";
        $tmpPath = "";
        $truncAct = "/" . $this->modName;
        $truncURI = substr($this->myURI, 0, strlen("/" . $this->modName));
        //echo "$truncAct<br>$truncURI<br>";
        if (!strcmp($truncAct, $truncURI)) {
            $tmpPath = substr($this->myURI, strlen("/" . $this->modName)+1);
            $this->expandItems($tmpPath, 1);
            $this->parseContent();
            for ($i = 0; $i < count($this->items); $i++) {
                if ($this->items[$i]['visible']) {
                    $indents = array();
                    if (strlen($retSt)> 5) {
                        $retSt .= "<br>";
                    }
                    if ($this->items[$i]['level']) {
                        for ($j = 0; $j < $this->items[$i]['level']; $j++) {
                            //$retSt .= ":";
                            $retSt .= $opts['Indent'];
                        }
                    }
                    if ($this->items[$i]['childcount']) {
                        if ($this->items[$i]['expanded']) {
                            $retSt .= $opts['ExpandedIndicator'];
                        } else {
                            $retSt .= $opts['ChildIndicator'];
                        }
                    } else {
                        $retSt .= $opts['IndicatorSpacer'];
                    }
                    $retSt .= "<a href=\"" . $this->items[$i]['path'] . "\">" . $this->items[$i]['Label'] . "</a>";
                    //$retSt .= "<br />";
                }
            }
            $app->setPageTitle($this->pageTitle);
            //$retSt = $app->parseChunk($retSt);
        } else {
            // We're not part of the URI, just return a link to us.
            $this->expandItems($tmpPath, 1);
            $this->parseContent();
            $retSt = $opts['ChildIndicator'] . "[[/" . $this->modName . " " . $this->items[0]['Label'] . "]]";

        }
        return $retSt;
    }

    // Load the items and expand the tree based on the action
    if (is_array($args)) $args = join("/", $args);
    $this->expandItems($args);
    $this->parseContent();
    
    //echo "<pre>"; print_r($this->items); echo "</pre>";
    /*
    $tmpSt = "";
    for ($i = 0; $i < count($this->items); $i++) {
        if ($this->items[$i]['visible']) {
            if ($this->items[$i]['level']) {
                for ($j = 0; $j < $this->items[$i]['level']; $j++) {
                    $tmpSt .= "&nbsp;&nbsp;&nbsp;";
                }
            }
            $tmpSt .= $this->items[$i]['Label'] . " " . $this->items[$i]['Tag'];
            if ($this->items[$i]['showit']) {
                $tmpSt .= " - " . $this->items[$i]['TextBrief'];
            }
            $tmpSt .= "<br />";
        }
    }
    $app->addBlock(10, CONTENT_ZONE, "catconModule", "Hello worms.<p>$tmpSt");
    */

    $tpl = new acmsTemplate($app->templatePath . "catcon_list.tpl");
    $tpl->assign("Items", $this->items);
    $tpl->assign("DispItem", $this->dispItem);
    $tpl->assign("Children", $this->dispChildren);
    $app->addBlock(10, CONTENT_ZONE, $this->dispItem['TextBrief'], $tpl->get());
    $this->pageTitle = $this->dispItem['TextBrief'];

    $this->addExtraBlocks();
}

/**
  * loadCategory - Calls itself recursively to load all of the
  * items for a category into memory.
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
            $curRow["visible"] = ($level) ? 0 : 1;
            $curRow["showit"] = 0; // ($level) ? 0 : 1;
            $curRow["showpath"] = 0; // ($level) ? 0 : 1;
            $curRow["expanded"] = 0; // ($level) ? 0 : 1;
            $tmpNum = array_push($this->items, $curRow);
            $childCount = $this->loadItems($path . "/" . $curRow["Tag"], $curRow["CatConID"], $level + 1, $tmpNum-1);
            $this->items[$tmpNum-1]["childcount"] = $childCount;
            if ($childCount && ($curRow["visible"] || $curRow["showit"])) {
                $this->items[$tmpNum-1]["expanded"] = 1;
            }
        }
    }
    return $retVal;
}

/**
  * expandItems - Loads the items from the database and then 
  *               walks through the subcategories and figures out
  *               which items are expanded and which ones aren't.
  */
function expandItems($path, $isMenu = 0)
{
    global $app;
    $this->items = array();
    $this->loadItems("/" . $this->modName);

    // Make sure the path on the URI exists, or redirect to the first
    // valid path
    $pathValid = 0;
    $curPath = $this->myURI;
    if (!$isMenu) {
        while(!$pathValid) {
            foreach($this->items as $tmpItem) {
                if (!strcmp($curPath, $tmpItem['path'])) $pathValid = 1;
            }
            if (!$pathValid) {
                // Lop off a bit of the path.
                $curPath = substr($curPath, 0, strrpos($curPath, "/"));
                $app->writeLog("$curPath");
            }
        }
        if (strcmp($curPath, $this->myURI)) {
            header("Location: $curPath");
            exit;
        }
    }
            
    $this->subCats = array();
    $fullPath = "/" . $this->modName;
    if (strlen($path)) $fullPath .= "/" . $path;
    $tmpsubcats = split("/", substr($fullPath, 1)); // $this->modName . "/" . $path);
    $level = 0;
    $parent = 0;
    $showPath = 1;
    $workPath = "";
    foreach($tmpsubcats as $tmpcat) {
        $workPath .= "/" . $tmpcat;
        // Find this subcat with the specified parent.
        for($i = 0; $i < count($this->items); $i++) {
            if (!strcmp($this->items[$i]['Tag'], $tmpcat) && $this->items[$i]['ParentID'] == $parent) {
                // Found the current items parent.  Unselect everything
                // else on the parents level.
                // If this is a menu, all higher trees should remain open.
                if (!$isMenu) {
                    for ($j = 0; $j < count($this->items); $j++) {
                        if ($this->items[$j]['level'] == $level && $this->items[$j]['CatConID'] <> $this->items[$i]['CatConID']) {
                            $this->items[$j]['visible'] = 0;
                        }
                    }
                }
                /*
                for ($j = 0; $j < count($this->items); $j++) {
                    if ($this->items[$j]['level'] == $level) {
                        $this->items[$j]['showit'] = 0;
                        $this->items[$j]['showpath'] = 0;
                    }
                }
                */
                // Increment the level and make everything on it visible.
                $level++;
                for ($j = 0; $j < count($this->items); $j++) {
                    if ($this->items[$j]['level'] == $level && $this->items[$j]['ParentID'] == $this->items[$i]['CatConID']) {
                        $this->items[$j]['visible'] = 1;
                        //$this->items[$j]['showit'] = 1;
                    }
                }
                // Set our parent ID to us now.
                $parent = $this->items[$i]['CatConID'];
                // And add this category to our subcat list.
                array_push($this->subcats, $tmpcat);
            }

            if (!strcmp($workPath, $this->items[$i]['path'])) {
                $this->items[$i]['showpath'] = 1;
                if ($this->items[$i]['childcount']) $this->items[$i]['expanded'] = 1;
            }
        }
    }

    // Get the path now.
    for($i = 0; $i < count($this->items); $i++) {
        // Check to see if this is the item we're showing based on
        // the path.
        //echo "FullPath: $fullPath<br>";
        //$this->items[$i]['showpath'] = $showPath;
        if (!strcmp($fullPath, $this->items[$i]['path'])) {
            $this->items[$i]['showit'] = 1;
            if ($this->items[$i]['childcount']) $this->items[$i]['expanded'] = 1;
            $showPath = 0;
        }
    }

}

/** parseContent - For each item that is visible, parse the content
  *                so it can be displayed to the user.
  */
function parseContent()
{
    global  $app;

    for($i = 0; $i < count($this->items); $i++) {
        if ($this->items[$i]['visible']) {
            $this->items[$i]['TextList']   = $app->parseChunk($this->items[$i]['TextList']);
        }
        if ($this->items[$i]['showit']) {
            $this->items[$i]['TextChunk']  = $app->parseChunk($this->items[$i]['TextChunk']);
            $this->items[$i]['TextFooter'] = $app->parseChunk($this->items[$i]['TextFooter']);
            // Set the main variable for this item since we're showing it.
            $this->dispItem = $this->items[$i];
        }
    }
    // Now, get the children of the displayed item, if any.
    for($i = 0; $i < count($this->items); $i++) {
        if ($this->items[$i]['ParentID'] == $this->dispItem['CatConID']) {
            array_push($this->dispChildren, $this->items[$i]);
        }
    }
}

/** addExtraBlocks - Loads up the extra blocks to display for the
  * visible pages and adds them to the layout.
  */
function addExtraBlocks()
{
    global $app, $handlers;
    $tree = "";
    $myID = 0;
    // Get the list of items we descended from for blocks.
    for($i = 0; $i < count($this->items); $i++) {
        if ($this->items[$i]['showpath']) {
            if (strlen($tree)) $tree .= ",";
            $tree .= $this->items[$i]['CatConID'];
        }
        if ($this->items[$i]['showit']) $myID = $this->items[$i]['CatConID'];
    }

    $db = $app->newDBConnection();
    $sql = "select distinct(catcon_blocks.BlockChunkID), Chunks.ChunkID, Chunks.ChunkName from catcon_blocks, Chunks where Chunks.ChunkID = catcon_blocks.BlockChunkID and ((catcon_blocks.CatConID in ($tree) and catcon_blocks.IncludeChildren > 0) or catcon_blocks.CatConID = $myID)";
    $result = mysql_query($sql, $db);
    if ($result && mysql_num_rows($result)) {
        $app->loadHandler("block");
        while ($curRow = mysql_fetch_array($result)) {
            $handlers["block"]->showChunk($curRow["ChunkName"]);
        }
    }




}

};

?>
