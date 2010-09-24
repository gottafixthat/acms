<?php
/**
 * acmslib.php - The main application class.  Where all the magic happens.
 *
 **************************************************************************
 * Written by R. Marc Lewis, 
 *   Copyright 1998-2010, R. Marc Lewis (marc@CheetahIS.com)
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


error_reporting(E_ALL);
//ob_start();
//ob_implicit_flush(0);

// Our "pound defines"
define("HEADER_ZONE",   0);
define("INFO_ZONE",     1);
define("CONTENT_ZONE",  2);
define("EXTRA_ZONE",    3);
define("FOOTER_ZONE",   4);
define("SECHEAD_ZONE",  5);

define("ACMS_DEBUG",    1);

define("SYSTEM_INI",    "/usr/local/etc/acms.ini");

// Paths where we store things
define('SMARTY_CACHE',              "/var/lib/acms/cache");
define('SMARTY_COMPILE_DIR',        "/var/lib/acms/templates_c");


// This is the global array of handlers.  When a handler is instantiated,
// it will store itself in this.
global $handlers;
global $modules;
global $admplugins;

global $ACMSVersion;
$ACMSVersion = "0.6.0";

global $ACMSCfg;

global $ACMSRewrite;
$ACMSRewrite = 1;

// First, make sure they didn't try loading us directly.
if (eregi('acmslib.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// Setup output buffering
ob_start();
//ob_implicit_flush(0);

// Set our global include directory.
global  $INCLUDE_DIR;
$INCLUDE_DIR = dirname(__FILE__) . "/";
ini_set('include_path', $INCLUDE_DIR . ":" . $INCLUDE_DIR . "pear:" . ini_get('include_path'));

global  $ACMS_BASE_URI;
$ACMS_BASE_URI = "/acms.php";

// Some other options
global  $siteConfig;
$siteConfig = array();
// FIXME: These should be configurable from a file somewhere.
global  $autoAddAuthedUsers;
$autoAddAuthedUsers = true;

// Authentication methods.
// They are tried in order, and use a plugin.
// The plugins are in the directory plugins under the main lib directory,
// and are prefixed with auth_ then the contents of the methods, i.e. ldap, 
// mysql, pam, etc.  The plugins are very simple, and have just a single
// function called $METHOD_checkLogin($username, $password) and they
// return true or false, whether the authentication was successful.  
// If one returns false, then the next one in line is checked.
global  $AUTH_METHODS;
$AUTH_METHODS = array('ldap');

// Get the Chunk class
require_once($INCLUDE_DIR."chunklib.php");
// Load the BLayout class
require_once("blayout.php");
if(!class_exists('BLayout')) die("Couldn't find the BLayout class");

require_once("class.inputfilter.php");

// The main ACMS class
class acmsApp extends BLayout {

// "Private" variables
var $acmsDB;
var $sysChunkVars;

// Session Variables
var $acmsSessionID;
var $acmsSessionKey;
var $acmsSessKeys;
var $acmsSessVars;
var $sessCookieName = "ACMSSESSID";
var $sessCookieKey  = "ACMSSESSKEY";

var $isAdmin = false;

var $pregCount = 0;

// User/Group Caches
var $UserIDCache;
var $GroupIDCache;

var $loggedIn;
var $doPersistantBlocks = 1;

// More variables
var $startTime;
var $chunksParsed;      // An array containing the chunks we have parsed
var $chunksParsedID;

var $templatePath;

// These are actually private variables, but here for simplicity's sake
// Realistically, they should be read from the .cf file...Perhaps later.
var $acmsDBHost = "";
var $acmsDBName = "";
var $acmsDBUser = "";
var $acmsDBPass = "";

var $compressOutput = 0;

/*
** acmsApp - Constructor.  Sets up the necessary stuff in the class for
**           its run.
*/

function acmsApp()
{
    global  $INCLUDE_DIR, $ACMSVersion, $HTTP_COOKIE_VARS, $ACMSRewrite;
    global  $siteConfig, $ACMSCfg;

    // Start our timer
    $this->startTime = microtime();

    // Load our INI file before doing anything else.
    $inifile = $this->findConfig();
    if (strlen($inifile)) {
        $ACMSCfg = parse_ini_file($inifile, true);
    } else {
        $this->writeLog("Unable to load '$usrini' or '" . SYSTEM_INI . "'");
        die("1 - Unable to load system configuration<BR>");
    }

    // Start the beginning of the session
    $this->writeLog("B");

    // Clean our request variables
    $this->parseURL();
    $this->request = $this->cleanRequest();

    //echo "<pre>" ; print_r($ACMSCfg); echo "</pre>";
    //MARC
    $this->loggedIn = false;
    $this->chunksParsed   = array();
    $this->chunksParsedID = array();

    $this->acmsDBHost = "localhost";
    $this->acmsDBName = "ACMS";
    $this->acmsDBUser = "ACMS";
    $this->acmsDBPass = "";

    // Reset our configuration based on our ini file.
    if (isset($ACMSCfg['Database']['DBHost'])) {
        $this->acmsDBHost = $ACMSCfg['Database']['DBHost'];
    }
    if (isset($ACMSCfg['Database']['DBName'])) {
        $this->acmsDBName = $ACMSCfg['Database']['DBName'];
    }
    if (isset($ACMSCfg['Database']['DBUser'])) {
        $this->acmsDBUser = $ACMSCfg['Database']['DBUser'];
    }
    if (isset($ACMSCfg['Database']['DBPass'])) {
        $this->acmsDBPass = $ACMSCfg['Database']['DBPass'];
    }

    if (isset($ACMSCfg['General']['ErrorReporting'])) {
        error_reporting($ACMSCfg['General']['ErrorReporting']);
    } else {
        error_reporting(0);
    }
    $this->compressOutput = 0;
    if (isset($ACMSCfg['General']['CompressOutput'])) {
        $this->compressOutput = $ACMSCfg['General']['CompressOutput'];
    }
    $this->compressOutput = 0;


    // Create our database connection
    if (!$this->acmsDB = mysql_pconnect($this->acmsDBHost, $this->acmsDBUser, $this->acmsDBPass)) {
        $this->writeLog("Unable to communicate with DB Host '" . $this->acmsDBHost . "' as '" . $this->acmsDBUser . "'");
        die("1 - Unable to connect to the database host<BR>");
    }
    // Now, select the database
    //print "DBName = '$this->acmsDBName'<BR>";
    if (!@mysql_select_db($this->acmsDBName, $this->acmsDB)) {
        die("2 - Unable to connect to the database '$this->acmsDBName'<BR>");
    }

    $siteConfig['ImagePath'] = "/images/";
    $this->loadConfig();
    $this->setSiteName($siteConfig['SiteName']);

    // Setup the template system and then update the
    // configurations based on the ini file.
    $this->templatePath = $INCLUDE_DIR . "templates/";
    if (isset($ACMSCfg['Templates']['TemplatePath'])) {
        $this->templatePath = $ACMSCfg['Templates']['TemplatePath'];
        // Add a trailing / if there isn't one.
        if (strrpos($this->templatePath, "/") != strlen($this->templatePath) -1) {
            $this->templatePath .= "/";
        }
    }
    $stylesheet = "style.css";
    if (isset($ACMSCfg['Templates']['StyleSheet'])) {
        $stylesheet = $ACMSCfg['Templates']['StyleSheet'];
    }
    if (eregi("MSIE", $_SERVER['HTTP_USER_AGENT'])) {
        $stylesheet = "style-ie.css";
        if (isset($ACMSCfg['Templates']['StyleSheetIE'])) {
            $stylesheet = $ACMSCfg['Templates']['StyleSheetIE'];
        }
    } else if (eregi("Mozilla/4.7", $_SERVER['HTTP_USER_AGENT'])) {
        $stylesheet = "style-ns4.css";
        if (isset($ACMSCfg['Templates']['StyleSheetNS4'])) {
            $stylesheet = $ACMSCfg['Templates']['StyleSheetNS4'];
        }
    }
    $this->writeLog("templatePath = '" . $this->templatePath . "'");
    $tmpTemplatePath = $this->templatePath;

    $this->BLayout();
    $this->setTemplatePath($tmpTemplatePath);
    $this->setTemplate("acmsmain.tpl", "/styles/$stylesheet");
    $this->setGlobal("ACMSVersion", $ACMSVersion);
    
    $this->sysChunkVars['ACMSVersion'] = $ACMSVersion;
    $this->sysChunkVars['LoginID']     = 'Anonymous';
    if ($ACMSRewrite) {
        $this->sysChunkVars['LogoutURL']   = "/ia/logout";
    } else {
        $this->sysChunkVars['LogoutURL']   = "/acms.php?ia=logout";
    }

    
    if (isset($HTTP_COOKIE_VARS[$this->sessCookieName])) {
        $sql = "SELECT * from Sessions WHERE SessionID = '" . $HTTP_COOKIE_VARS[$this->sessCookieName] . "'";
        //print "Query = '$sql'<BR>";
        if ($result = mysql_query($sql, $this->acmsDB)) {
            // So far, so good.  The query at least succeeded
            if ($curRow = mysql_fetch_array($result)) {
                //print "Raw session info:<BR><PRE>";
                //print $curRow["SessionData"];
                //print "</PRE><BR><HR>";

                // Now, base 64 decode the session.
                //print "Decoded session data:<P><PRE>";
                //print base64_decode($curRow["SessionData"]);
                //print "</PRE><BR><HR>";

                // Now, explode the session data into multiple lines
                $lines = explode("\n", base64_decode($curRow["SessionData"]));
                // Loop through each of the lines and split things out into
                // their own variables
                for ($i = 0; $i < count($lines); $i++) {
                    // print "Line $i = '$lines[$i]'<BR>";
                    $keyval = explode("\t", $lines[$i], 2);
                    //print "Key = '$keyval[0]', Val = '$keyval[1]'<BR>";
                    if (isset($keyval[0]) && isset($keyval[1])) {
                        $this->acmsSessKeys[$i] = $keyval[0];
                        $this->acmsSessVars[$keyval[0]] = ereg_replace("\{NEWLINE\}", "\n", $keyval[1]);
                    }
                }

            } else {
                //syslog(LOG_INFO, "No session information was found.");
                //print "No session information was found.<BR>";
                $this->startSession();
                $this->loadUserInfo();
                //$this->showLoginPage();
                //die();
                return;
                //return false;
            }
        }
    } else {
        //syslog(LOG_INFO, "No BCGI Session ID cookie found.");
        //print "No BCGI Session ID cookie found.<BR>";
        $this->startSession();
        $this->loadUserInfo();
        //$this->showLoginPage();
        //die();
        return;
        // return false;
    }
    
    if (isset($this->acmsSessVars["SessionKey"]) && strcmp($this->acmsSessVars["SessionKey"], $HTTP_COOKIE_VARS[$this->sessCookieKey])) {
        syslog(LOG_INFO, "Invalid session key detected!");
        // print "Invalid session key!<BR>";
        unset($this->acmsSessionID);
        unset($this->acmsSessionKey);
        $this->startSession();
        $this->loadUserInfo();
        //$this->showLoginPage();
        //die();
        return;
    } else {
        //print "Session valid and loaded.<BR>";
        $this->acmsSessionID  = $HTTP_COOKIE_VARS[$this->sessCookieName];
        $this->acmsSessionKey = $HTTP_COOKIE_VARS[$this->sessCookieKey];
        //$this->acmsSessionKey = $this->acmsSessVars["SessionKey"];
        // If there is a login ID set, we are logged in.
        //syslog(LOG_INFO, "Checking for a login...");
        if (empty($this->acmsSessVars["LoginID"])) {
            // Check to see if there is any login information in the
            // post.  If so, try logging them in.
            if ($this->checkLogin()) {
                $this->loggedIn = true;
                $this->sysChunkVars['LoginID'] = $this->acmsSessVars["LoginID"];
                //$this->loadPreferences();
            } else {
                //$this->showLoginPage();
                $this->loggedIn = false;
            }
        } else {
            $this->loggedIn = true;
            $this->sysChunkVars['LoginID'] = $this->acmsSessVars["LoginID"];
            // FIXME: Maybe.  Having it check for isGroupMember should be fine.
            if ($this->isGroupMember(0)) $this->isAdmin = true;
            //$this->loadPreferences();
        }
        //return true;
    }

    if(isset($this->acmsSessVars["LastSearch"])) {
        $this->sysChunkVars["LastSearch"] = $this->acmsSessVars["LastSearch"];
    }


} // acmsApp

