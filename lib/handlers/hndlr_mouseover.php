<?php
/**
 * hndlr_mouseover.php - Mouseover handler.
 *
 * When a user puts their mouse over the Title/Link it gives an overlib
 * popup with the text in the chunk content.
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
if (eregi('hndlr_mouseover.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers;


// The main ACMS class
class mouseoverHandler {

/*
** mouseoverHandler - Constructor.  This handles extra info chunks.
*/

function mouseoverHandler()
{
} // mouseoverHandler

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
    $style  = "";
    if (count($args)) {
        // We only care about the first argument.
        if (isset($args['style'])) {
            $style = $args['style'];
        }
    }

    $cc     = new chunkClass();

    if ($cc->fetch($chunk, "mouseover")) {
        $tpl = new acmsTemplate($app->templatePath . "mouseover.tpl");
        // Escape our chunk text to fit it into the javascript tags
        $tmpChunk = $cc->get("Chunk");
        //$tmpChunk = str_replace("'", "&#39;", $tmpChunk);
        $tmpChunk = str_replace("'", "\\'", $tmpChunk);
        $tmpChunk = str_replace("\"", "&quot;", $tmpChunk);
        $tmpChunk = str_replace("(", "&#40;", $tmpChunk);
        $tmpChunk = str_replace(")", "&#41;", $tmpChunk);

        $tpl->assign("ChunkTitle",   $cc->get("Title"));
        $tpl->assign("ChunkName",    $cc->get("ChunkName"));
        $tpl->assign("Chunk",        $tmpChunk);
        $tpl->assign("Style",        $style);

        $retVal .= $app->parseChunk($tpl->get());
    }

    return $retVal;
}

};  // mouseoverHandler class

$handlers['mouseover'] = new mouseoverHandler();

?>
