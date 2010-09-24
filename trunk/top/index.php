<?
/* $Id: index.php,v 1.2 2004/05/25 19:36:20 marc Exp $
**
** index.php - This is the main CMS entry point.  It takes care of 
**             displaying the main page to the visitor.
**
*/

// Setup our include path.  We will only load things with a full path,
// and only based on what the server tells us about.
$libdir = substr($_SERVER['DOCUMENT_ROOT'], 0, strrpos($_SERVER['DOCUMENT_ROOT'], "/")) . "/lib/";
require_once($libdir . "acmslib.php");

$app = new acmsApp();
//$app->destroySession();
//$app->addHeader("Header Title", $app->parseChunk("{{Header}}"));
//$app->addInfo("Content Title", "Info content.");
//$app->addContent("Content Title", $app->parseChunk("{{TestContent}}<p>{{SampleImage}}"));
if ($app->loggedIn) {
    $app->addInfo("Hello!", "Welcome to Another Content Management System, " . $app->acmsSessVars['LoginID'] . ".  We hope you enjoy your visit.");
}
$app->setupMainPage();
//$app->render();

    // The only thing that really should be here is:
    // require_once "libacms.php";
    // $app = new acms();
    // $app->setupMainPage();
    // $app->render();
?>

