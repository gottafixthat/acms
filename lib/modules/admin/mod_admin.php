<?php
/**
 * mod_admin.php - Admin module.
 *
 * This is still *very* rough at this point.
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
if (eregi('mod_admin.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $modules, $admplugins;

$modules['admin'] = new adminModule();

// The main ACMS class
class adminModule {

// Class Variables
var $templatePath;

// Class "globals"
var $categoryList = array();

// Our internal category editor variables
var $CategoryID;
var $ParentID;
var $Title;
var $IconTag;
var $Description;

// Our internal site configuration editor variables.
var $scSiteName;

/*
** adminModule - Constructor.  This handles the setup of the various admin
**               functions.
*/

function adminModule()
{
    // Get the template file name.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts["dirname"] . "/";

    $this->CategoryID       = 0;
    $this->ParentID         = "";
    $this->Title            = "";
    $this->IconTag          = "";
    $this->Description      = "";
    // Site Config variables.
    $this->scSiteName       = "";

} // adminModule

/*
** exec - Entry point.  This is the function that gets called by the ACMS
**        kernel when the module is loaded.  It should check for arguments
**        and then call the appropriate admin section.
*/

function exec($action, $args = array())
{
    global $app, $INCLUDE_DIR, $admplugins;
    $retVal = "";
    $parms  = "";

    //echo "action = '$action'<br><pre>"; print_r($args); echo "</pre>";
    // Make sure they are logged in and have appropriate permissions.
    if (!$app->hasAccess("read", 2660, 0, 0)) {
        $app->addBlock(0, CONTENT_ZONE, "Permission Denied", "You do not have permission to access this content.");
        return;
    }

    // Split out our arguments.  We may be given more than just one thing
    // on the command line.
    if (is_array($args)) {
        $parms = join("/", $args);
    }

    if (strpos($args, "/") !== FALSE) {
        list($args, $parms) = split("/", $args, 2);
    }
    
    if ($action == "exec") $action = $args;

    $urimod = "";
    if (substr($_SERVER['REQUEST_URI'], 0, 6) == "/admin") {
        $urimod = substr($_SERVER['REQUEST_URI'], 7);
        if (strpos($urimod, "/") === FALSE) {
            $urimod = "";
        } else {
            list($urimod, $junk) = split("/", $urimod, 2);
        }
    }

    // Load our plugins.
    $this->loadPlugins($INCLUDE_DIR . "modules");
    
    $links = "";        // Our "menu"
    //$links .= "<a href=\"/\">Home</a>\n";
    $links .= "<a href=\"/admin/SiteConfig\">Site Config</a>\n";
    $links .= "<a href=\"/admin/Categories\">Categories</a>\n";
    $links .= ":<a href=\"/admin/NewCategory\">New Category</a>\n";
    $links .= "<a href=\"/admin/ListChunks\">List Chunks</a>\n";
    $plugMenus = "";

    // Walk through our plugins and get any menu options they may have.
    // echo "<pre>"; print_r($admplugins); echo "</pre>";
    foreach($admplugins as $plug) {
        //echo "Found plugin '" . $plug->pluginName . "'<br>";
        // Check to see if this plugin has a menu for us.
        // Also check to see if we want to expand the menu.
        $expandMenu = 0;
        if (isset($plug->pluginName)) {
            if (!strcmp($action, $plug->pluginName) || !strcmp($urimod, $plug->pluginName)) $expandMenu = 1;
        }
        if (method_exists($plug, 'menu')) {
            $plugMenus .= $plug->menu($expandMenu);
        }
        if (method_exists($plug, 'create')) {
            $links .= ":<a href=\"/admin/" . $plug->pluginName . "/create\">Create " . $plug->handler . "</a>\n";
        }
    }

    //$links .= "<a href=\"/admin/ListChunks\">List Chunks</a><br>";
    $opts = array(
            'Indent'            => "&nbsp;&nbsp;",
            'ExpandedIndicator' => "{{MenuArrowDown}}",
            'IndicatorSpacer'   => "{{MenuArrowSpacer}}",
            'ChildIndicator'    => "{{MenuArrowRight}}"
            );
    //echo "<pre>$links</pre><hr>";
    $links = $app->createMenu($links, $opts);
    $links .= $app->createMenu($plugMenus, $opts);

    // Check to see if our argument was a call to a plugin.
    if (isset($admplugins[$action])) {
        if (method_exists($admplugins[$action], 'exec')) {
            // It was to a plugin.  Pass control off to it.
            $admplugins[$action]->exec($parms);
            return;
        }
    }

    if (isset($args["Filter"])) {
        $app->setSessVar("AdminHandlerListFilter", $opts["Filter"]);
    }

    switch ($action) {
        case    "MainMenu":
            $retVal = $links;
            break;

        case    "SiteConfig":
            $this->siteConfig();
            break;

        case    "Categories":
            $this->listCategories();
            break;

        case    "EditCategory":
            $this->editCategory($parms);
            break;

        case    "NewCategory":
            $this->newCategory();
            break;

        case    "DeleteCategory":
            $this->deleteCategory($parms);
            break;

        case    "ListChunks":
            $this->listChunks($parms);
            break;

        case    "EditChunk":
            $this->editChunk($parms);
            break;

        case    "DelChunk":
            $this->deleteChunk($parms);
            break;

        default:
            $app->addBlock(0, CONTENT_ZONE, "Admin", "Unknown action '$args'");
            // We should show some sort of main page here.
            break;
    }

    //$retVal = "Loaded admin module with the arguments '$args', test has a value of '$test'";

    return $retVal;

}   // exec

