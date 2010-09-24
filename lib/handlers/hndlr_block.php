<?php
/**
 * hndlr_block.php - The block handler.
 *
 *                   The block handler is fairly straight forward.
 *                   showChunk creates the requested block.
 *                   getChunk simply calls showChunk.
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
if (eregi('hndlr_block.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers;

$handlers['block'] = new blockHandler();

// The main handler class
class blockHandler {

/*
** blockHandler - Constructor.  This handles blocks.
*/

function blockHandler()
{
} // blockHandler

/*
** showChunk - Adds the block to the page.
*/

function showChunk($chunk)
{
    // We'll use the global database connection for both speed and to
    // keep the total number of database connections down.  This will
    // help it to scale.
    global  $app;
    global  $ACMS_BASE_URI, $ACMSRewrite;
    $retVal = "";

    $cc = new chunkClass();
    if ($cc->fetch($chunk, "block")) {
        if ($app->hasAccess("read", $cc->fields["Perms"], $cc->fields["UserID"], $cc->fields["GroupID"])) {
            // Are we the admin?
            $nav = "";
            if ($app->hasAccess("write", $cc->fields["Perms"], $cc->fields["UserID"], $cc->fields["GroupID"])) {
                $nav = "<a href=\"/admin/EditChunk/" . $cc->get("ChunkID") . "\">Edit</a>";
            }

            // Add the block to the page.
            $weight = $cc->get('Weight');
            $app->addBlock(
                    $weight,
                    $cc->get("Zone"), 
                    $app->parseChunk($cc->get("Title")),
                    $app->parseChunk($cc->get("Chunk")),
                    $app->parseChunk($cc->get("Footer")),
                    array(
                        'TitleNav'  => $app->parseChunk($cc->get("TitleNav") . $nav),
                        'FooterNav' => $app->parseChunk($cc->get("FooterNav"))
                        )
                    );
        }
    }

    return $retVal;
}


function getChunk($chunk)
{
    return $this->showChunk($chunk);
}

};  // blockHandler class


?>
