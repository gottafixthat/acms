<?php
/**
 * chunklib.php - Contains the class "chunk" which handles all of the 
 *                storage and retrieval functions for a chunk.
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
if (eregi('chunklib.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

class chunkClass {

// Our public/global variables.
var $fields;

var $chunkNameOrig;

// This is where we keep track of what columns go into what tables
var $chunkCols = array("ChunkID", "ChunkName", "Title", "Perms", "Active", "UserID",
        "GroupID", "Weight", "LastModified", "Handler", "Chunk","Rendered");
var $extCols = array();

var $chunkCats = array();

/*
** chunk - The main constructor.
*/

function chunkClass($handler = "")
{
    global $app;
    // Set our "standard" columns, which could be extended with another
    // table...
    foreach($this->chunkCols as $col) {
        $this->fields[$col] = "";
    }

    $chunkNameOrig = "";

    // If the handler is set, we need to check and load any extended
    // columns we may have.  They will be used for inserts.
    if (strlen($handler)) $this->defineExtCols($handler);
}

/*
** defineExtCols - Loads the extended columns based on the handler passed in.
**
**                 Returns true on success, false on failure.
*/

function defineExtCols($handler)
{
    global $app;
    $retVal = false;

    // If the handler is set, we need to check and load any extended
    // columns we may have.  They will be used for inserts.
    if (strlen($handler)) {
        // Based on the name of the handler, we look for a table called
        // Chunks_$handler for "extended" columns for it.
        $tabName = "Chunks_" . $handler;
        $sql = "show tables like '$tabName'";
        $result = mysql_query($sql, $app->acmsDB);
        if ($result) {
            if (mysql_num_rows($result)) {
                // There is an extended attributes table.
                // Fetch any rows from it.
                $sql = "show columns from $tabName";
                $result = mysql_query($sql, $app->acmsDB);
                if ($result) {
                    if (mysql_num_rows($result)) {
                        while ($extRow = mysql_fetch_array($result, MYSQL_ASSOC)) {
                            array_push($this->extCols, $extRow["Field"]);
                            $this->fields[$extRow["Field"]] = "";
                        }
                        $retVal = true;
                        $this->fields["Handler"] = $handler;
                    }
                }
            }
        }
    }

    return $retVal;
}

/*
** setVal - Sets the value of one of our fields, if it exists.
*/

function set($key, $val)
{
    $retVal = false;
    if (isset($this->fields[$key])) {
        $this->fields[$key] = $val;
    }
    return $retVal;
}

/*
** get - Returns the value of a key/column.
*/

function get($key)
{
    $retVal = "";
    if (isset($this->fields[$key])) {
        $retVal = $this->fields[$key];
    }
    return $retVal;
}

/*
** getCategories - Returns any categories we found.
*/

function getCategories()
{
    return $this->chunkCats;
}

/*
** fetch - Fetches a chunk from the database by name.
**
**         If a handler is specified, it must match the name.
**
**         Returns true if we loaded the chunk, false if we didn't.
*/

function fetch($chunkName, $handler = "", $whichACL = "read", $ignoreLoginBit = 0)
{
    global  $app;
    $retVal = false;
    $chunkID = 0;

    //echo "fetch, chunk = '$chunkName', checkLogin = '$ignoreLoginBit'<br>";

    $columns = implode(",", $this->chunkCols);
    $sql = "select $columns from Chunks where ChunkName = '" . mysql_escape_string($chunkName) . "'";
    //echo "$sql<br>";
    if (strlen($handler)) {
        $sql .= " and Handler = '" . mysql_escape_string($handler) . "'";
    }

    $result = mysql_query($sql, $app->acmsDB);
    if ($result) {
        if (mysql_num_rows($result)) {
            // Okay, so far so good.  Fetch the row.
            $curRow = mysql_fetch_array($result);
            // Check the read ACL.
            //echo "<pre>"; print_r ($ignoreLoginBit); echo "</pre>";
            $acl = $app->hasAccess($whichACL, $curRow["Perms"], $curRow["UserID"], $curRow["GroupID"], $ignoreLoginBit);
            if ($acl) {
                $chunkID = $curRow["ChunkID"];
                $this->chunkNameOrig = $curRow["ChunkName"];
                foreach($this->chunkCols as $col) {
                    // echo "Setting fields[$col] to '" . $curRow[$col] . "'<br>";
                    $this->fields[$col] = $curRow[$col];
                }
                $retVal = true;
            } else {
                //echo "Permission denied for '$chunkName' - whichACL = '$whichACL'<br>";
            }
        }
    } else {
        $app->writeLog("Error with query '$sql'");
    }

    // If we were successful at loading the chunk, see if there are any
    // "extended" columns for it.
    if ($retVal) {
        // Based on the name of the handler, we look for a table called
        // Chunks_$handler for "extended" columns for it.
        if ($this->defineExtCols($this->fields["Handler"])) {
            $tabName = "Chunks_" . $this->fields["Handler"];
            $columns = implode(",", $this->extCols);
            $sql = "select $columns from $tabName where ChunkID = $chunkID";
            //echo "$sql<br>";
            $result = mysql_query($sql, $app->acmsDB);
            if ($result) {
                if (mysql_num_rows($result)) {
                    $extRow = mysql_fetch_array($result);
                    foreach($this->extCols as $key) {
                        $this->fields[$key] = $extRow[$key];
                    }
                }
            }
        }
    }

    // Also, get any categories that might be there for this chunk.
    $this->chunkCats = array();
    $sql = "select CategoryID from CategoryItems where ChunkID = $chunkID";
    $result = mysql_query($sql, $app->acmsDB);
    if ($result) {
        if (mysql_num_rows($result)) {
            while($catRow = mysql_fetch_array($result)) {
                array_push($this->chunkCats, $catRow["CategoryID"]);
            }
        }
    }

    return $retVal;
}