/*
** loadPlugins - Searches through the modules directories and loads
**               any admin plugins found.
*/

function loadPlugins($dname)
{
    global  $app;

    if (substr($dname, strlen($dname)-1, 1) != "/") $dname .= "/";

    if ($dh = opendir($dname)) {
        while (($fname = readdir($dh)) !== false) {
            if ($fname != "." && $fname != "..") {
                if (is_file($dname . $fname)) {
                    // Check to see if this file is a plugin
                    //echo "examining '$fname'</br>";
                    if (eregi("^admplug_[a-z0-9_]+.php$", $fname)) {
                        // Looks like a plugin.
                        //$app->writeLog("Loading admin plugin '$dname$fname'");
                        if (is_readable($dname . $fname)) {
                            include_once($dname . $fname);
                        }
                    }
                } else if (is_dir($dname . $fname)) {
                    if (is_readable($dname . $fname)) {
                        $this->loadPlugins($dname . $fname);
                    }
                }
            }
        }
    }
    
}

/*
** listChunks- Lists all of the chunks that are contained in the ACMS
*/

function listChunks($setFilter = "")
{
    global $app, $ACMSRewrite, $ACMS_BASE_URI, $admplugins;

    $filt = "";

    if (isset($setFilter) && strlen($setFilter)) {
        $filt = $setFilter;
    } else if (isset($_REQUEST['HandlerFilter']) && strlen($_REQUEST['HandlerFilter'])) {
        $filt = $_REQUEST['HandlerFilter'];
    }

    $whichHandler = "";
    if (isset($filt) && strlen($filt)) {
        if (!strcasecmp("All...", $filt)) {
            $app->setSessVar("AdminHandlerListFilter", "");
        } else {
            $tmpFilt = "";
            $sql = "select ChunkID from Chunks where Handler = '" . mysql_escape_string($filt) . "'";
            $result = mysql_query($sql, $app->acmsDB);
            if ($result) {
                if (mysql_num_rows($result)) {
                    $tmpFilt = $filt;
                }
            }

            $app->setSessVar("AdminHandlerListFilter", $tmpFilt);

            // Check to see if there is a specific list function for
            // the type of chunk we are filtering on.
            /*
            if (isset($admplugins[$tmpFilt]) && method_exists($admplugins[$tmpFilt], 'listChunks')) {
                // Pass control to the appropriate editor.
                $admplugins[$tmpFilt]->listChunks();
                return;
            }
            */
        }
    }

    // Check to see if we'll be doing an internal listing or calling a 
    // plugin's listing.
    $content = "";
    if (isset($app->acmsSessVars["AdminHandlerListFilter"]) && strlen($app->acmsSessVars["AdminHandlerListFilter"])) {
        $tmpfilt = $app->acmsSessVars["AdminHandlerListFilter"];
        foreach($admplugins as $plug) {
            //echo "Found plugin '" . $plug->pluginName . "'<br>";
            // Check to see if this is the plugin we are looking for.
            if (isset($plug->handler)) {
                if (!strcmp($plug->handler, $tmpfilt)) {
                    if (method_exists($plug, 'listChunks')) {
                        // Got it.  It has a function to get them.
                        $content = $plug->listChunks();
                        break;
                    }
                }
            }
        }
    }

    // Do an internal listing, but only if we have no content.
    if (!strlen($content)) {
        $tpl = new acmsTemplate($this->templatePath . "listchunks.tpl");
        $chunkList = array();

        // Do our query and get our blocks.
        $sql =  "select ChunkID, ChunkName, Handler, UserID, GroupID, Perms from Chunks";
        if (isset($app->acmsSessVars["AdminHandlerListFilter"]) && strlen($app->acmsSessVars["AdminHandlerListFilter"])) {
            $sql .= " where Handler = '" . mysql_escape_string($app->acmsSessVars["AdminHandlerListFilter"]) . "' ";
        }
        $sql .= " order by ChunkName";
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Admin - List Chunks", "No chunks were found.");
            return;
        }

        if (!mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Admin - List Chunks", "No chunks were found.");
            return;
        }

        $rows = array();
        while ($curRow = mysql_fetch_array($result)) {
            array_push($rows, $curRow);
        }
        foreach($rows as $curRow) {
            // Create the URL to edit the chunk
            $editURL = "<a href=\"";
            if ($ACMSRewrite) {
                $editURL .= "/admin/EditChunk/" . $curRow["ChunkID"];
            } else {
                $editURL .= "$ACMS_BASE_URI?page=admin/EditChunk/" . $curRow["ChunkID"];
            }
            $editURL .= "\">" . $curRow["ChunkName"] . "</a>";

            $delURL = "<a href=\"";
            if ($ACMSRewrite) {
                $delURL .= "/admin/DelChunk/" . $curRow["ChunkID"];
            } else {
                $delURL .= "$ACMS_BASE_URI?page=admin/DelChunk/" . $curRow["ChunkID"];
            }
            $delURL .= "\"";
            $delURL .= " onClick=\"return confirm('There is no way to undo this action.\\nAnd there is no reference checking yet.\\nAre you sure you want to delete this chunk?')\"";
            $delURL .= ">Delete</a>";

            array_push($chunkList, array(
                        "ChunkID"       => $curRow["ChunkID"],
                        "ChunkName"     => $editURL,
                        "Handler"       => $curRow["Handler"],
                        "Owner"         => $app->getUser($curRow["UserID"]),
                        "Group"         => $app->getGroup($curRow["GroupID"]),
                        "Perms"         => $curRow["Perms"],
                        "DelURL"        => $delURL
                        ));
        }
        $tpl->assign("Chunks", $chunkList);
        $content = $tpl->get();
    }
    
    // Create a small navigation form for limiting what types of chunks 
    // to display.
    $navtext = "";
    $navtpl = new acmsTemplate($this->templatePath . "chunklistnav.tpl");
    $sql = "select distinct Handler from Chunks order by Handler";
    $result = mysql_query($sql, $app->acmsDB);
    if ($result) {
        if (mysql_num_rows($result)) {
            $optText = "<option>All...</option>";
            while($curRow = mysql_fetch_array($result)) {
                $optText .= "<option";
                if (isset($app->acmsSessVars["AdminHandlerListFilter"]) && !strcmp($app->acmsSessVars["AdminHandlerListFilter"], $curRow["Handler"])) {
                    $optText .= " selected";
                }

                $optText .= ">" . $curRow["Handler"];
                $optText .= "</option>";
            }
            $navtpl->assign("HandlerOpts", $optText);
            $navtext = $navtpl->get();
        }
    }
    
    $app->addBlock(10, CONTENT_ZONE, "Admin - List Chunks", $content, "", array('TitleNav' => $navtext));
}