/* findConfig - Finds the configuration file for this instance of ACMS.
 * It starts by looking at the directory above the document root for an
 * .acms.ini file, if its not found, it looks in /usr/local/etc for the 
 * hostname.ini, and keeps shortening the hostname until it finds one.
 *
 * @return The file name of the configuration file or an empty string
 * if none were found.
 */
function findConfig()
{
    $retVal = "";
    $foundOne = false;

    $usrini = substr($_SERVER['DOCUMENT_ROOT'], 0, strrpos($_SERVER['DOCUMENT_ROOT'], "/")) . "/.acms.ini";
    //$this->writeLog("Checking for ini file '$usrini'");
    if (file_exists($usrini) && is_readable($usrini)) {
        $retVal = $usrini;
        $foundOne = true;
    }

    $sName = $_SERVER['SERVER_NAME'];
    while (strpos($sName, ".") > 0 && !$foundOne) {
        $usrini = "/usr/local/etc/acms-" . $sName . ".ini";
        //$this->writeLog("Checking for ini file '$usrini'");
        if (file_exists($usrini) && is_readable($usrini)) {
            $retVal = $usrini;
            $foundOne = true;
        } else {
            $sName = substr($sName, strpos($sName, ".")+1, strlen($sName));
        }
    }
   
    if (!$foundOne) {    
        // Load the system ini file instead
        if (file_exists(SYSTEM_INI) && is_readable(SYSTEM_INI)) {
            $retVal = SYSTEM_INI;
            $foundOne = true;
        }
    }

    return $retVal;
}

//  loadConfig
//! Loads the site specific configuration values from the database.

function loadConfig()
{
    global  $siteConfig;

    $sql = "select CfgName, CfgVal from SiteConfig";
    $result = mysql_query($sql, $this->acmsDB);
    if ($result) {
        if (mysql_num_rows($result)) {
            while ($curRow = mysql_fetch_array($result)) {
                $siteConfig[$curRow['CfgName']] = $curRow['CfgVal'];
            }
        }
    }

    // Set a few defaults if they aren't already set.
    if (!isset($siteConfig['SiteName']))    $siteConfig['SiteName'] = "ACMS";
}

/*
** newDBConnection - Creates a new database connection.  Returns the
**                   connection.  If it can't create a new connection
**                   things stop here.
*/

function newDBConnection()
{
    $newConn = 0;
    // Create our database connection
    if (!$newConn = mysql_pconnect($this->acmsDBHost, $this->acmsDBUser, $this->acmsDBPass)) {
        die("1 - Unable to connect to the database host<BR>");
    }
    // Now, select the database
    //print "DBName = '$this->acmsDBName'<BR>";
    if (!@mysql_select_db($this->acmsDBName, $newConn)) {
        die("2 - Unable to connect to the database '$this->acmsDBName'<BR>");
    }
    
    return $newConn;
}

/*
** startSession - Sets up our session.
*/

function startSession()
{
    global $HTTP_COOKIE_VARS;

    // Check to see if they have a session cookie set.
    if (!isset($this->acmsSessionID)) {
        // No session cookie found.

        // Do a quick cleanup of any sessions that are more than 7 days old
        $sql = "delete from Sessions where LastMod < subdate(current_timestamp(), interval 7 day)";
        $result = mysql_query($sql, $this->acmsDB);

        // Generate a "unique" session ID for this user and store it in 
        // the database.  We make our key unique by reading data from 
        // /dev/urandom and then performing an MD5 sum on it.  If there is
        // no /dev/urandom available, we will use the current time in ms
        // to generate the session ID with.
        $buf = "";
        $fp = fopen("/dev/urandom", "rb");
        if ($fp) {
            $buf = fread($fp, 128);
        } else {
            // Couldn't open /dev/urandom.  Use the current time instead.
            $buf = microtime();
        }
        $sessVal = md5($buf);

        // Now, create the key for it.
        if ($fp) {
            $buf = fread($fp, 128);
        } else {
            // Couldn't open /dev/urandom.  Use the current time instead.
            $buf = microtime();
        }
        $sessKey = md5($buf);

        // Now, set the cookies.
        //setcookie($this->sessCookieName, $sessVal, time()+CCC_COOKIE_LIFE, "/");
        //setcookie($this->sessCookieKey,  $sessKey, time()+CCC_COOKIE_LIFE, "/");
        setcookie($this->sessCookieName, $sessVal);
        setcookie($this->sessCookieKey,  $sessKey);

        // Now, create this session in the database.  This will be done 
        // automagically.
        $this->acmsSessionID  = $sessVal;
        $this->acmsSessionKey = $sessKey;
        $this->setSessVar("SessionKey", $sessKey);

        // Save the referrer.
        $this->setSessVar("OriginalReferrer", $_SERVER['HTTP_REFERER']);

    } else {
        // They do have a cookie set, make sure it is valid.
        // This is done by running it through a few tests.
        
        // A session ID should be exactly 32 characters long, and should
        // only contain lower-case hex characters.
        $goodSessID = true;
        if (!empty($this->acmsSessionID) && strlen($this->acmsSessionID) != 32) $goodSessID = false;

        // Now, check for the hex characters
        if (!empty($this->acmsSessionID) && !ereg("[0-9,a-f]{32}", $this->acmsSessionID)) $goodSessID = false;

        if (!$goodSessID) {
            // Bad session.  Kill it and start over.
            // Find their cookies and remove them.
            unset($HTTP_COOKIE_VARS[$this->sessCookieName]);
            $this->startSession();
        }
    }
}

/*
** destroySession - Removes all session information.
*/

function destroySession()
{
    $sql = "delete from Sessions where SessionID = '" . $this->acmsSessionID . "'";
    $result = mysql_query($sql, $this->acmsDB);
    if (!$result) {
        $this->writeLog("Error destroying session '" . $this->acmsSessionID . "'!");
    }
    //setcookie($this->sessCookieName, "", time()-1, "/");
    //setcookie($this->sessCookieKey,  "", time()-1, "/");
    setcookie($this->sessCookieName, "");
    setcookie($this->sessCookieKey,  "");
}

/*
** checkLogin - If the user is not logged in, this function is called
**              in an attempt to log them in.  It searches for the POST
**              form variables "username" and "password".  If they exist,
**              it checks the appropriate auth plugins and tries to log
**              the user in using each one of them.
*/

function checkLogin()
{
    global  $AUTH_METHODS, $INCLUDE_DIR;
    $retVal = false;

    if (isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
        // Both a username and password were given.  Try to authenticate.
        $login = ereg_replace("[^a-zA-Z0-9\._-]", "", $_REQUEST['username']);
        $pw    = $_REQUEST['password'];
        
        foreach($AUTH_METHODS as $method) {
            // Load the module and pass control to it.
            $method = eregi_replace("[^a-z0-9]", "", $method);
            $plugin = $INCLUDE_DIR . "plugins/auth_$method.php";
            if (file_exists($plugin) && is_readable($plugin)) {
                // Okay, it exists and we can read it.
                // Load up the plugin and check to make sure that the
                // auth function exists.
                if (ACMS_DEBUG) {
                    $this->writeLog("Loading auth plugin '$plugin'");
                }
                require_once($plugin);
                $authName = $method . "_checkLogin";
                if (function_exists($authName)) {
                    // Call the auth function and store its result
                    $funcRet = $authName($login, $pw);
                    if ($funcRet) {
                        // The user authenticated with this method.
                        // Store our return value and break out of our
                        // loop.
                        $retVal = true;
                        $this->setSessVar("LoginID", strtolower($login));
                        $this->setSessVar("Password", $pw);
                        $this->loadUserInfo();
                        break;
                    }
                }
            }
        }
    } 

    return $retVal;
}

/*
** loadUserInfo - Loads the user's information into their session variables.
*/

