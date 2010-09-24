<?php
/**
 * hndlr_text.php - The text/html handler.
 *
 * The text/html handler is the most basic handler.
 * It does no parsing and nothing special.  It just pulls data out of the
 * database and returns it.
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
if (eregi('hndlr_text.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers;

$handlers['text'] = new textHandler();

// The main ACMS class
class textHandler {

/*
** textHandler - Constructor.  This handles text and HTML chunks.
*/

function textHandler()
{
} // textHandler

/*
** getChunk - Gets a chunk of text out of the database and returns it.
*/

function getChunk($chunk, $args = array())
{
    // We'll use the global database connection for both speed and to
    // keep the total number of database connections down.  This will
    // help it to scale.
    global  $app;
    $retVal = "";

    $cc     = new chunkClass();

    if ($cc->fetch($chunk, "text")) {
        $retVal .= $cc->parse();
    }

    return $retVal;
}



};  // textHandler class


?>