/*
** fetchID - Fetches a chunk from the database by name.
**
**           If a handler is specified, it must match the ID.
**
**           Returns true if we loaded the chunk, false if we didn't.
*/

function fetchID($chunkID, $handler = "", $whichACL = "read", $ignoreLoginBit = 0)
{
    global  $app;
    $retVal = false;

    //echo "fetchID, chunk = '$chunkID', checkLogin = '$ignoreLoginBit'<br>";

    $sql = "select ChunkName from Chunks where ChunkID = '" . mysql_escape_string($chunkID) . "'";
    if (strlen($handler)) {
        $sql .= " and Handler = '" . mysql_escape_string($handler) . "'";
    }

    $result = mysql_query($sql, $app->acmsDB);
    if ($result) {
        if (mysql_num_rows($result)) {
            // Okay, so far so good.  Fetch the row.
            $curRow = mysql_fetch_array($result);
            $retVal = $this->fetch($curRow["ChunkName"], $handler, $whichACL, $ignoreLoginBit);
        }
    }
    return $retVal;
}


/*
** parse - This is a wrapper function for the main acmslib parseChunk
**         function.  It searches for pre-rendered content.  If it is found
**         That is passed to the parseChunk with wiki formatting turned off.
*/

function parse()
{
    global $app;
    //return $app->parseChunk($this->fields["Chunk"], 1);
    if (isset($this->fields["Rendered"]) && strlen($this->fields["Rendered"])) {
        return $app->parseChunk($this->fields["Rendered"], 0);
    } else {
        return $app->parseChunk($this->fields["Chunk"], 1);
    }
}

/*
** update - Stores a chunk back into the database.
**
**          THIS FUNCTION WILL NOT WORK FOR INSERTS.  Use insert() instead.
**
**          If preRender is true, then we run the chunk through parseWiki
**          before saving it.
**
**          Returns true or false for success or failure.
*/

