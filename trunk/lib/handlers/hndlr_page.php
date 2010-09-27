<?php
/**
 * hndlr_page.php - The page handler.
 *
 * getChunk creates a link based on the page name.  showChunk displays
 * the page.
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
if (eregi('hndlr_page.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers;

$handlers['page'] = new pageHandler();

// The main handler class
class pageHandler {

/*
** pageHandler - Constructor.  This handles pages.
*/

function pageHandler()
{
} // pageHandler

/*
** getChunk - Creates a link to the page and returns it.
*/

function getChunk($chunk, $args = array())
{
    // We'll use the global database connection for both speed and to
    // keep the total number of database connections down.  This will
    // help it to scale.
    global  $app;
    global  $ACMS_BASE_URI, $ACMSRewrite;
    $retVal = "";

    $cc = new chunkClass();
    if ($cc->fetch($chunk, "page")) {
        if ($app->hasAccess("read", $cc->fields["Perms"], $cc->fields["UserID"], $cc->fields["GroupID"])) {
            // Create the link.
            $linkTag = "";
            $linkTxt = "";
            if (isset($cc->fields["LinkText"])) $linkTxt = $cc->fields["LinkText"];
            if (!strlen($linkTxt)) $linkTxt = $chunk;
            if ($ACMSRewrite) {
                $linkTag = "<a title=\"" . addslashes($cc->get("Title")) . "\" href=\"/" . urlencode($chunk) . "\">";
                //$linkTag .= str_replace(" ", "&nbsp;", $linkTxt);
                $linkTag .= $linkTxt;
                $linkTag .= "</a>";
            } else {
                $linkTag = "<a href=\"$ACMS_BASE_URI?page=" . urlencode($chunk) . "\">";
                $linkTag .= $linkTxt;
                $linkTag .= "</a>";
            }
            $retVal .= $linkTag;
        }
    }


    return $retVal;
}

/*
** showChunk - Pulls the chunk out of the database and shows it to the user.
*/

function showChunk($chunk, $optArgs = array())
{
    // We'll use the global database connection for both speed and to
    // keep the total number of database connections down.  This will
    // help it to scale.
    global  $app, $handlers, $siteConfig;
    $blist = array();
    $gotPage = 0;
    $title   = "";
    $content = "";
    $printable  = 0;
    
    
    //echo "<pre>"; print_r($optArgs); echo "</pre>";
    if (!is_array($optArgs)) {
        $cArgs = $app->parseArgs($optArgs);
        //echo "<pre>"; print_r($cArgs); echo "</pre>";
        if (isset($cArgs["print"])) $printable = 1;
    }

    $cc = new chunkClass();
    if ($cc->fetch($chunk, "page")) {
        if ($app->hasAccess("read", $cc->fields["Perms"], $cc->fields["UserID"], $cc->fields["GroupID"])) {
            $gotPage = 1;
        } else {
            $app->writeLog("Requested page '$chunk' - Permission Denied");
        }
    } else {
        if (!empty($chunk)) {
            ob_end_clean();
            $app->writeLog("Requested page '$chunk' - 404 Not Found");
            header("HTTP/1.1 404 Not Found");
            $app->setPageTitle("Document not found");
            $app->addBlock(0, CONTENT_ZONE, "Page Not Found", "The requested page could not be found.");
            $app->render();
            exit;
        }
    }

    // Check to see if we got the page, if we didn't, load the home page.
    if (!$gotPage) {
        if (!empty($chunk)) {
            $app->writeLog("No page found for '$chunk', redirecting");
            header("Location: /");
            exit;
        } else {
            $this->showChunk("HomePage");
            return;
        }
    }

    // Check for a permanent redirection.  This will be as the very first
    // line in the chunk and be in the form <!-- Redirect: /newuri -->
    // If it is found, issue a 301, moved header and redirect them.
    //$spat = "/^\!\!(.*?)\!\!/s";  // Redirection
    $spat = "/<!-- Redirect: (.*?) -->/s";  // Redirection
    if (preg_match($spat, $cc->fields["Chunk"], $matches)) {
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: " . $matches[1]);
        exit;
    }
    //$spat = "/<!-- Redirect: ([^;])*?-->/"; // HTML comments


    // Check for PhpDig as the first 6 characters of the user agent.
    // If it is, don't give it the print option.
    $givePrintOpt = 0;
    if (eregi('^PhpDig', $_SERVER['HTTP_USER_AGENT'])) {
    // if (!strcmp("PhpDig", substr($_SERVER['HTTP_USER_AGENT'], 0, 6))) {
        $givePrintOpt = 0;
    }


    // If we're printing, set the page template to be the printable one.
    // That contains just the content blocks.
    if ($printable) {
        $app->setPageBlock("PrintablePage");
        $app->setGlobal("Printable", "1");
    }
    $app->setPageTitle($cc->get("Title"));
    // Is there content in this page?  If there is, add it into Zone 2 (content)
    //$content = $app->parseChunk($content);
    $content = $cc->parse();
    if (!empty($content)) {
        // Are we the admin?
        $nav = "";
        if ($app->hasAccess("write", $cc->fields["Perms"], $cc->fields["UserID"], $cc->fields["GroupID"])) {
            $nav = "<a href=\"/admin/EditChunk/" . $cc->get("ChunkID") . "\">Edit</a>&nbsp;";
        }
        if (!$printable && $givePrintOpt) {
            $nav .= "<a href=\"/" . urlencode($chunk) . "/print\"><img alt=\"Print\" title=\"Printable version\" border=\"0\" src=\"/static/icons/print.gif\"></a>";
        }
        $weight = $cc->get("Weight");
        $app->addBlock($weight, CONTENT_ZONE, $cc->fields["Title"], $content, "", array("TitleNav" => $nav));
    }
    
    $app->loadHandler("block");
    $sql = "select Chunks.ChunkName from Chunks, PageBlocks where PageBlocks.PageChunkID = " . $cc->fields["ChunkID"] . " and PageBlocks.BlockChunkID = Chunks.ChunkID";
    //$sql = "select Chunk, Filename, MimeType from Chunks where ChunkName = '$chunk' and Handler = 'image'";
    //echo "query = '$sql'<p>";
    if ($result = mysql_query($sql, $app->acmsDB)) {
        if (mysql_num_rows($result)) {
            while ($curRow = mysql_fetch_array($result)) {
                array_push($blist, $curRow['ChunkName']);
            }

            foreach($blist as $blockName) {
                // echo "Adding block '$blockName'<p>";
                $handlers["block"]->showChunk($blockName);
                //$app->showBlock($blockName);
            }
        }
    }

    // If we were printable, then add a block at the bottom of the page 
    // to show where things came from and how to get back.
    if ($printable) {
        $fBlock  = "\n----\n{{" . $chunk . "}}, printed from ";
        $fBlock .= "[[http://" . $_SERVER['SERVER_NAME'] . " " . $siteConfig['SiteName'] . "]]";
        $app->addBlock(500, FOOTER_ZONE, "", $app->parseChunk($fBlock, 1));
    }
    
    $app->render();
}


};  // pageHandler class


?>
