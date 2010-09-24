<?php
/**
 * acmsmodule.php - Base module class.
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


/**
  * acmsModule class.  Base class and common functions that are required
  * for all modules.
  *
  * @author     Marc Lewis <marc@cheetahis.com>
  * @version    0.0
  */
class acmsModule {

var     $myURI;
var     $templatePath;

/*
 * Constructor for the acmsModule class.
 *
 * @author      Marc Lewis <marc@cheetahis.com>
 */

function acmsModule()
{
    $this->myURI = $_SERVER['REQUEST_URI'];
}

};

?>