/*
** editChunk - Calls the admin plugin responsible for editing the chunk
**             type.
**
**             The chunk to edit must be found in $ChunkID, and is the
**             numeric ChunkID from the database, not the name.
**
*/

function editChunk($ChunkID)
{
    global $app, $admplugins;

    // See if we can locate the chunk.
    $sql = "select ChunkID, ChunkName, Handler from Chunks where ChunkID = " . mysql_escape_string($ChunkID);
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Chunk", "Unable to load ChunkID '$ChunkID'.");
        return;
    }
    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Chunk", "Unable to load ChunkID '$ChunkID'.");
        return;
    }
    
    // See if we are going to return to a different page
    if (isset($_SERVER['HTTP_REFERER'])) {
        $tmpRef = $_SERVER['HTTP_REFERER'];
        
        $myRef = eregi_replace("http[s]?://" . $_SERVER['SERVER_NAME'], "", $tmpRef);
        //echo "$myRef<br>";

        if (!eregi("^/admin/", $myRef)) {
            // myRef points to where we came from.
            $app->setSessVar("EditChunkReturnURI", $myRef);
        }
    }

    // Load the result of the query.
    $chunk = mysql_fetch_array($result);

    // Before loading other plugins, check our preloaded plugins and see
    // if one of those will work to edit this chunk.
    $loadHandler = true;
    $plugName    = $chunk["Handler"];
    // Walk through them one by one.
    foreach($admplugins as $plug) {
        if (isset($plug->handler)) {
            if (!strcmp($plug->handler, $chunk["Handler"])) {
                // We have a plugin that says they are for this
                // type of handler, check for an edit function.
                if (method_exists($plug, 'edit')) {
                    // They do, set our plugName
                    $plugName = $plug->pluginName;
                    $loadHandler = false;
                }
            }
        }
    }

    if ($loadHandler) {
        // Nope.  Don't have a plugin already for this.  Load one.
        // Now that we have verified the chunk ID and have the chunk name and
        // handler, check to see if an editor for the handler exists.
        // The file format is "handlradm_handler.php".
        $parts = pathinfo(__FILE__);
        // Set the include file name
        $iname  = $parts["dirname"];
        $iname .= "/hndlradm_" . $chunk["Handler"] . ".php";
        if (!file_exists($iname) || !is_readable($iname)) {
            $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Chunk", "Unable to locate the editor for the chunk '" . $chunk["ChunkName"] . "' that uses the handler '" . $chunk["Handler"] . "'.");
            $this->listChunks();
            return;
        }

        // Load it.
        require_once($iname);
    }

    /*
    echo "<pre>"; print_r($admplugins); echo "</pre>";
    $handler = &$admplugins[$chunk["Handler"]];
    */

    if (isset($admplugins[$plugName]) && method_exists($admplugins[$plugName], 'edit')) {
        // Pass control to the appropriate editor.
        $admplugins[$plugName]->edit($chunk['ChunkID']);
    } else {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Chunk", "The handler '" . $chunk["Handler"] . "' has no edit capabilties.");
        return;
    }


}

