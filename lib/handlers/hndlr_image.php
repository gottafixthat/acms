<?php
/**
 * hndlr_image.php - An image handler.
 *
 * The image handler is fairly straight forward.  getChunk creates an
 * <img ...> tag based on the tag name.  showChunk dumps the actual image
 * and is called externally.
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
if (eregi('hndlr_image.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers;

$handlers['image'] = new imageHandler();

// The main handler class
class imageHandler {

/*
** imageHandler - Constructor.  This handles image chunks.
*/

function imageHandler()
{
} // imageHandler

/*
** getChunk - Creates an img tag reference to an image chunk from the database
**            and returns it.
*/

function getChunk($chunk, $args = array())
{
    // We'll use the global database connection for both speed and to
    // keep the total number of database connections down.  This will
    // help it to scale.
    global  $app, $siteConfig;
    global  $ACMS_BASE_URI, $ACMSRewrite;
    $retVal = "";
    $attrs  = "";

    if (isset($args["align"])) {
        if (strlen($attrs)) $attrs .= " ";
        $attrs .= "align=\"" . $args["align"] . "\"";
    }

    if (isset($args["class"])) {
        if (strlen($attrs)) $attrs .= " ";
        $attrs .= "class=\"" . $args["class"] . "\"";
    }

    if (isset($args["alt"])) {
        if (strlen($attrs)) $attrs .= " ";
        $attrs .= "alt=\"" . $args["alt"] . "\"";
    }


    $cc = new chunkClass('image');
    if ($cc->fetch($chunk)) {
        // Create the <img> tag.
        $imgTag = "";
        $srcURI = "/chunk/" . $chunk . "/" . $cc->fields['Filename'];
        if (!$cc->get("StoreInDB")) {
            $srcURI = $siteConfig['ImagePath'] . $cc->get("Filename");
        } else {
            if (!$ACMSRewrite) {
                $imgTag = "<img $attrs border=\"0\" src=\"$ACMS_BASE_URI?chunk=" . $chunk . "&dn=/" . $cc->fields['Filename'] . "\">";
            }
        }
        if ((int)$cc->get("Width") > 0) {
            if (strlen($attrs)) $attrs .= " ";
            $attrs .= "width=\"" . $cc->get("Width") . "\"";
        }
        if ((int)$cc->get("Height") > 0) {
            if (strlen($attrs)) $attrs .= " ";
            $attrs .= "height=\"" . $cc->get("Height") . "\"";
        }
        $imgTag = "<img $attrs border=\"0\" src=\"$srcURI\">";
        /*
        if ($ACMSRewrite) {
            $imgTag = "<img $attrs border=\"0\" src=\"/chunk/" . $chunk . "/" . $cc->fields['Filename'] . "\">";
        } else {
            $imgTag = "<img $attrs border=\"0\" src=\"$ACMS_BASE_URI?chunk=" . $chunk . "&dn=/" . $cc->fields['Filename'] . "\">";
        }
        */
        $retVal .= $imgTag;
    }
    return $retVal;
}

/*
** showChunk - Pulls the image out of the database and shows it to the user.
*/

function showChunk($chunk, $args = array())
{
    // We'll use the global database connection for both speed and to
    // keep the total number of database connections down.  This will
    // help it to scale.
    global  $app;

    $cc = new chunkClass('image');
    if ($cc->fetch($chunk)) {
        $decoded = base64_decode($cc->get('Chunk'));
        header("Content-type: " . $cc->get('MimeType') . "; name=\"" . $cc->get('Filename') . "\"");
        header("Content-Disposition: inline; filename=\"" . $cc->get('Filename') . "\"");
        header("Content-length: " . strlen($decoded));
        // Now, decode the base-64 encoded chunk and dump it.

        echo "$decoded";
        // ob_flush();
        $app->writeLog();
    }
}


};  // imageHandler class


?>