function loadUserInfo()
{
    global $autoAddAuthedUsers;

    $userID  = -1;
    $groupID = -1;

    if (isset($this->acmsSessVars["LoginID"])) {
        $escLogin = mysql_escape_string($this->acmsSessVars["LoginID"]);
        $sql = "select UserID, GroupID from Users where UserName = '$escLogin'";
        $result = mysql_query($sql, $this->acmsDB);
        if ($result) {
            if (mysql_num_rows($result)) {
                $userRow = mysql_fetch_array($result);
                $userID  = $userRow["UserID"];
                $groupID = $userRow["GroupID"];
            } else {
                if ($autoAddAuthedUsers) {
                    // Add the user into the database since we authenticated
                    // from an external source.
                    $sql = "insert into Groups (GroupID, GroupName) values (0, '$escLogin')";
                    if (ACMS_DEBUG) {
                        $this->writeLog($sql);
                    }
                    $insRes = mysql_query($sql, $this->acmsDB);
                    $groupID = mysql_insert_id();
                    $sql = "insert into Users (UserID, UserName, GroupID) values (0, '$escLogin', $groupID)";
                    if (ACMS_DEBUG) {
                        $this->writeLog($sql);
                    }
                    $insRes = mysql_query($sql, $this->acmsDB);
                    $userID = mysql_insert_id();
                }
            }
        } else {
            if (ACMS_DEBUG) {
                $this->writeLog($sql);
            }
        }
    }

    $this->setSessVar("UserID",     $userID);
    $this->setSessVar("GroupID",    $groupID);
    $this->sysChunkVars["UserID"]  = $userID;
    $this->sysChunkVars["GroupID"] = $groupID;
}

/*
** logout - Destroys the session and logs the user out
*/

function logout()
{
    $this->writeLog("Destroying session and logging out...");
    // Get rid of the session information
    $this->destroySession();
    // And take them back to the main page.
    header("Location: /");
    exit;
    //$this->setupMainPage();
}

/*
** setSessVar - Sets a session variable and writes the session into 
**              the database.
*/

function setSessVar($key, $val)
{
    //$this->writeLog("Setting session variable $key/$val for session " . $this->acmsSessionID);
    // global $HTTP_COOKIE_VARS;
    //if (!$this->loggedIn) return;
    // Check to see if the session variable already exists.  If so, 
    // We just need to update it.  If not, we need to create a new one.
    $val = ereg_replace("\n", "{NEWLINE}", $val);
    if (isset($this->acmsSessVars[$key])) {
        $this->acmsSessVars[$key] = $val;
    } else {
        if (isset($this->acmsSessKeys)) {
            $this->acmsSessKeys[count($this->acmsSessKeys)] = $key;
            $this->acmsSessVars[$key] = $val;
        } else {
            $this->acmsSessKeys[0] = $key;
            $this->acmsSessVars[$key] = $val;
        }
    }

    // Now walk through the keys and create our tab/newline separated
    // list of keys and values.
    $tmpst = "";
    for ($i = 0; $i < count($this->acmsSessKeys); $i++) {
        // Only add they keys if there is a value...
        if (isset($this->acmsSessKeys[$i])) {
            if (isset($this->acmsSessVars[$this->acmsSessKeys[$i]]) && strlen($this->acmsSessVars[$this->acmsSessKeys[$i]])) {
                $tmpst .= $this->acmsSessKeys[$i];
                $tmpst .= "\t";
                $tmpst .= ereg_replace("\n", "{NEWLINE}", $this->acmsSessVars[$this->acmsSessKeys[$i]]);
                $tmpst .= "\n";
            }
        }
    }

    // Now, base64 eoncode the string and shove it back into the database.
    $b64sess = chunk_split(base64_encode($tmpst));
    $sql = "replace into Sessions values ('". $this->acmsSessionID . "', NULL, '" . $b64sess . "')";
    //syslog(LOG_NOTICE, "Session Query = '$sql'");
    
    
    // Create a new database connection so we can be called within a 
    // query loop.
    $tmpDB = mysql_pconnect($this->acmsDBHost, $this->acmsDBUser, $this->acmsDBPass);
    if (!$tmpDB) {
        $this->fatalError("CCC", "Unable to connect to the database.  Please try again later.");
    }
    // Now, select the database
    //print "DBName = '$this->acmsDBName'<BR>";
    if (!@mysql_select_db($this->acmsDBName, $tmpDB)) {
        $this->fatalError("CCC", "Unable to connect to the database.  Please try again later.");
    }
    if (!mysql_query($sql, $tmpDB)) {
        $this->fatalError("Session Error", "There was an error accessing your account information.  Please try again later.");
    }

}   // setSessVar


/*
** setupMainPage - This function gets the main page definition and displays
**                 it.
*/

function setupMainPage()
{
    $this->handlerExec('page', 'HomePage');
}   // setupMainPage

/*
** exec - This is the main entry point for every other module except the
**        main page.  It looks at the arguments and checks for handlers,
**        etc.
*/

function exec()
{
    global $INCLUDE_DIR, $handlers, $modules;
    
    if (ACMS_DEBUG) {
        $this->writeLog("Entering acms:exec()");
    }
    // Are we doing an internal action?  This is specified by the 'ia'
    // argument.  If an action is processed, it does nothing else.
    // Actions are for things like logging out, etc.
    if (isset($_REQUEST['ia'])) {
        $this->processAction($_REQUEST['ia']);
        return;
    } else if (isset($_REQUEST['page'])) {
        // If they specified a Page, we automatically use the page handler.
        // Check to see if they specified a "reserved" page first, before
        // passing it to the main handlerExec function.
        $action = $_REQUEST['page'];
        $optArgs= "";
        if (strpos($_REQUEST['page'], "/") !== FALSE) {
            // Check to see if we have loaded this module.
            list($action, $optArgs) = split("/", $_REQUEST['page'], 2);
            $optArgs = eregi_replace("[^a-z0-9/-]", "", $optArgs);
        }
        if (!strcmp("ia", $action)) {
            $this->processAction($optArgs);
        } else if (!strcmp("chunk", $action) && strlen($optArgs)) {
            list($cName, $extra) = split("/", $optArgs, 2);
            $this->handlerExec($this->getHandler($cName), $cName);
        } else {
            $this->handlerExec('page', $_REQUEST['page']);
        }
        return;     // We're done.
    } else if (isset($_REQUEST['chunk']) && strlen($this->getHandler($_REQUEST['chunk']))) {
        $this->handlerExec($this->getHandler($_REQUEST['chunk']), $_REQUEST['chunk']);
        return;
    } else if (isset($_REQUEST['handler']) && isset($_REQUEST['name'])) {
        // Now check to see if we were given a handler.  If so,
        // we pass control off to the handler to Show the content.
        $this->handlerExec($_REQUEST['handler'], $_REQUEST['name']);
        return;     // We're done.
    } else if (isset($_REQUEST['mod'])) {
        // Check for a module load.
        // Load the module and pass control to it.
        $modname = eregi_replace("[^a-z0-9]", "", $_REQUEST['mod']);
        $module  = $INCLUDE_DIR . "modules/$modname/mod_" . $modname . ".php";
        //echo "$module<br>";
        if (file_exists($module) && is_readable($module)) {
            // Okay, it exists and we can read it.
            // Load up the module and hand control over to it.
            if (ACMS_DEBUG) {
                $this->writeLog("Loading module[1] '$module'");
            }
            require_once($module);
            if (ACMS_DEBUG) {
                $this->writeLog("Executing 'modules[" . $modname . "]->exec()'");
            }
            $modules[$modname]->exec();
        }
        return;     // We're done.
    } else {
        // We didn't find anything more specific, load the main page.
        // We do this by sending them back to the main URL.
        //header("Location: /");
        //exit;
        $this->setupMainPage();
    }

}

/*
** processAction - Checks for an internal action.
*/

function processAction($act)
{
    // $this->writeLog("processAction ('$act')");
    switch($act) {
        case    'logout':
            $this->logout();
            break;

        default:
            header("Location: /");
            exit;
            //$this->setupMainPage();
            break;
    }
}

/*
** loadHandler - Searches for the specified handler in our already loaded
**               list of handlers, and if its not found, it loads it.
**               Returns true if we were successful, false if we weren't.
*/

function loadHandler($handler)
{
    global $INCLUDE_DIR, $handlers;
    $retVal = false;

    // Check to see if the handler is already loaded. 
    // If it is, simply return success.
    if (isset($handlers[$handler])) return true;

    // If we're here, we have to load the handler.
    // Create the name of the handler file.
    $handlername = eregi_replace("[^a-z0-9]", "", $handler);
    $handlerfile = $INCLUDE_DIR . "handlers/hndlr_" . $handlername . ".php";
    //echo "$handler<br>";
    if (file_exists($handlerfile) && is_readable($handlerfile)) {
        // Okay, it exists and we can read it.
        if (ACMS_DEBUG) {
            $this->writeLog("Loading handler '$handlerfile'");
        }
        include_once($handlerfile);
        $retVal = true;
    }

    return $retVal;
}

/*
** handlerExec - Passes control off to a handler's "show" function.
*/

