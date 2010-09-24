<?
/**
 * hndlr_menu.php - The menu handler.
 *
 * To display a menu, simply call it as {{MenuName}}.
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
if (eregi('hndlr_menu.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers;

$handlers['menu'] = new menuHandler();

// The menu handler class
class menuHandler {

var $menuStyle;

var $menuItems = array();

/*
** menuHandler - Constructor.  This handles menu chunks.
*/

function menuHandler()
{
    $this->menuStyle = "standard";

} // menuHandler

/*
** getChunk - Gets a menu chunk out of the database, formats it and
**            returns it.
*/

function getChunk($chunk, $args = array())
{
    // We'll use the global database connection for both speed and to
    // keep the total number of database connections down.  This will
    // help it to scale.
    global  $app;
    $retVal = "";

    $menuList  = array();
    $curLevel  = 0;
    $curParent = 0;
    $itemID    = -1;

    $cc = new chunkClass("menu");
    if ($cc->fetch($chunk)) {
        // MARC
        $opts = array(
                'Indent'            => $cc->get("Indent"),
                'ExpandedIndicator' => $cc->get("ExpandedIndicator"),
                'IndicatorSpacer'   => $cc->get("IndicatorSpacer"),
                'ChildIndicator'    => $cc->get("ChildIndicator")
                );

        $retVal = $app->createMenu($cc->get('Chunk'), $opts);
    }

    return $retVal;
}


};  // menuHandler class


?>