/*
** deleteChunk - Calls the admin plugin responsible for editing the chunk
**               type.
**
**               If no plugin is found with a specific delete method, then
**               we simply delete the chunk and return to the chunk list.
**
*/

function deleteChunk($ChunkID)
{
    global $app, $admplugins;

    // First, see if we can locate the chunk.
    $sql = "select ChunkID, ChunkName, Handler from Chunks where ChunkID = " . mysql_escape_string($ChunkID);
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Delete Chunk", "Unable to locate ChunkID '$ChunkID'.");
        return;
    }
    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Delete Chunk", "Unable to locate ChunkID '$ChunkID'.");
        return;
    }

    // Load the result of the query.
    $chunk = mysql_fetch_array($result);

    // Before loading other plugins, check our preloaded plugins and see
    // if one of those will work to edit this chunk.
    $useHandler  = false;
    $plugName    = $chunk["Handler"];
    // Walk through them one by one.
    foreach($admplugins as $plug) {
        if (isset($plug->handler)) {
            if (!strcmp($plug->handler, $chunk["Handler"])) {
                // We have a plugin that says they are for this
                // type of handler, check for an edit function.
                if (method_exists($plug, 'delete')) {
                    // They do, set our plugName
                    $plugName = $plug->pluginName;
                    $useHandler = true;
                }
            }
        }
    }

    if (!$useHandler) {
        // We don't already have a plugin for this handler, check to see
        // if we have any more specific ones.
        // Since we have verified the chunk ID and have the chunk name and
        // handler, check to see if an editor for the handler exists.
        // The file format is "handlradm_handler.php".
        $parts = pathinfo(__FILE__);
        // Set the include file name
        $iname  = $parts["dirname"];
        $iname .= "/hndlradm_" . $chunk["Handler"] . ".php";
        if (file_exists($iname) && is_readable($iname)) {
            // Load it.
            require_once($iname);
            // Now that the plugin is loaded, check to see if it has
            // a delete function.
            if (isset($admplugins[$plugName]) && method_exists($admplugins[$plugName], 'delete')) {
                $useHandler = true;
            }
        }
    }

    /*
    echo "<pre>"; print_r($admplugins); echo "</pre>";
    $handler = &$admplugins[$chunk["Handler"]];
    */

    // If we found a handler for the delete, use it.  Otherwise, just
    // delete the chunk and return to the list.
    if ($useHandler) {
        $admplugins[$plugName]->delete($chunk['ChunkID']);
    } else {
        $cc = new chunkClass("page");
        $cc->delete($chunk["ChunkID"]);
        // Don't check for an error, just return to the list.
        ob_end_clean();
        header("Location: /admin/ListChunks");
        exit;
    }


}

