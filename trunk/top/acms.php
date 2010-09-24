<?
/*
 * acms.php -  This is the main program body besides the index.
 *             It handles calling of modules and other things.
 *
 */
// Setup our include path.  We will only load things with a full path,
// and only based on what the server tells us about.
$libdir = substr($_SERVER['DOCUMENT_ROOT'], 0, strrpos($_SERVER['DOCUMENT_ROOT'], "/")) . "/lib/";
require_once($libdir . "acmslib.php");

// To run, all we need to do is create the application object and
// call the exec function.  It will do the rest.
$app = new acmsApp();
$app->exec();
?>