function update($preRender = true)
{
    global $app;
    $retVal = false;

    // If the ChunkID isn't set, there is no point in continueing.
    if (!isset($this->fields["ChunkID"])) return false;
    if (!strlen($this->fields["ChunkID"])) return false;

    if ($preRender) {
        // We may also want to parse links and stuff here later as well.
        // We could parse just about anything but references to other
        // chunks or other dynamic content.
        $this->fields["Rendered"] = $app->parseWiki($this->fields["Chunk"]);
    }

    // Create our update statement.
    $upd = "";
    $this->fields['LastModified'] = date("YmdHis");
    foreach($this->chunkCols as $col) {
        if (strcmp("ChunkID", $col)) {
            if (strlen($upd)) {
                $upd .= ",";
            }
            $upd .= "$col = '";
            $upd .= mysql_escape_string($this->fields[$col]);
            $upd .= "'";
        }
    }

    $sql = "update Chunks set $upd where ChunkID = " . $this->fields["ChunkID"];
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->writeLog("chunkClass:update - query '$sql' failed");
        return false;
    }

    // If we made it here, the main chunks table was updated.  See if
    // there is an extended table to work with now.
    if (isset($this->extCols) && is_array($this->extCols) && count($this->extCols)) {
        // Okay.  We passed all the tests.  There is an extended table.
        $tabName = "Chunks_" . $this->fields["Handler"];
        // Create the list of update columns
        $upd = "";
        foreach($this->extCols as $col) {
            if (strcmp("ChunkID", $col)) {
                if (strlen($upd)) {
                    $upd .= ",";
                }
                $upd .= "$col = '";
                $upd .= mysql_escape_string($this->fields[$col]);
                $upd .= "'";
            }
        }
        if (strlen($upd)) {
            $sql = "update $tabName set $upd where ChunkID = " . $this->fields["ChunkID"];
            $result = mysql_query($sql, $app->acmsDB);
            if (!$result) {
                $app->writeLog("chunkClass:update - query '$sql' failed");
                return false;
            }
        }
    }

    // Check to see if we need to update any chunks that refer to this
    // one's old name.
    if (strcmp($this->fields["ChunkName"], $this->chunkNameOrig)) {
        // We do.  Get the list of chunk ID's that we'll be updating.
        $idRay = array();
        $sql = "select ChunkID from Chunks where Chunk LIKE '%%{{" . mysql_escape_string($this->chunkNameOrig) . "%%'";
        $result = mysql_query($sql, $app->acmsDB);
        if ($result) {
            //$app->writeLog("chunkClass:update - Found " . mysql_num_rows($result) . " matches");
            if (mysql_num_rows($result)) {
                // Load up the chunk ID's
                while ($curRow = mysql_fetch_array($result)) {
                    $idRay[] = $curRow["ChunkID"];
                }

                foreach($idRay as $id) {
                    $pregMatches      = array();
                    $pregReplacements = array();
                    $sql = "select Chunk from Chunks where ChunkID = $id";
                    $result = mysql_query($sql, $app->acmsDB);
                    if ($result && mysql_num_rows($result)) {
                        $curRow = mysql_fetch_array($result);
                        $tmpChunk = $curRow["Chunk"];
                        // Extract any non parsable blocks FIRST
                        $npmatch = "/\~np\~(.*?)\~\/np\~/s";
                        while (preg_match($npmatch, $tmpChunk, $matches)) {
                            $repStr = "";
                            //echo "<pre>Found Match 0 = '" . $matches[0] . "', 1 = '" . $matches[1] . "', 2 = '" . $matches[2] . "', 3 = '" . $matches[3] . "'</pre><br>";
                            // Non-parsable stuff will be in $matches[1]
                            if (strlen($matches[1])) {
                                $repStr = md5($matches[1]);
                                // Add it to the layout manager which will put back the
                                // original, unparsed block just before rendering.
                                $match = "/$match/s";
                                array_push($pregMatches,      $match);
                                array_push($pregReplacements, $repl);
                            }
                            $tmpChunk = preg_replace($npmatch, $repStr, $tmpChunk, 1);
                        }
                        
                        // Do the replacements
                        $match = "/(\{\{)(" . $this->chunkNameOrig . ")(\}\}|\/|:)/s";
                        $repl  = "\\1" . $this->fields["ChunkName"] . "\\3";
                        $tmpChunk = preg_replace($match, $repl, $tmpChunk);

                        // Pre-parse the block
                        $parsedBlock = $app->parseWiki($tmpChunk);
                        
                        // Put back any nonparsed blocks
                        if (count($pregMatches)) {
                            $parsedBlock = preg_replace($pregMatches, $pregReplacements, $parsedBlock);
                        }
                        // And then update the chunk in the database.
                        $sql = "update Chunks set Chunk = '" . mysql_escape_string($tmpChunk) . "', Rendered = '" . mysql_escape_string($parsedBlock) . "' where ChunkID = $id";
                        mysql_query($sql, $app->acmsDB);
                    }
                }
            }
        }
    }

    // If we made it to this point, all is well.
    return true;
}

/*
** insert - Stores a new chunk row.  It will also insert for any extra
**          extended tables we have.
**
**          THIS FUNCTION WILL NOT WORK FOR UPDATES.  Use update() instead.
**
**          If preRender is true, then we run the chunk through parseWiki
**          before saving it.
**
**          Returns true or false for success or failure.
*/

function insert($preRender = true)
{
    global $app;
    $retVal = false;

    if ($preRender) {
        // We may also want to parse links and stuff here later as well.
        // We could parse just about anything but references to other
        // chunks or other dynamic content.
        $this->fields["Rendered"] = $app->parseWiki($this->fields["Chunk"]);
    }

    // Create our insert statement.
    $cols = implode(",", $this->chunkCols);
    $vals = "";
    $this->fields['LastModified'] = date("YmdHis");
    foreach($this->chunkCols as $col) {
        if (strlen($vals)) {
            $vals .= ",";
        }
        if (!strcmp("ChunkID", $col)) {
            // Make sure Chunk ID is 0
            $vals .= "0";
        } else {
            $vals .= "'";
            $vals .= mysql_escape_string($this->fields[$col]);
            $vals .= "'";
        }
    }

    $sql = "insert into Chunks ($cols) values ($vals)";
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) return false;

    $insID = mysql_insert_id();
    $this->fields["ChunkID"] = "$insID";

    // If we made it here, the main chunks table was updated.  See if
    // there is an extended table to work with now.
    if (isset($this->extCols) && is_array($this->extCols) && count($this->extCols)) {
        // Okay.  We passed all the tests.  There is an extended table.
        $tabName = "Chunks_" . $this->fields["Handler"];
        // Create our insert statement.
        $cols = implode(",", $this->extCols);
        $vals = "";
        foreach($this->extCols as $col) {
            if (strlen($vals)) {
                $vals .= ",";
            }
            $vals .= "'";
            $vals .= mysql_escape_string($this->fields[$col]);
            $vals .= "'";
        }

        $sql = "replace into $tabName ($cols) values ($vals)";
        //echo "$sql<br>";
        $result = mysql_query($sql, $app->acmsDB);
        if (!$result) return false;
    }

    // If we made it to this point, all is well.
    return true;
}