/*
** listCategories - Category maintenance.
*/

function listCategories()
{
    global  $app;
    
    // We need a second database connection.
    $this->categoryList = array();
    $content = $this->getChildCategories(0, 0);

    if (!count($this->categoryList)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - List Categories", "No categories were found.");
        return;
    }

    // Create our template.
    $tpl = new acmsTemplate($this->templatePath . "listcategories.tpl");
    $cats = array();

    foreach($this->categoryList as $item) {
        $editLink  = "<a href=\"/admin/EditCategory/" . $item["CategoryID"] . "\">";
        $editLink .= $item["Title"];
        $editLink .= "</a>";
        $level = "";
        $levelOpen  = "";
        $levelClose = "";
        if ((int)$item["Level"]) {
            for ($i = 0; $i < (int)$item["Level"]; $i++) {
                $levelOpen .= "<div class=\"indent\">";
                $levelClose.= "</div>";
                $level .= "&nbsp;&nbsp;";
            }
        }
        array_push($cats, array(
                    "CategoryID"    => $item["CategoryID"],
                    "ParentID"      => $item["ParentID"],
                    "Title"         => $item["Title"],
                    "IconTag"       => $item["IconTag"],
                    "IconParsed"    => $app->parseChunk($item["IconTag"]),
                    "Description"   => $item["Description"],
                    "Level"         => $level,
                    "LevelOpen"     => $levelOpen,
                    "LevelClose"    => $levelClose,
                    "EditLink"      => $editLink
                    ));
    }
    $tpl->assign("Categories", $cats);

    $app->addBlock(0, CONTENT_ZONE, "Admin - List Categories", $tpl->get());
}