function handlerExec($handler, $name)
{
    global $INCLUDE_DIR, $handlers, $modules;

    // We were given a handler.  Load it.
    $handler = eregi_replace("[^a-z0-9]", "", $handler);
    if (!$this->loadHandler($handler)) {
        $this->writeLog("handlerExec: Unable to load handler '$handler'");
        return;
    }

    // We should probably make sure that it exists first.
    // Make sure that the chunk doesn't recurse into itself.
    // i.e. TextChunk returns something that references TextChunk
    
    // Check for optional arguments.  Arguments are given with a "/"
    // character.
    $optArgs = "";
    // Check for a module call.
    if (strpos($name, "/") !== FALSE) {
        // Check to see if we have loaded this module.
        list($name, $optArgs) = split("/", $name, 2);
        $optArgs = eregi_replace("[^a-z0-9\./ ]", "", $optArgs);
    }
    
    // Finally, set the chunk name.
    $chunkName = eregi_replace("[^a-z0-9]", "", $name);

    // There is a special handler, page.  If page is set, it will first check
    // to see if there is a module that matches the given page name.  If there
    // is, it will load the module and call it with optArgs as the
    // chunk name.
    
    // So, create the module file name...
    $module  = $INCLUDE_DIR . "modules/$chunkName/mod_" . $chunkName. ".php";
    //echo "$module<br>";
    if (file_exists($module) && is_readable($module)) {
        // Okay it is a module we're calling.
        // Load up the module and hand control over to it.
        if (ACMS_DEBUG) {
            $this->writeLog("Loading module[2] '$module'");
        }
        require_once($module);
        if (ACMS_DEBUG) {
            $this->writeLog("Executing 'modules[" . $chunkName . "]->exec($optArgs)'");
        }
        $modules[$chunkName]->exec("exec", $optArgs);
        $this->render();
        return;
    }


    //echo "$handlername, $chunkName";
    if (ACMS_DEBUG) {
        $this->writeLog("Calling handler '$handler->showChunk('$chunkName', '$optArgs')'");
    }
    $handlers[$handler]->showChunk($chunkName, $optArgs);
    $content = ob_get_contents();
    //echo "$content";
    return;     // We're done.
}

/*
** render - Puts together all of the page parts and sends it to the
**          visitor.
*/

function render()
{
    if ($this->isAdmin && count($this->chunksParsed)) {
        $tmpContent = "";
        foreach($this->chunksParsed as $parsed) {
            if (strlen($tmpContent)) $tmpContent .= "<br>";
            $tmpContent .= "<a href=\"/admin/EditChunk/" . $parsed["ChunkID"] . "\">";
            $tmpContent .= $parsed["ChunkName"];
            $tmpContent .= "</a>";
        }
        if (strlen($tmpContent)) {
            $this->addBlock(10000, INFO_ZONE, "Admin Edit", $tmpContent);
        }
    }
    // Send our cache controls.
    global $siteConfig;
    header("Cache-Control: no-cache,must-revalidate");
    header("pragma: no-cache");
    global $HTTP_ACCEPT_ENCODING;
    if ($this->doPersistantBlocks) $this->loadPersistantBlocks();
    $this->renderLayout();
    $content = ob_get_contents();
    // Check to see if we're going to compress the output
    $encoding = "gzip";
    $normsize = strlen($content);
    $gzipsize = $normsize;
    if ($this->compressOutput) {
        //$this->writeLog("ACCPET: $HTTP_ACCEPT_ENCODING");
        if (headers_sent()) $this->compressOutput = 0;
        if (headers_sent()) $this->writeLog("Unable to gzip output -- headers already sent");
        //if (eregi("gzip, deflate", $HTTP_ACCEPT_ENCODING)) $cangzip = 0;
        if (eregi("gzip", $HTTP_ACCEPT_ENCODING)) $encoding = "gzip";
        else if (eregi("x-gzip", $HTTP_ACCEPT_ENCODING)) $encoding = "x-gzip";
        else {
            $this->writeLog("Client doesn't support compression.  Disabling.");
            $this->compressOutput = 0;
        }
    }
    ob_end_clean();
    if ($this->compressOutput) {
        header("Content-Encoding: $encoding");
        $Crc  = crc32($content);
        $content = gzcompress($content, 5);
        $gzipsize = strlen($content);
        $content = substr($content, 0, strlen($content)-4);
        echo "\x1f\x8b\x08\x00\x00\x00\x00\x00";
        echo $content;
        //echo pack('V', crc32($content));
        //echo pack('V', $normsize);
        $comprate = sprintf("%.2f", (float)($normsize/$gzipsize));
        $this->writeLog("Compression rate $comprate:1");
    } else {
        //$this->writeLog("NOT gzipping data...");
        echo $content;
    }
    //echo "$content";
    //$this->writeLog("Did " . $this->pregCount . " preg_match()es");
    $this->writeLog();
}   // render


/*
** parseChunk  - Given a string with a chunk of data, it will parse it 
**               searching for other chunks.  It will call itself 
**               recursively until all chunks have been parsed.  What we
**               end up with is a block of data suitable for showing to
**               the user.
*/

function parseChunk($chunk, $wikiFormat = 1)
{
    global  $INCLUDE_DIR, $handlers, $modules;

    // Extract any non parsable blocks FIRST
    $npmatch = "/\~np\~(.*?)\~\/np\~/s";
    while (preg_match($npmatch, $chunk, $matches)) {
        $this->pregCount++;
        $repStr = "";
        //echo "<pre>Found Match 0 = '" . $matches[0] . "', 1 = '" . $matches[1] . "', 2 = '" . $matches[2] . "', 3 = '" . $matches[3] . "'</pre><br>";
        // Non-parsable stuff will be in $matches[1]
        if (strlen($matches[1])) {
            $repStr = md5($matches[1]);
            // Add it to the layout manager which will put back the
            // original, unparsed block just before rendering.
            $this->addPregMatch($repStr, $matches[1]);
        }
        $chunk = preg_replace($npmatch, $repStr, $chunk, 1);
    }

    // If we are doing wiki formatting, do that before we parse other
    // chunks.
    if ($wikiFormat) {
        $chunk = $this->parseWiki($chunk);
    }

    /*
    if (preg_match_all("/\{\{(.*?)\}\}/s", $chunk, $matches)) {
        $this->writeLog("Found " . count($matches) . " matches in the chunk '$chunk'");
    }
    */

    // Extract any chunk references from the string that was passed to us
    while (preg_match("/\{\{(.*?)\}\}/s", $chunk, $matches)) {
        $this->pregCount++;
        //echo "Found Match 0 = '" . $matches[0] . "', 1 = '" . $matches[1] . "'<br>";
        // The name of the chunk will be in $matches[1]
        $chunkName = $matches[1];
        $cArgs     = array();
        $parms     = "";            // Handler/module paramaters.
        $replText  = "";
        $modname   = "";
        $keepGoing = true;

        // Since we now have the chunk name, what we should do next
        // is see if there are any modifiers, such as:
        // handler/ChunkName - to override the handler from the DB.
        // or mod:ChunkName  - To call a module instead.
        // Later, though....

        // Extract our arguments.
        if (strpos($chunkName, "/") !== FALSE) {
            list($chunkName, $rawargs) = split("/", $chunkName, 2);
            // Turn the raw arguments into an array.
            $cArgs = $this->parseArgs($rawargs);
        }

        // Check to see if it is a module call.
        if (strpos($chunkName, ":") !== FALSE) {
            list($modname, $chunkName) = split(":", $chunkName, 2);
            $modname = eregi_replace("[^a-z0-9]", "", $modname);
            //list($chunkName, $parms) = split(":", $chunkName, 2);
        }

        // Check for a module call - Modules must always have arguments
        if (strlen($modname)) {
            // Check for a module load.
            // Load the module and pass control to it.
            $module  = $INCLUDE_DIR . "modules/$modname/mod_" . $modname . ".php";
            //echo "$module<br>";
            if (file_exists($module) && is_readable($module)) {
                // Okay, it exists and we can read it.
                // Load up the module and hand control over to it.
                if (ACMS_DEBUG) {
                    $this->writeLog("Loading module[3] '$module'");
                }
                require_once($module);
                if (ACMS_DEBUG) {
                    $this->writeLog("Executing 'modules[" . $modname . "]->exec($chunkName, $cArgs)'");
                }
                $replText = $modules[$modname]->exec($chunkName, $cArgs);
                $keepGoing = false;
            }
        } 
        
        if ($keepGoing) {

            // Search the database and find out who the handler is, and then
            // include the necessary files to extract the data.
            $sql = "select Handler, ChunkID from Chunks where ChunkName = '" . mysql_escape_string($chunkName) . "'";
            if ($result = mysql_query($sql, $this->acmsDB)) {
                if (mysql_num_rows($result)) {
                    $handlerlist = array();
                    // Load the result into memory so the handlers can
                    // re-use our database connection.
                    while ($curRow = mysql_fetch_array($result)) {
                        if (!isset($this->chunksParsedID[$curRow["ChunkID"]])) {
                            $this->chunksParsedID[$curRow["ChunkID"]] = $curRow["ChunkID"];
                            array_push($this->chunksParsed, array("ChunkID" => $curRow["ChunkID"], "ChunkName" => $chunkName));
                        }
                        array_push($handlerlist, $curRow['Handler']);
                    }
                    foreach($handlerlist as $handlername) {
                        if ($this->loadHandler($handlername)) {
                            // We should probably make sure that it exists first.
                            // Make sure that the chunk doesn't recurse into itself.
                            // i.e. TextChunk returns something that references TextChunk
                            $pr = "/\{\{" . $chunkName;
                            if (strlen($parms)) {
                                $pr .= ":$parms";
                            }
                            $pr .= "\}\}/s";
                            if (ACMS_DEBUG) {
                                $this->writeLog("Calling handler '$handlername -> getChunk('$chunkName', '$parms')'");
                            }
                            $replText .= preg_replace($pr, "", $handlers[$handlername]->getChunk($chunkName, $cArgs));
                        }
                    }
                }
            }
        }

        $chunk = preg_replace("/\{\{(.*?)\}\}/s", $replText, $chunk, 1);
    }

    // Extract any "global" variables.
    while (preg_match("/\{\%(.*?)\%\}/s", $chunk, $matches)) {
        $this->pregCount++;
        //echo "Found Match 0 = '" . $matches[0] . "', 1 = '" . $matches[1] . "'<br>";
        // The name of the block will be in $matches[1]
        $varName  = $matches[1];
        $replText = "";
        if (isset($this->sysChunkVars[$varName])) $replText = $this->sysChunkVars[$varName];
        $chunk = preg_replace("/\{\%(.*?)\%\}/s", $replText, $chunk, 1);
    }

    // Extract any "normal" links.
    $chunk = $this->parseLinks($chunk);

    // All done.  Return our parsed chunk.
    return $chunk;
}   // parseChunk

/*
** parseArgs - Given a "raw" list of arguments, each separated with a 
**             comma, this will turn it into an array.
**             Arguments with no paramaters will be set to "true".
*/