/*
** categorize - Sets the categories for the chunk.  If no chunk ID is specified
**              it uses the currently loaded chunk.
*/

function categorize($categories, $tmpChunkID = 0)
{
    global $app;
    if (!$tmpChunkID) {
        $tmpChunkID = $this->get("ChunkID");
    }
    if (!$tmpChunkID) {
        return;
    }

    $sql = "delete from CategoryItems where ChunkID = $tmpChunkID";
    $result = mysql_query($sql, $app->acmsDB);
    foreach($categories as $cat) {
        $sql = "insert into CategoryItems (CategoryItemID, CategoryID, ChunkID) values(0, ";
        $sql .= $cat;
        $sql .= ", ";
        $sql .= $tmpChunkID;
        $sql .= ")";
        $result = mysql_query($sql, $app->acmsDB);
        $app->writeLog($sql);
    }
}

/*
** delete - Deletes the specified chunk ID and any matching extended 
**          table entries.
**
**          Returns true on success, false on failure.
*/

function delete($chunkID)
{
    global $app;
    $retVal = false;

    // Get the handler for the specified chunk ID.
    $sql = "select Handler from Chunks where ChunkID = '" . mysql_escape_string($chunkID) . "'";
    $result = mysql_query($sql, $app->acmsDB);
    if ($result) {
        if (mysql_num_rows($result)) {
            $curRow = mysql_fetch_array($result);
            $hndlr = $curRow["Handler"];
            if ($this->defineExtCols($hndlr)) {
                $tabName = "Chunks_" . $hndlr;
                $sql = "delete from $tabName where ChunkID = '" . mysql_escape_string($chunkID) . "'";
                mysql_query($sql, $app->acmsDB);
            }
            $sql = "delete from Chunks where ChunkID = '" . mysql_escape_string($chunkID) . "'";
            $result = mysql_query($sql, $app->acmsDB);
            if (mysql_affected_rows($result)) $retVal = true;
        }
    }
}

/*
** categoryIcon - Returns the icon(s) for the categories that the specified
**                chunk is in.  It returns them in chunk format, so they
**                need to be sent through parseChunk.
*/

function categoryIcon($chunkID, $limit = 0)
{
    global $app;
    $dbConn = $app->newDBConnection();
    $catsAdded = 0;
    $retVal = "";

    $tpl = new acmsTemplate($app->templatePath . "/categoryiconlist.tpl");

    $icons = array();
    $retVal .= "<table align=\"right\">";
    $sql = "select distinct Categories.IconTag from Categories, CategoryItems where CategoryItems.ChunkID = $chunkID and Categories.CategoryID = CategoryItems.CategoryID";
    $result = mysql_query($sql, $dbConn);
    if ($result) {
        if (mysql_num_rows($result)) {
            while ($curRow = mysql_fetch_array($result)) {
                array_push($icons, array(
                            "IconTag" => $app->parseChunk($curRow["IconTag"])
                            ));
                $catsAdded++;
                if ($limit) {
                    if ($catsAdded >= $limit) break;
                }
            }
        }
    }
    $tpl->assign("IconList", $icons);
    if (!$catsAdded) {
        $retVal = "";
    } else {
        $retVal .= $tpl->get();
    }
    return $retVal;
}

};  // class chunk

// Utility functions

/*
** getChunkFormField - Gets the chunk form field from our template and
**                     returns it as a variable.
*/

function getChunkFormField($chunk, $rows = 25, $id = "chunkedit", $name="Chunk")
{
    global $app;

    $htpl = new acmsTemplate($app->templatePath . "/textchunkeditheaders.tpl");
    $htpl->assign("ID",     $id);
    $app->setExtraHeaders($htpl->get());

    $tpl = new acmsTemplate($app->templatePath . "/textchunkeditsection.tpl");
    $tpl->assign("Chunk",  $chunk);
    $tpl->assign("ID",     $id);
    $tpl->assign("Name",   $name);
    $tpl->assign("Rows",   $rows);
    return $tpl->get();
}

?>