function getChildCategories($parentID, $level)
{
    global $app;
    $retVal = "";
    $myDBConn = $app->newDBConnection();
    $sql = "select * from Categories where ParentID = $parentID order by Title";
    $result = mysql_query($sql, $myDBConn);
    if ($result) {
        if (mysql_num_rows($result)) {
            while($curRow = mysql_fetch_array($result)) {
                $item = array(
                          "CategoryID"  => $curRow["CategoryID"],
                          "ParentID"    => $curRow["ParentID"],
                          "Title"       => $curRow["Title"],
                          "IconTag"     => $curRow["IconTag"],
                          "Description" => $curRow["Description"],
                          "Level"       => "$level"
                        );
                array_push($this->categoryList, $item);

                if ($level) {
                    for ($i = 0; $i < $level; $i++) {
                        $retVal .= "*";
                    }
                }
                $retVal .= $curRow["Title"];
                $retVal .= "\n";
                $retVal .= $this->getChildCategories($curRow["CategoryID"], $level+1);
            }
        }
    }

    return $retVal;
}

/*
** editCategory - Handles the form and database updates for editing categories.
**
*/

function editCategory($catID)
{
    global $app;

    // See if we can locate the category.
    $sql = "select CategoryID from Categories where CategoryID = " . mysql_escape_string($catID);
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Category", "Unable to load the category '$catID'.");
        return;
    }
    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Category", "Unable to load the category '$catID'.");
        return;
    }

    // See if they are posting data.  If they are, then we will have an
    // action variable set.
    if (!isset($_REQUEST['Action'])) {
        if (!$this->loadCategory($catID)) return;
        $this->showCategoryEditForm();
        return;
    }

    $this->getCategoryFormVars();

    switch($_REQUEST['Action']) {
        case "edit":
            // We came from the edit form.  Do something with what the 
            // user gave us.
            $this->processCategoryEdit();
            break;

        default:
            $this->showCategoryEditForm();
            break;
    }

    return;
}

/*
** loadCategory - Loads the specified category into our class variables.
*/

function loadCategory($catID)
{
    global $app;

    $sql = "select CategoryID, ParentID, Title, IconTag, Description from Categories where CategoryID = $catID";
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Category", "Unable to load the specified category.");
        return false;
    }

    if (!mysql_num_rows($result)) {
        $app->addBlock(0, CONTENT_ZONE, "Admin - Edit Category", "Unable to load the specified category.");
        return false;
    }

    $curRow  = mysql_fetch_array($result);
    $this->CategoryID       = $curRow["CategoryID"];
    $this->ParentID         = $curRow["ParentID"];
    $this->Title            = $curRow["Title"];
    $this->IconTag          = $curRow["IconTag"];
    $this->Description      = $curRow["Description"];

    return true;
}

/*
** showCategoryEditForm - Displays the edit form, filling in any variables
**                        that we need.
*/

function showCategoryEditForm()
{
    global $app;
    
    // Create our list of categories so that a parent may be selected.
    $selOpts = "<option value=\"0\">[Top Level Item]</option>\n";
    $sql = "select CategoryID, Title from Categories order by Title";
    $result = mysql_query($sql, $app->acmsDB);
    if ($result) {
        if (mysql_num_rows($result)) {
            while($curRow = mysql_fetch_array($result)) {
                $sel = "";
                if ($this->ParentID == $curRow["CategoryID"]) {
                    $sel = " selected";
                }
                $selOpts .= "<option value=\"" . $curRow["CategoryID"] . "\"$sel>";
                $selOpts .= $curRow["Title"];
                $selOpts .= "</option>\n";
            }
        }
    }
    // If we're editing, give them the option to delete the category.
    $delBut = "";
    if ($this->CategoryID) {
        $delBut  = "<input type=\"submit\" name=\"Delete\" value=\"Delete\" ";
        $delBut .= "onClick=\"return confirm('There is no way to undo this action.  Are you sure you want to delete this category?')\">";
    }

    $tpl = new acmsTemplate($this->templatePath . "categoryeditform.tpl");

    $tpl->assign("Action",         "edit");
    $tpl->assign("CategoryID",     $this->CategoryID);
    $tpl->assign("ParentID",       $this->ParentID);
    $tpl->assign("ParentSel",      $selOpts);
    $tpl->assign("IconTag",        $this->IconTag);
    $tpl->assign("Title",          $this->Title);
    $tpl->assign("Description",    $this->Description);
    $tpl->assign("DeleteButton",   $delBut);

    $app->addBlock(10, CONTENT_ZONE, "Edit Category", $tpl->get());
}