function parseArgs($rawargs)
{
    $retRay = array();

    // First things first, find anything wrapped in double quotes.  This is
    // how arguments can contain commas.
    $qmatch = "/\"\"(.*?)\"\"/s";
    $qsearch = array();
    $qrepl   = array();
    while (preg_match($qmatch, $rawargs, $matches)) {
        $repStr = "";
        // $this->writeLog("Found Match 0 = '" . $matches[0] . "', 1 = '" . $matches[1] . "', 2 = '" . $matches[2] . "', 3 = '" . $matches[3] . "'");
        // Non-parsable stuff will be in $matches[1]
        if (strlen($matches[1])) {
            $repStr = md5($matches[1]);
            // Add it to our list of arguments with quotes
            array_push($qsearch,  "/" . $repStr . "/s");
            array_push($qrepl,    $matches[1]);
        }
        $rawargs = preg_replace($qmatch, $repStr, $rawargs, 1);
    }

    // Now that we have removed our quoted stuff, split out the arguments.
    // And while we're at it, put back any quoted stuff.
    $argList = preg_split("/[,\/]/s", $rawargs);
    foreach($argList as $arg) {
        $arg = trim($arg);
        if (strpos($arg, "=") !== FALSE) {
            list($key, $val) = split("=", $arg, 2);
            $retRay[$key] = preg_replace($qsearch, $qrepl, $val);
        } else {
            $retRay[$arg] = true;
        }
    }

    //echo "<pre>"; print_r($retRay); echo "</pre>";
    return $retRay;
}

/*
** parseWiki - Given a block of text, this parses and expands any Wiki
**             style formatting.  It returns the expanded block/chunk.
*/

function parseWiki($chunk)
{
    $npsearch  = array();
    $npreplace = array();
    
    // Extract any non parsable blocks FIRST
    $npmatch = "/\~np\~(.*?)\~\/np\~/s";
    while (preg_match($npmatch, $chunk, $matches)) {
        $this->pregCount++;
        $repStr = "";
        // $this->writeLog("Found Match 0 = '" . $matches[0] . "', 1 = '" . $matches[1] . "', 2 = '" . $matches[2] . "', 3 = '" . $matches[3] . "'");
        // Non-parsable stuff will be in $matches[1]
        if (strlen($matches[1])) {
            $repStr = md5($matches[1]);
            // Add it to the layout manager which will put back the
            // original, unparsed block just before rendering.
            array_push($npsearch,  "/" . $repStr . "/s");
            array_push($npreplace, "~np~" . $matches[1] . "~/np~");
        }
        $chunk = preg_replace($npmatch, $repStr, $chunk, 1);
    }

    // Extract any Chunk calls since they may contain colons ans mess things up
    $npmatch = "/\{\{(.*?)\}\}/s";
    while (preg_match($npmatch, $chunk, $matches)) {
        $repStr = "";
        // $this->writeLog("Found Match 0 = '" . $matches[0] . "', 1 = '" . $matches[1] . "'");
        // Non-parsable stuff will be in $matches[1]
        if (strlen($matches[1])) {
            $repStr = md5($matches[1]);
            // Add it to the layout manager which will put back the
            // original, unparsed block just before rendering.
            array_push($npsearch,  "/" . $repStr . "/s");
            array_push($npreplace, "{{" . $matches[1] . "}}");
        }
        $chunk = preg_replace($npmatch, $repStr, $chunk, 1);
    }

    $chunk = $this->parseTemplateLists($chunk);
    $chunk = $this->parseWikiLists($chunk);
    $chunk = $this->parseWikiTables($chunk);

    // Get rid of any carriage returns.
    $chunk = ereg_replace("\r", "", $chunk);

    // I'm sure there is a simpler and more clever way of doing this,
    // but that can wait until we want to optimize it a bit more
    // Bold face
    $cnt = 0;
    $patterns[$cnt]     = "/<!--([^;])*?-->/"; // HTML comments
    $replacements[$cnt] = "";
    $cnt++;
    // Header - ~~h1:text~~
    $patterns[$cnt]     = "/~~h([0-9,a-f]+):(.*?)~~/is"; // Header
    $replacements[$cnt] = "<h$1>$2</h$1>";
    $cnt++;
    // Font color - ~~#color:text~~
    $patterns[$cnt]     = "/~~#([0-9,a-f]{6,}):(.*?)~~/is"; // Font color
    $replacements[$cnt] = "<font color=\"$1\">$2</font>";
    $cnt++;
    // Style sheet - ~~@classname:text~~
    $patterns[$cnt]     = "/~~@([0-9,a-z-]*):(.*?)~~/is"; // Span
    $replacements[$cnt] = "<span class=\"$1\">$2</span>";
    $cnt++;
    $patterns[$cnt]     = "/~~!([0-9,a-z-]*):(.*?)~~/is"; // Div
    $replacements[$cnt] = "<div class=\"$1\">$2</div>";
    $cnt++;
    $patterns[$cnt]     = "/~lt~/is";      // Less than character
    $replacements[$cnt] = "&lt;";
    $cnt++;
    $patterns[$cnt]     = "/~gt~/is";      // Greater than character
    $replacements[$cnt] = "&gt;";
    $cnt++;
    $patterns[$cnt]     = "/@\^(.*?)\^@/is";      // Superscript
    $replacements[$cnt] = "<sup>\\1</sup>";
    $cnt++;
    $patterns[$cnt]     = "/@_(.*?)_@/s";  // Small Print
    $replacements[$cnt] = "<div class=\"fineprint\">\\1</div>";
    $cnt++;
    $patterns[$cnt]     = "/__(.*?)__/s";      // Bold Faced
    $replacements[$cnt] = "<b>\\1</b>";
    $cnt++;
    $patterns[$cnt]     = "/''(.*?)''/s";      // Italics
    $replacements[$cnt] = "<i>\\1</i>";
    $cnt++;
    $patterns[$cnt]     = "/::(.*?)::/s";      // Centered
    $replacements[$cnt] = "<center>\\1</center>";
    $cnt++;
    $patterns[$cnt]     = "/\^\^(.*?)\^\^/s";  // Boxed
    $replacements[$cnt] = "<div class=\"simplebox\">\\1</div>";
    $cnt++;
    $patterns[$cnt]     = "/\^\#(.*?)\#\^/s";  // Boxed and Shaded
    $replacements[$cnt] = "<div class=\"shadedbox\">\\1</div>";
    $cnt++;
    $patterns[$cnt]     = "/\^\%(.*?)\%\^/s";  // Boxed and Shaded and floating
    $replacements[$cnt] = "<table width=\"33%\" align=\"right\" cellpadding=\"1\" cellspacing=\"1\" border=\"0\"><tr><td><div class=\"shadedbox\">\\1</div></td></tr></table>";
    $cnt++;
    $patterns[$cnt]     = "/-=(.*?)=-/s";      // Title bar
    $replacements[$cnt] = "<div class=\"articlesubheader\">\\1</div>";
    $cnt++;
    $patterns[$cnt]     = "/\+\+(.*?)\+\+/s";  // Indented paragraph.
    $replacements[$cnt] = "<div class=\"indent\">\\1</div>";
    $cnt++;
    $patterns[$cnt]     = "/\-\+(.*?)\+\-/s";  // Inset paragraph.
    $replacements[$cnt] = "<div class=\"inset\">\\1</div>";
    $cnt++;
    // This last pattern searches for our closing div tags.  We should
    // get rid of any single line feeds following our tags since the closing
    // div tag implies a new line.
    $patterns[$cnt]     = "/\<\/div\>\\n/s";
    $replacements[$cnt] = "</div>";
    $cnt++;

    $chunk = preg_replace($patterns, $replacements, $chunk);

    // Newlines
    $chunk = ereg_replace("\n----\n", "<hr>", $chunk);
    $chunk = ereg_replace("\n\n", "<p>", $chunk);
    $chunk = ereg_replace("\\\\\n", "", $chunk);
    $chunk = ereg_replace("\n",   "<br>\n", $chunk);

    // Now, put back all of our non-parsable sections.  They will be pulled
    // out again later by the chunk handler.
    if (count($npsearch)) {
        $chunk = preg_replace($npsearch, $npreplace, $chunk);
    }

    return $chunk;
}

/*
** parseWikiLists - Parses any ordered (numbered) or unordered lists.
**                  Lists begin with one or more "*" or "#" characters
**                  at the beginning of a line.  The number of "*" or "#"
**                  determine how deep the level is.
**
**                  A chunk is passed in, and a parsed chunk is returned.
*/

function parseWikiLists($chunk)
{
    $listTypes = array(
            array('marker' => "*", 'tag' => "ul"),
            array('marker' => "#", 'tag' => "ol")
        );

    $parts = split("\n", $chunk);

    foreach($listTypes as $wikiList) {
        $listLevel = 0;
        $tag       = $wikiList["tag"];
        $marker    = $wikiList["marker"];

        for ($i = 0; $i < count($parts); $i++) {
            $line = $parts[$i];
            $newLine = "";
            $icount  = 0;
            if (!strcmp(substr($line, 0, 1), $marker)) {
                // Count them.
                //echo "$line<br>";
                while (!strcmp(substr($line,0,1), $marker)) {
                    $icount++;
                    $line = substr($line,1);
                }
                if ($listLevel < $icount) {
                    while ($listLevel < $icount) {
                        $newLine = "<$tag>";
                        $listLevel++;
                    }
                }
                if ($listLevel > $icount) {
                    while ($listLevel > $icount) {
                        $newLine .= "</$tag>";
                        $listLevel--;
                    }
                }
                $newLine .= "<li>$line</li>";
            } else {
                while($listLevel) {
                    $newLine .= "</$tag>";
                    $listLevel--;
                }
                $newLine .= $line;
            }
            $parts[$i] = $newLine;
        }

        // Close any open lists
        if ($listLevel) {
            $tmpLine = "";
            while ($listLevel) {
                $tmpLine .= "</$tag>";
                $listLevel--;
            }
            array_push($parts, $tmpLine);
        }

    }

    // Get definition lists.
    $inList  = 0;
    for ($i = 0; $i < count($parts); $i++) {
        $line = $parts[$i];
        $tmpLine = "";
        if (preg_match("/^;(.*?):(.*?)$/s", $line, $matches)) {
            //echo "Found Match 0 = '" . $matches[0] . "', 1 = '" . $matches[1] . "', 2 = '" . $matches[2] . "'<br>";
            // The title/thing we are defining will be in $matches[1]
            // The definition itself will be in $matches[2]
            if (!$inList) {
                $tmpLine = "<dl>";
                $inList = 1;
            }
            $tmpLine .= "<dt>" . $matches[1] . "</dt>";
            $tmpLine .= "<dd>" . $matches[2] . "</dd>";
        } else {
            if ($inList) {
                $tmpLine = "</dl>";
                $inList  = 0;
            }
            $tmpLine .= $line;
        }
        $parts[$i] = $tmpLine;
    }

    // Put the chunk back together
    $chunk = implode("\n", $parts);

    // Now, to keep the parser from adding br tags to the lists, remove
    // the line feeds here.
    $cnt = 0;
    $patterns[$cnt]     = "/<\/ul>\\n/"; // Unordered lists
    $replacements[$cnt] = "</ul>";
    $cnt++;
    $patterns[$cnt]     = "/<\/ol>\\n/"; // Ordered lists
    $replacements[$cnt] = "</ol>";
    $cnt++;
    $patterns[$cnt]     = "/<\/li>\\n/"; // List items
    $replacements[$cnt] = "</li>";
    $cnt++;
    $patterns[$cnt]     = "/<\/dd>\\n/"; // List items
    $replacements[$cnt] = "</dd>";
    $cnt++;
    $chunk = preg_replace($patterns, $replacements, $chunk);

    return $chunk;
}

