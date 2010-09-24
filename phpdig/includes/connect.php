<?php
/*
--------------------------------------------------------------------------------
PhpDig Version 1.8.x
This program is provided under the GNU/GPL license.
See the LICENSE file for more information.
All contributors are listed in the CREDITS file provided with this package.
PhpDig Website : http://www.phpdig.net/
--------------------------------------------------------------------------------
*/

// Connection configuration
if (!defined('PHPDIG_DB_NAME')) { // do not change this line

define('PHPDIG_DB_PREFIX','phpdig_');
if (eregi('corp.blarg.net', $_SERVER['SERVER_NAME'])) {
    define('PHPDIG_DB_HOST','localhost');
    define('PHPDIG_DB_USER','ACMS');
    define('PHPDIG_DB_PASS','Xk32aQDr');
    define('PHPDIG_DB_NAME','ACMS');
} else {
    define('PHPDIG_DB_HOST','206.124.128.81');
    define('PHPDIG_DB_USER','ACMS');
    define('PHPDIG_DB_PASS','Xk32aQDr');
    define('PHPDIG_DB_NAME','ACMS');
}

} // do not change this line

//connection to the MySql server
$id_connect = @mysql_connect(PHPDIG_DB_HOST,PHPDIG_DB_USER,PHPDIG_DB_PASS);
if (!$id_connect) {
die("Unable to connect to database : Check the connection script.\n");
}

//Select DataBase
$db_select = @mysql_select_db(PHPDIG_DB_NAME,$id_connect);
if (!$db_select) {
die("Unable to select the database : Check the connection script.\n");
}
?>