/*
** getCategoryFormVars - Gets the form variables and loads them into our class
**                       variables.  We can clean them here if we want/need to.
*/

function getCategoryFormVars()
{
    if (isset($_REQUEST["CategoryID"])) $this->CategoryID  = $_REQUEST["CategoryID"];
    else $this->ChunkID = 0;
    $this->ParentID         = stripslashes($_REQUEST["ParentID"]);
    $this->Title            = stripslashes($_REQUEST["Title"]);
    $this->IconTag          = stripslashes($_REQUEST["IconTag"]);
    $this->Description      = stripslashes($_REQUEST["Description"]);
}

/*
** processCategoryEdit - The user hit either Save or Cancel on the edit form.
**                       this function does the appropriate thing with that.
*/

function processCategoryEdit()
{
    global $app;

    if (isset($_REQUEST["Cancel"]) && strlen($_REQUEST["Cancel"])) {
        // The user hit cancel, abort
        // Don't check for an error, just return to the list.
        ob_end_clean();
        header("Location: /admin/Categories");
        exit;
    }

    // Check to see if there was another action, such as Add.
    if (isset($_REQUEST["Delete"]) && strlen($_REQUEST["Delete"])) {
        // They want to delete it.  So go forward and do it.
        if ($this->CategoryID) {
            $sql = "delete from CategoryItems where CategoryID = " . $this->CategoryID;
            mysql_query($sql, $app->acmsDB);
            $sql = "delete from Categories where CategoryID = " . $this->CategoryID;
            mysql_query($sql, $app->acmsDB);
            ob_end_clean();
            header("Location: /admin/Categories");
            exit;
        }
    }
    
    // Okay, if we made it here, they must want to save.
    // Our form variables have already been loaded, so verify that we have
    // a Category ID.  If we don't, then its an insert.  Otherwise, make sure
    // there is no name collision.

    $errText = "";

    // We'll do some basic validation first.
    if (!strlen($this->Title)) {
        $errText .= "<li>All categories must have a unique title</li>";
    }

    if (!strlen($this->Description)) {
        $errText .= "<li>All categories must have a description</li>";
    }

    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the category.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Category - Error", $errText);
        $this->showCategoryEditForm();
        return;
    }
    
    if (!$this->CategoryID) {
        // Okay, we're doing an insert.  We just need to check that
        // there are no chunks with this name already.
        $sql = "select CategoryID from Categories where Title = '" . mysql_escape_string($this->Title) . "'";
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Category - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showCategoryEditForm();
            return;
        }
        if (mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Category - Error", "A category already exists in the database with the name '" . $this->Title . "'.  Category names must be unique.");
            $this->showCategoryEditForm();
            return;
        }
        // Create our insert query.
        $sql  = "insert into Categories(CategoryID, ParentID, Title, IconTag, Description) values (0, ";
        $sql .= mysql_escape_string($this->ParentID) . ", ";
        $sql .= "'" . mysql_escape_string($this->Title) . "',";
        $sql .= "'" . mysql_escape_string($this->IconTag) . "', ";
        $sql .= "'" . mysql_escape_string($this->Description) . "'";
        $sql .= ")";
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Category - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showCategoryEditForm();
            return;
        }
        // We saved, return to the category list
        ob_end_clean();
        header("Location: /admin/Categories");
        exit;

    } else {
        // Make sure we don't have a category with this name already.
        $sql = "select CategoryID from Categories where Title = '" . mysql_escape_string($this->Title) . "' and CategoryID <> " . mysql_escape_string($this->CategoryID);
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Category - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showCategoryEditForm();
            return;
        }
        if (mysql_num_rows($result)) {
            $app->addBlock(0, CONTENT_ZONE, "Save Category - Error", "A category already exists in the database with the name '" . $this->Title . "'.  Category names must be unique.");
            $this->showCategoryEditForm();
            return;
        }

        // If we made it here, we're good to replace the database entry.
        $sql  = "update Categories set ";
        $sql .= "Title = '" . mysql_escape_string($this->Title) . "', ";
        $sql .= "ParentID = " . mysql_escape_string($this->ParentID) . ", ";
        $sql .= "IconTag = '" . mysql_escape_string($this->IconTag) . "', ";
        $sql .= "Description = '" . mysql_escape_string($this->Description) . "' ";
        $sql .= " where CategoryID = " . mysql_escape_string($this->CategoryID);
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) {
            $app->addBlock(0, CONTENT_ZONE, "Save Category - Error", "Error while communicationg with the database.  Please try again later.");
            $this->showCategoryEditForm();
            return;
        }
        // We saved, return to the category list
        ob_end_clean();
        header("Location: /admin/Categories");
        exit;

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
** newCategory - Shows the create form and handles the input from the
**               user.  Once the user has entered basic information, we
**               we save and then take them to the edit form.
*/