/*
** parseWikiTables - Extracts any tables from the chunk.
*/

function parseWikiTables($chunk)
{
    $modifiers = "/^\((.*?)\)(.*?)$/s";
    while (preg_match("/(.*?)\|\|(.*?)\|\|(.*?)$/s", $chunk, $matches)) {
        //echo "<pre>Found Match 0 = '" . $matches[0] . "', 1 = '" . $matches[1] . "', 2 = '" . $matches[2] . "', 3 = '" . $matches[3] . "'</pre><br>";
        // Stuff before the table will be in $matches[1]
        // The table will will be in $matches[2]
        // Stuff after the table will be in $matches[3]

        $tmpChunk = $matches[1];
        $args     = "";
        $tabChunk = $matches[2];

        if (preg_match($modifiers, $tabChunk, $modmatch)) {
            $args     = $modmatch[1];
            $tabChunk = $modmatch[2];
        }

        $parts = split("\n", $tabChunk);
        if (count($parts)) {
            $tmpChunk .= "<table $args>";
            for ($i = 0; $i < count($parts); $i++) {
                if (strlen($parts[$i])) {
                    $tmpChunk .= "<tr>";
                    $cols = split("\|", $parts[$i]);
                    for ($j = 0; $j < count($cols); $j++) {
                        $tmpCol = $cols[$j];
                        $args   = "";
                        if (preg_match($modifiers, $tmpCol, $modmatch)) {
                            $args   = $modmatch[1];
                            $tmpCol = $modmatch[2];
                        }
                        $tmpChunk .= "<td $args>";
                        $tmpChunk .= $tmpCol;
                        $tmpChunk .= "</td>";
                    }
                    $tmpChunk .= "</tr>";
                }
            }
            $tmpChunk .= "</table>";
        }
        $tmpChunk .= $matches[3];
        $chunk = $tmpChunk;
    }

    return $chunk;
}

/** parseTemplateLists - Looks for text in the form:
  *                      [tlist="templatename.tpl]
  *                      ColHead1|ColHead2|ColHead3|ColHead4
  *                      row1val1|row1val2|row1val3|row1val4
  *                      row2val1|row2val2|row2val3|row2val4
  *                      ...
  *                      rowxval1|rowxval2|rowxval3|rowxval4
  *                      [/tlist]
  *
  * It then creates an associative array using the CSV information
  * and feeds that data into the specified template.  If the template
  * doesn't being with a "/", it prepends the system template path.
  * If no template name is given, or the specified template can't be found,
  * it uses the default template, tlist.tpl.
  * Simple, no?
  *
  * @return The parsed chunk.
  */
function parseTemplateLists($chunk)
{
    // Convert it so that if they don't give us a template, we'll provide
    // one for them.
    $chunk = ereg_replace("\[tlist\]", "[tlist=\"tlist.tpl\"]", $chunk);
    $sstr = "/\[tlist\=(&quot;|\")([^\n\r\t\<\>]+?)(&quot;|\")\]([^\a]+?)\[\/tlist\]/i";
    while (preg_match($sstr, $chunk, $matches)) {
        //for ($i = 0; $i < 8; $i++) echo "Match $i: '" . $matches[$i] . "'<br>";
        // The template name will be in $matches[2]
        // The table will will be in $matches[4]

        // Create our template name.
        $tmpFile = $matches[2];
        if ($tmpFile{0} != "/") {
            $tmpFile = $this->templatePath . $tmpFile;
        }
        if (!file_exists($tmpFile) || !is_readable($tmpFile)) {
            $tmpFile = $this->templatePath . "tlist.tpl";
        }
        
        
        // Split the list/table content into lines.
        //$tmpSt = ereg_replace("\$", "&#34;", $matches[4]);
        $lines = split("\n", trim(ereg_replace("\r", "", $matches[4])));
        //echo "<pre>"; print_r($lines); echo "</pre>";
        // We have to have at least two lines to be a list.
        if (count($lines) > 1) {
            // Get the keys from the first line.
            $line = trim(array_shift($lines));
            $keys = split("\|", $line);
            $data = array();
            // Now, walk through the rest of the lines and turn
            // them into the associative array using the keys
            // we extracted above.
            foreach($lines as $line) {
                //echo "Line: '$line'<br>";
                $items = split("\|", trim($line));
                $assocItem = array();
                $idx = array();
                for ($i = 0; $i < count($keys); $i++) {
                    if (isset($items[$i])) {
                        array_push($idx, $items[$i]);
                        $assocItem[$keys[$i]] = $items[$i];
                    } else {
                        array_push($idx, "");
                        $assocItem[$keys[$i]] = "";
                    }
                }
                $assocItem["indexed"] = $idx;
                array_push($data, $assocItem);
            }

            // Now, create our template.
            $tpl = new acmsTemplate($tmpFile);
            $tpl->assign("keys", $keys);
            $tpl->assign("data", $data);
            $table = $tpl->get();
            $tableLines = split("\n", trim($table));
            $table = join("", $tableLines);
            $table = str_replace("$", "&#36;", $table);
            //echo "<pre>$table</pre>";
        }

        $chunk = preg_replace($sstr, $table, $chunk, 1);
    }
    return $chunk;
}

/*
** getHandler - Returns the handler for the specified chunk.
**              If it can't find one, it returns an empty string,
**              if it can find one, it returns its name.
*/

function getHandler($chunkName)
{
    global $INCLUDE_DIR;
    $retVal = "";
    $sql = "select distinct Handler from Chunks where ChunkName = '" . mysql_escape_string($chunkName) . "'";
    if ($result = mysql_query($sql, $this->acmsDB)) {
        //echo "query returned " . mysql_num_rows($result) . " rows.<br>";
        if (mysql_num_rows($result)) {
            $handlerlist = array();
            // Load the result into memory so the handlers can
            // re-use our database connection.
            while ($curRow = mysql_fetch_array($result)) {
                array_push($handlerlist, $curRow['Handler']);
                $retVal = $curRow['Handler'];
            }
            foreach($handlerlist as $handlername) {
                // Okay, we know what handler to call.  Include it.
                $handler = $INCLUDE_DIR . "handlers/hndlr_$handlername.php";
                if (file_exists($handler) && is_readable($handler)) {
                    // Okay, it exists and we can read it.
                    if (ACMS_DEBUG) {
                        $this->writeLog("Loading handler plugin '$handler'");
                    }
                    require_once($handler);
                    $retVal = $handlername;
                }
            }
        }
    }

    //echo "handler = '$retVal'<br>";

    return $retVal;
}

/*
** parseLinks  - Given an already expanded chunk.  This will extract
**               links.
**
**               Links are in the form:
**                 [[#http://www.example.com/ Example Web Site]]
**
**               The # is optional, and if specified, it will open the
**               window in a new target.
**               The name (Example Web Site) is also optional, and if omitted
**               the link itself will be used as the text.  The name is 
**               separated by a space.  Anything else will be assumed
**               to be part of the link.
*/

function parseLinks($chunk)
{
    global  $INCLUDE_DIR, $handlers, $modules;
    // Extract any chunk references from the string that was passed to us
    while (preg_match("/\[\[(.*?)\]\]/s", $chunk, $matches)) {
        //echo "Found Match 0 = '" . $matches[0] . "', 1 = '" . $matches[1] . "'<br>";
        // The name of the chunk will be in $matches[1]
        $link      = $matches[1];
        $replText  = "";

        // Okay, extract the parts of the linke we care about.
        $url    = "";
        $name   = "";
        $target = "";

        // Check to see if we're doing a new target.
        if (!strcmp(substr($link, 0, 1), "#")) {
            // Yup.  New target.
            $target = " target=\"_newwin\"";
            // Get rid of the "#".
            $link = substr($link, 1);
        }

        // Check to see if they gave us any text.
        $spos = strpos($link, " ");
        if ($spos) {
            // Yup.  They did.
            $name = trim(substr($link, $spos));
            $url  = trim(substr($link, 0, $spos));
        } else {
            // Nope.  No text.  Show them the link.
            $name = $link;
            $url  = $link;
        }

        $replText = "<a href=\"" . $url . "\"$target>$name</a>";
        
        $chunk = preg_replace("/\[\[(.*?)\]\]/s", $replText, $chunk, 1);
    }
    return $chunk;
}   // parseLinks

/*
** loadPersistantBlocks - Gets all of the persistant blocks out of
**                        the database and adds them to the page.
**                        This should be called from the render() 
**                        function.
*/

function loadPersistantBlocks()
{
    global $handlers;
    $blist = array();

    // We only want to load the blocks if there is a block handler.
    if ($this->loadHandler("block")) {
        $sql = "select Chunks.ChunkName from Chunks, Chunks_block where Chunks.Handler = 'block' and Chunks.ChunkID = Chunks_block.ChunkID and Chunks_block.Persistant <> 0";
        if ($result = mysql_query($sql, $this->acmsDB)) {
            if (mysql_num_rows($result)) {
                // Load up the rows.
                while ($curRow = mysql_fetch_array($result)) {
                    array_push($blist, $curRow['ChunkName']);
                }

                // Okay.  The array is all loaded up.  Now, walk through
                // each block
                foreach($blist as $blockName) {
                    $handlers['block']->showChunk($blockName);
                }
            }
        }
    }
}


// Permissions/User/Group Functions

/*
** getUser - Given a numeric user ID, this gets the user name from the 
**           database.  It stores whichever users it knows about in 
**           a cache.
*/

function getUser($uid)
{
    if ($uid == 0) return "admin";
    $retStr = "Unknown";
    if (isset($this->UserIDCache[$uid])) {
        $retStr = $this->UserIDCache[$uid];
    } else {
        // Get both the user name and group and cache them both.
        $sql = "select Users.UserName, Users.GroupID, Groups.GroupName from Users, Groups where Users.UserID = " . mysql_escape_string($uid) . " and Groups.GroupID = Users.GroupID";
        $result = mysql_query($sql, $this->acmsDB);
        if ($result) {
            if (mysql_num_rows($result)) {
                $curRow = mysql_fetch_array($result);
                $retStr = $curRow["UserName"];
                $this->UserIDCache[$uid]  = $retStr;
                $this->GroupIDCache[$curRow["GroupID"]] = $curRow["GroupName"];
            }
        }
    }

    return $retStr;
}

/*
** getGroup - Given a numeric group ID, this gets the group name from the 
**            database.  It stores whichever groups it knows about in 
**            a cache.
*/

function getGroup($gid)
{
    if ($gid == 0) return "admin";
    $retStr = "Unknown";
    if (isset($this->GroupIDCache[$gid])) {
        $retStr = $this->GroupIDCache[$gid];
    } else {
        // Get the group and cache it
        $sql = "select GroupName from Groups where GroupID = " . mysql_escape_string($gid);
        $result = mysql_query($sql, $this->acmsDB);
        if ($result) {
            if (mysql_num_rows($result)) {
                $curRow = mysql_fetch_array($result);
                $retStr = $curRow["GroupName"];
                $this->GroupIDCache[$gid]  = $retStr;
            }
        }
    }

    return $retStr;
}

/*
** isGroupMember - Given a Group ID, this function returns true or false
**                 depending on whether or not the current user is a member
**                 of the specified group.
*/

function isGroupMember($gid)
{
    // First things first, if they're not logged in, they're not a member
    // of any group.
    if (!$this->loggedIn) return false;
    $retVal = false;

    if (isset($this->acmsSessVars["UserID"])) {
        $sql = "select GroupMemberID from GroupMembers where GroupID = " . mysql_escape_string($gid) . " and UserID = " . $this->acmsSessVars["UserID"];
        $result = mysql_query($sql, $this->acmsDB);
        if ($result) {
            if (mysql_num_rows($result)) {
                $retVal = true;
            }
        }
    }

    return $retVal;
}

/*
** hasAccess - Given the type of access (read, write, list), a set of 
**             permision octets, the owner and group of an item, this
**             returns true or false whether access should be granted.
*/

function hasAccess($aType, $perms, $uid, $gid, $checkLogin = 1)
{
    $retVal     = false;
    
    // Our objects permissions.
    $oLoggedIn  = 0;
    $oOwner     = 0;
    $oGroup     = 0;
    $oWorld     = 0;
    // Our users permisssions
    $uLoggedIn  = 0;
    $uOwner     = 0;
    $uGroup     = 0;
    $uWorld     = 0;

    // Create our mask for doing a bitwise and on the passed in permissions.
    if ($this->loggedIn) {
        $uLoggedIn  = 2;
    } else {
        // Not logged in.
        $uLoggedIn  = 1;
    }

    if (isset($this->acmsSessVars["UserID"])) {
        if ($this->acmsSessVars["UserID"] == $uid) {
            $uOwner = 7;
        }
    }

    if (isset($this->acmsSessVars["GroupID"])) {
        if ($this->acmsSessVars["GroupID"] == $gid) {
            $uGroup = 7;
        } 
    }

    if ($this->isGroupMember($gid)) {
        $uGroup = 7;
    }

    $uWorld = 7;

    // Check to see if they are an admin
    if ($this->isGroupMember(0)) {
        $retVal = true;
    }

    // Get the permissions out of the passed in variables.
    $oWorld = $perms % 10;
    $perms  = ($perms - $oWorld) / 10;
    $oGroup = $perms % 10;
    $perms  = ($perms - $oGroup) / 10;
    $oOwner = $perms % 10;
    $perms  = ($perms - $oOwner) / 10;
    $oLoggedIn = $perms % 10;

    // Now, strip out which value we're looking for.
    switch ($aType) {
        case "write":
            $oWorld = $oWorld & 2;
            $oGroup = $oGroup & 2;
            $oOwner = $oOwner & 2;
            break;
        case "list":
            $oWorld = $oWorld & 1;
            $oGroup = $oGroup & 1;
            $oOwner = $oOwner & 1;
            break;
        case "read":
        default:
            $oWorld = $oWorld & 4;
            $oGroup = $oGroup & 4;
            $oOwner = $oOwner & 4;
            break;
    }

    if ($oWorld & $uWorld) $retVal = true;
    if ($oGroup & $uGroup) $retVal = true;
    if ($oOwner & $uOwner) $retVal = true;
    if ($retVal) {
        //echo "Check login bit = '$checkLogin'...<br>";
        if ($checkLogin) {
            //echo "Checking login bit...<br>";
            // Check the logged in attrbiute.
            if ($oLoggedIn) {
                if ($this->loggedIn && $oLoggedIn == 1) $retVal = false;
                if (!$this->loggedIn && $oLoggedIn == 2) $retVal = false;
            }
        } else {
            //echo "Skipped login bit check...<br>";
        }
    }

    /*
    if ($oPerms & $perms) {
        echo "<pre>";
        echo "$oPerms\n";
        echo "$perms\n";
        echo $oPerms & $perms;
        echo "</pre>";
        $retVal = true;
    }
    */


    return $retVal;
}

function writeLog($extra = "")
{
    global $ACMSCfg;

    // Get the time it took for the script to execute
    list($a_dec, $a_sec) = explode(" ", $this->startTime);
    list($b_dec, $b_sec) = explode(" ", microtime());
    $execTime = $b_sec - $a_sec + $b_dec - $a_dec;

    // Set our log options
    $logMsg = "";
    $logIP = 1;
    $logTime = 1;
    $timeFmt = "Y-m-d H:i:s";
    if (isset($ACMSCfg['General']['LogClientIP'])) $logIP   = $ACMSCfg['General']['LogClientIP'];
    if (isset($ACMSCfg['General']['LogTime']))     $logTime = $ACMSCfg['General']['LogTime'];
    if (isset($ACMSCfg['General']['TimeFormat']))  $timeFmt = $ACMSCfg['General']['TimeFormat'];

    // Log the time
    if ($logTime) {
        $logMsg .= "[" . date($timeFmt) . "] ";
    }
    // Now, log the script information.
    $logMsg .= "[";
    if (isset($this->loggedIn) && $this->loggedIn) {
        $logMsg .= $this->acmsSessVars['LoginID'];
    } else {
        $logMsg .= "UNKNOWN";
    }
    $logMsg .= "] ";
    // Now, the client IP address
    if ($logIP) {
        $logMsg .= "[";
        if (strlen(getenv("REMOTE_ADDR"))) {
            $logMsg .= getenv("REMOTE_ADDR");
        } else {
            $logMsg .= "NO IP ADDRESS";
        }
        $logMsg .= "] ";
    }

    // The execution time (so far)
    $logMsg .= sprintf("[%.3fs]", $execTime);

    if ($extra) $logMsg .= "*";

    $logMsg .= " " . $_SERVER['REQUEST_URI'];

    if ($extra) $logMsg .= " [$extra]";

    error_log($logMsg);
}

/*
** isInCategory - Given a category ID, this fuction will return true or
**                false if the currently loaded chunk is in that category.
**                It is SQL loop safe.
*/

function isInCategory($catID, $chunkID)
{
    global $app;
    $retVal = false;
    $dbConn = $app->newDBConnection();
    $sql = "select ChunkID from CategoryItems where CategoryID = $catID and ChunkID = $chunkID";
    $result = mysql_query($sql, $dbConn);
    if ($result) {
        if (mysql_num_rows($result)) {
            $retVal = true;
        }
    }
    return $retVal;
}

/*
** getCategoryList - Gets a list of categories and their children.
*/

function getCategoryList()
{
    $categoryList = array();
    $this->getCategoryListInternal(&$categoryList, 0, 0);

    return $categoryList;
}

function getCategoryListInternal($categoryList, $parentID, $level)
{
    $retVal = "";
    $myDBConn = $this->newDBConnection();
    $sql = "select * from Categories where ParentID = $parentID order by Title";
    $result = mysql_query($sql, $myDBConn);
    if ($result) {
        if (mysql_num_rows($result)) {
            while($curRow = mysql_fetch_array($result)) {
                $item = array(
                          "CategoryID"  => $curRow["CategoryID"],
                          "ParentID"    => $curRow["ParentID"],
                          "Title"       => $curRow["Title"],
                          "IconTag"     => $curRow["IconTag"],
                          "Description" => $curRow["Description"],
                          "Level"       => "$level"
                        );
                array_push($categoryList, $item);

                if ($level) {
                    for ($i = 0; $i < $level; $i++) {
                        $retVal .= "*";
                    }
                }
                $retVal .= $curRow["Title"];
                $retVal .= "\n";
                $retVal .= $this->getCategoryListInternal(&$categoryList, $curRow["CategoryID"], $level+1);
            }
        }
    }

    return $retVal;
}