function newCategory()
{
    global $app;

    // Check our request variables to see if we need to load variables.
    if (!isset($_REQUEST['Action'])) {
        $this->showCategoryEditForm();
        return;
    }

    // If we made it here, there is an action that has been set that we
    // need to look at.  In other words, the user hit a button on the
    // form.
    $this->getCategoryFormVars();

    switch($_REQUEST['Action']) {
        case "edit":
            // We came from the edit form.  Do something with what the 
            // user gave us.
            $this->processCategoryEdit();
            break;

        default:
            $this->showCategoryEditForm();
            break;
    }
}

/*
** siteConfig - Allows the user to configure various aspects of the site.
*/

function siteConfig()
{
    global $app;
    // Load the site config form variables, either from the global 
    // settings or from the form.
    if (!isset($_REQUEST['Action'])) {
        $this->loadSiteConfig();
    } else {
        $this->getSiteConfigFormVars();
    }

    if (!isset($_REQUEST["Save"]) || !strlen($_REQUEST["Save"])) {
        $this->showSiteConfigForm();
        return;
    }

    // If we made it to this point, the user actually put something
    // in the form and hit an action button.
    // Verify the input.
    $errText = "";
    if (!strlen($this->scSiteName)) {
        $errText .= "<li>The site name must be specified</li>";
    }

    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the configuration.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Configuration - Error", $errText);
        $this->showSiteConfigForm();
        return;
    }

    // If we made it here, we're good to save.
    // Because of the way the config variables are saved, we need to go through
    // them one by one.

    $sql = "replace into SiteConfig (CfgName, CfgVal) values ('SiteName', '" . mysql_escape_string($this->scSiteName) . "')";
    if (!mysql_query($sql, $app->acmsDB)) {
        $errText .= "<li>Error updating site name</li>";
    }

    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to save the configuration.  The following errors occurred:<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Save Configuration - Error", $errText);
        $this->showSiteConfigForm();
        return;
    } else {
        $app->addBlock(0, CONTENT_ZONE, "Save Configuration - Done", "The site configuration has been updated.");
    }
}

/*
** loadSiteConfig - Loads the class variables with the site configuration
**                  variables.
*/

function loadSiteConfig()
{
    global $siteConfig;
    $this->scSiteName       = $siteConfig['SiteName'];
}

/*
** getSiteConfigFormVars - Gets the form variables and loads them into our 
**                         class variables.  We can clean them here if we 
**                         want/need to.
*/

function getSiteConfigFormVars()
{
    $this->scSiteName       = trim(stripslashes($_REQUEST["SiteName"]));
}

/*
** showSiteConfigForm - Shows the site configuration form to the user.
*/

function showSiteConfigForm()
{
    global $app;

    $tpl = new acmsTemplate($this->templatePath . "siteconfigform.tpl");
    $tpl->assign("Action",         "edit");
    $tpl->assign("SiteName",       $this->scSiteName);

    $app->addBlock(10, CONTENT_ZONE, "Site Configuration", $tpl->get());
}

};  // adminModule class


?>