/*
** getCategoryTitle - Returns the name of a category.
*/

function getCategoryTitle($catID)
{
    $retVal = "";
    $myDBConn = $this->newDBConnection();
    $sql = "select Title from Categories where CategoryID = $catID";
    $result = mysql_query($sql, $myDBConn);
    if ($result) {
        if (mysql_num_rows($result)) {
            $curRow = mysql_fetch_array($result);
            $retVal = $curRow["Title"];
        }
    }

    return $retVal;
}

/** Cleans and filters the PHP $_REQUEST[] array and returns a new
  * associative array.
  *
  * \param skip An array of request variables to not filter.
  */

function cleanRequest($skip = array(""))
{
    // Join the skip array for easy searching.
    $skipStr = "";
    if (count($skip)) $skipStr = "\t" . implode("\t", $skip) . "\t";

    // Setup our return array and walk through the $_REQUEST vars
    $filter = new InputFilter();
    $retRay = array();
    foreach ($_REQUEST as $key => $val) {
        if (strpos($skipStr, "\t$key\t") === false) {
            // This key wasn't in our skip list.
            $retRay[$key] = trim(stripslashes($filter->process($val)));
        } else {
            $retRay[$key] = $val;
        }
    }
    return $retRay;
}

/* \brief Gets the variables passed in via the GET method and stores
** them in a local array.  This is done so they can be manipulated
** and deleted if desired.  They are then reassembled with the 
** createURL function.  This works much like cleanRequest()
** but only looks at the $_GET variables.
**
** \return A reference to the array we created.
*/ 
function parseURL($skip = array())
{
    global $ACMSRewrite;
    $this->getVars = array();
    // Join the skip array for easy searching.
    $skipStr = "";
    if (count($skip)) $skipStr = "@" . implode("@", $skip) . "@";

    // Setup our return array and walk through the $_REQUEST vars
    $filter = new InputFilter();
    foreach ($_GET as $key => $val) {
        if ($ACMSRewrite && !strcasecmp("page", $key)) continue;
        if (strpos($skipStr, "@$key@") === false) {
            // This key wasn't in our skip list.
            $this->getVars[$key] = trim(stripslashes($filter->process($val)));
        } else {
            $this->getVars[$key] = $val;
        }
    }
    return $this->getVars;
}

/* Assembles a URL from our variables.  Additional
** variables can be included, but the application variable
** getVars will be examined first, and then the passed in
** variables will be used.  This can be used to set a default
** and then override them with passed in variables.
*/
function createURL($extra = array())
{
    $url = "";
    $args = array();
    if (count($this->getVars)) {
        foreach($this->getVars as $key => $val) {
            $args[$key] = $val;
        }
    }
    if (count($extra)) {
        foreach($extra as $key => $val) {
            $args[$key] = $val;
        }
    }

    if (count($args)) {
        foreach($args as $key => $val) {
            if (strlen($url)) $url .= "&";
            else $url .= "?";

            $url .= urlencode($key);
            $url .= "=";
            $url .= urlencode($val);
        }
    }
    $baseURL = $_SERVER['REQUEST_URI'];
    $pos = strpos($baseURL, "?");

    if ($pos !== false) {
        $baseURL = substr($baseURL, 0, $pos);
    }


    $url = $baseURL . $url;
    return $url;
}

/*
** createMenu - Given a chunk of text, this will return a formatted menu.
**              This is probably a *really* bad idea, but I want to give
**              the other internal functions a way to create a dynamic 
**              menu.  Why reinvent the wheel, you know?
*/

function createMenu($menuChunk, $opts)
{
    $retVal = "";

    $menuList  = array();
    $curLevel  = 0;
    $curParent = 0;
    $itemID    = -1;

    // Split each line of the menu and run it through the 
    // chunk parser.
    $lines = split("\n", ereg_replace("\r", "", $menuChunk));
    foreach($lines as $item) if (strlen($item)) {
        // Count the number of indent levels.
        $iLevel = 0;
        $tmpItem = $item;
        while(ereg("^:", $tmpItem) !== false) {
            $tmpItem = ereg_replace("^:", "", $tmpItem);
            $iLevel++;
        }
        //echo "Indent level for '$tmpItem' is $iLevel<br>";
        // Check to see if we need to make this one a child of the 
        // parent.  But only if its not the first entry.
        if (count($menuList) && $iLevel > $curLevel) {
            $curLevel++;
            $curParent = $itemID;
            $menuList[$curParent]["hasChildren"] = 1;
        }
        while ($curLevel > $iLevel) {
            $curLevel--;
            $curParent = $menuList[$curParent]["parent"];
        }
        $itemID++;
        $expanded = 0;
        // Grab the URI of the page (if possible), to determine whether or
        // not we should expand this item.  If there is no matching URI
        // then we should expand the item since we will have no other way 
        // of knowing whether or not to expand it.
        $parsedItem = $this->parseChunk($tmpItem);
        $itemURI = "";
        if (preg_match("/href=\"(.*?)\"/s", $parsedItem, $matches)) {
            $itemURI = $matches[1];
        }
        if (strlen($itemURI)) {
            if (!strcmp($_SERVER['REQUEST_URI'], $itemURI)) {
                $expanded = 1;
            }
        } else {
            $expanded = 1;
        }

        if ($expanded) {
            // Walk through and expand all of our parent items as well.
            if ($curParent) {
                $tmpParent = $curParent;
                while ($tmpParent) {
                    $menuList[$tmpParent]["expanded"] = 1;
                    $tmpParent = $menuList[$tmpParent]["parent"];
                }
            }
        }


        $curItem = array();
        $curItem["ItemID"] = $itemID;
        $curItem["level"] = $curLevel;
        $curItem["item"]  = $tmpItem;
        //$curItem["parsedItem"] = $app->parseChunk($tmpItem);
        $curItem["parsedItem"] = $tmpItem;
        $curItem["itemURI"] = $itemURI;
        $curItem["parent"]   = $curParent;
        $curItem["expanded"] = $expanded;
        array_push($menuList, $curItem);

        $itemEntry = array();
        $indent = "";
        while(ereg("^:", $item) !== false) {
            $item = ereg_replace("^:", "", $item);
            $indent .= "&nbsp;&nbsp;";
        }
        if (strlen($item)) {
            $retVal .= $indent . $item . "<br>";
        }
        if (ACMS_DEBUG) {
            $this->writeLog("Added item '$indent$item' to the menu.");
        }
    }

    // Build the menu now.
    $retVal = $this->addMenuItems($opts, $menuList, 0);
    if (ACMS_DEBUG) {
        //$app->writeLog("Done building menu '$menuChunk'");
    }


    //echo "<pre>"; print_r($menuList); echo "</pre>";

    return $retVal;
}

function addMenuItems($opts, $menuList, $parent)
{
    if (ACMS_DEBUG) {
        $this->writeLog("menuHandler:addItems ($opts, list, $parent)");
    }
    $retVal = "";
    foreach($menuList as $item) {
        if ($item["parent"] == $parent) {
            if ($item["level"]) {
                for ($i = 0; $i < $item["level"]; $i++) {
                    $retVal .= $opts["Indent"];
                }
            }
            if (ereg("^!", $item["item"])) {
                $retVal .= ereg_replace("^!", "", $item["item"]) . "<br>";
            } else {
                if ($item["expanded"]) {
                    if (isset($item["hasChildren"]) && $item["hasChildren"] > 0) {
                        $retVal .= $opts["ExpandedIndicator"];
                    } else {
                        $retVal .= $opts["IndicatorSpacer"];
                    }
                    $retVal .= $item["item"];
                    $retVal .= "<br>";
                    if ($item["ItemID"]) {
                        $retVal .= $this->addMenuItems($opts, $menuList, $item["ItemID"]);
                    }
                } else {
                    if (isset($item["hasChildren"]) && $item["hasChildren"] > 0) {
                        $retVal .= $opts["ChildIndicator"];
                    } else {
                        $retVal .= $opts["IndicatorSpacer"];
                    }
                    $retVal .= $item["item"];
                    $retVal .= "<br>";
                }
            }
        }
    }
    return $retVal;
}

};  // acmsApp class

/*! \brief A shorthand Smarty template class 
**
** acmsTemplate does all of the work for setting "global" variables and 
** parsing for a Smarty based template.
**
*/
class acmsTemplate extends Smarty {

var $myTpl;

/*! \brief The constructor for the acmsTemplate
**
** \param tplfile The template file to load and use.
** \param cache Cache the output of the template, default off.
** \param csecs If cache is enabled, then this states how long to cache it.
*/
function acmsTemplate($tplfile, $cache = 0, $csecs = 3600)
{
    global $INCLUDE_DIR;
    global $ACMSVersion;
    global $ACMSCfg;

    $this->myTpl = $tplfile;
    $this->template_dir = $INCLUDE_DIR . "templates";
    $this->compile_dir  = SMARTY_COMPILE_DIR;
    $this->cache_dir    = SMARTY_CACHE;
    $this->caching = $cache;
    $this->cache_lifetime = $csecs;

    // Update our defaults now based on our ini file settings.
    if (isset($ACMSCfg['Templates']['TemplatePath'])) {
        $this->template_dir = $ACMSCfg['Templates']['TemplatePath'];
    }
    if (isset($ACMSCfg['Templates']['CompileDir'])) {
        $this->compile_dir = $ACMSCfg['Templates']['CompileDir'];
    }
    if (isset($ACMSCfg['Templates']['CacheDir'])) {
        $this->cache_dir = $ACMSCfg['Templates']['CacheDir'];
    }
    //$this->assign("CCCURL",    $CCCServer);
    $this->assign("ACMSVersion",$ACMSVersion);
    //$this->assign("Script",    $Script);
    //$this->assign("ScriptSSL", $ScriptSSL);
    //$this->assign("MyURL",     $MyURL);
    //$this->assign("MyURLSSL",  $MyURLSSL);
    //$this->assign("Module",    "mod=" . $Module);
}

/*! \brief Convenience function to parse and display the template file
**
** \return The parsed output from the template.
*/

function get()
{
    return $this->fetch($this->myTpl);
}


}   // class acmsTemplate

?>
