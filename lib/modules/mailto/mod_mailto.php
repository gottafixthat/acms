<?php
/**
 * mod_mailto.php - Safely handles email being sent from the web site.
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
if (eregi('mod_mailto.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers, $modules;

$modules['mailto'] = new mailtoModule();

// The module class.
class mailtoModule {

var $templatePath;

var $validRcpts;

// Form variables.
var $recipient;
var $rcptName;
var $sendername;
var $senderemail;
var $subject;
var $message;


/*
** mailtoModule - Constructor.  This handles the setup of the various 
**                internal functions.
*/

function mailtoModule()
{
    global $ACMSCfg;
    // FIXME: There should be an easy way to override templates with a theme.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts["dirname"] . "/mailto_";
    $this->recipient   = "";
    $this->rcptName    = "";
    $this->subject     = "";
    $this->message     = "";
    $this->sendername  = "";
    $this->senderemail = "";

    $this->validRcpts = array();
    // Load the valid recipeints
    $tags = split(':', $ACMSCfg['mod_mailto']['tags']);
    foreach($tags as $tag) {
        $ctag = 'tag_' . $tag;
        if (!empty($ACMSCfg['mod_mailto'][$ctag])) {
            list($addr,$name) = split(':', $ACMSCfg['mod_mailto'][$ctag],2);
            $this->validRcpts[] = array('tag' => $tag, 'addr' => $addr, 'name' => $name);
        }
    }

} // mailtoModule

/*
** exec - Entry point.  This is the function that gets called by the ACMS
**        kernel when the module is loaded.  It should check for arguments
**        and then call the appropriate internal function.
*/

function exec($action, $args = array())
{
    global $app;

    $retVal  = "";
    $subj    = "";
    $tag     = "";
    $name    = "";

    //echo "action = '$action'<br><pre>"; print_r($args); echo "</pre>";

    // Check to see if we were loaded directly.  If we were, we show and/or
    // process the email form.  If we weren't, we create a link to ourselves
    // and return it.
    if (!strcmp("exec", $action) && eregi("^/mailto/", $_SERVER['REQUEST_URI'])) {
        // Check the referrer as well.  If its not us, we want to store
        // it away to use later.
        if (isset($_SERVER['HTTP_REFERER'])) {
            $tmpRef = $_SERVER['HTTP_REFERER'];
            
            $myRef = eregi_replace("http[s]?://" . $_SERVER['SERVER_NAME'], "", $tmpRef);
            //echo "$myRef<br>";

            if (!eregi("^/mailto/", $myRef)) {
                // myRef points to where we came from.
                $app->setSessVar("MailToRefURI", $myRef);
            }
        }

        // Check for arguments in the action.  This can be:
        // recipient/subject
        if (strpos($args, "/")) {
            list($tag, $subj) = split("/", urldecode($args), 2);
        } else {
            $tag = $args;
        }
        // Find the address based on the tag.  If we don't have one,
        // we'll abort with an error block.
        foreach($this->validRcpts as $rcpt) {
            if (!strcmp($tag, $rcpt["tag"])) {
                $tag  = $rcpt["tag"];
                $this->rcptName  = $rcpt["name"];
                $this->recipient = $rcpt["addr"];
            }
        }
        $this->subject = $subj;
        if (!strlen($this->recipient)) {
            $this->showErrorBlock("no_recipient", "No recipient specified");
            return;
        } else {
            $this->processMailto();
            return;
        }
    } else {
        // Check for a subject.  If there is one, put it in the URL.
        if (isset($args["subject"]) && strlen($args["subject"])) {
            $subj = "/" . urlencode($args["subject"]);
        }
        // Action will contain whomever they want to email.  Walk through
        // the list of actions we have and see if we have a match.  If we 
        // don't, take the first one.
        $tag  = $this->validRcpts[0]["tag"];
        $name = $this->validRcpts[0]["name"];
        foreach($this->validRcpts as $rcpt) {
            if (!strcmp($action, $rcpt["tag"])) {
                $tag  = $rcpt["tag"];
                $name = $rcpt["name"];
            }
        }
        // Setup the link to whomever they want us to email.
        // FIXME: This will only work if we're using mod_rewrite.
        $retVal = "<a href=\"/mailto/$tag$subj\">$name</a>";
    }


    return $retVal;
}   // exec

/*
** showErrorBlock - Shows them an error message letting them know that
**                  something wasn't right.
*/

function showErrorBlock($section, $title = "Unable to send message")
{
    global $app;

    // Parse our return path if it exists.
    $returnText = "";
    if (isset($app->acmsSessVars["MailToRefURI"])) {
        // Check to see if there is a chunk name matching the previous path.
        $chunkName = ereg_replace("^/", "", $app->acmsSessVars["MailToRefURI"]);
        $tmpChunk = "{{" . $chunkName . "}}";
        $tmpChunkParsed = $app->parseChunk($tmpChunk);
        if (eregi("a href=", $tmpChunkParsed)) {
            $returnText = "Return to $tmpChunkParsed.";
        } else {
            $returnText = "Return to <a href=\"" . $app->acmsSessVars["MailToRefURI"] . "\">previous page</a>.";
        }
    }

    $tpl = new acmsTemplate($this->templatePath . $section . ".tpl");

    $tpl->assign("ReturnToText", $returnText);
    $app->setPageTitle($title);
    $app->addBlock(10, CONTENT_ZONE, $title, $tpl->get());
}

/*
** setupForm - Sets up the mail form.
*/

function setupForm()
{
    global $app;

    $title   = "Send message to " . $this->rcptName;

    $tpl = new acmsTemplate($this->templatePath . "main_form.tpl");
    $tpl->assign("SenderName",     $this->sendername);
    $tpl->assign("SenderEmail",    $this->senderemail);
    $tpl->assign("Recipient",      $this->rcptName . " &lt;" . $this->recipient . "&gt");
    $tpl->assign("Subject",        $this->subject);
    $tpl->assign("Message",        $this->message);
    $app->setPageTitle($title);
    $app->addBlock(10, CONTENT_ZONE, $title, $tpl->get());
}

/*
** getFormVars - Extracts the form variables from the submitted form and
**               puts them into our local class variables.
*/

function getFormVars()
{
    if (isset($_REQUEST["subject"])) {
        $this->subject = $_REQUEST["subject"];
    }
    if (isset($_REQUEST["message"])) {
        $this->message = $_REQUEST["message"];
    }
    if (isset($_REQUEST["sendername"])) {
        $this->sendername = $_REQUEST["sendername"];
        $this->sendername = ereg_replace("[<>]", "", $this->sendername);
    }
    if (isset($_REQUEST["senderemail"])) {
        $this->senderemail = $_REQUEST["senderemail"];
    }
}

/*
** processMailto - Checks for form variables, if it finds them, it attempts
**                 to send the message.  If not, it shows the mail form.
*/

function processMailto()
{
    global $app;

    $this->getFormVars();

    if (!isset($_REQUEST["mailaction"]) || strcmp($_REQUEST["mailaction"], "send")) {
        $this->setupForm();
        return;
    }

    if (isset($_REQUEST["Cancel"]) && strlen($_REQUEST["Cancel"])) {
        $this->showErrorBlock("message_cancelled", "Message Cancelled");
        return;
    }

    // If we made it here, the user requested to send the message.
    // Validate the input.
    $errText = "";
    
    if (!strlen($this->sendername)) {
        $errText .= "<li>You must include your name";
    }
    if (!strlen($this->senderemail)) {
        $errText .= "<li>You must include your email address";
    } elseif (!eregi( "^[_\.0-9a-z-]+@([0-9a-z][0-9a-z-]+\.)+[a-z]{2,3}$", $this->senderemail)) {
        $errText .= "<li>Invalid email address specified";
    }
    if (!strlen($this->subject)) {
        $errText .= "<li>A subject must be specified";
    }
    if (!strlen($this->message)) {
        $errText .= "<li>You may not send an empty message";
    }

    if (strlen($errText)) {
        // Ooops.  We had an error.  Return.
        $errText = "Unable to send the message.  Correct the following errors and try again.<p><ul>" . $errText . "</ul>";
        $app->addBlock(0, CONTENT_ZONE, "Send Message - Error", $errText);
        $this->setupForm();
        return;
    }

    // If we made it to this point, we're good to send the message.

    $this->sendMessage();
}

/*
** sendMessage - Does the actual sending of the message, then returns them
**               to whence they came.
*/

function sendMessage()
{
    global $ACMSVersion;
    // Create the message.
    $to = $this->recipient;
    $fr = $this->sendername . " <" . $this->senderemail . ">";
    $su = stripslashes($this->subject);
    $me = stripslashes($this->message);
    $he  = "From: $fr";
    $he .= "\nX-Mailer: ACMS v$ACMSVersion\nX-Sender-IP: " . $_SERVER['REMOTE_ADDR'];

    // Add a tag so we know where the message came from.
    $me .= "\n\n----\nSent via " . $_SERVER['SERVER_NAME'] . ", from IP Address " . $_SERVER['REMOTE_ADDR'];
    
    // Now that the variables are set, call mail() to send it.
    if (!mail($to, $su, $me, $he)) {
        if (strlen($errText)) {
            // Ooops.  We had an error.  Return.
            $errText = "Unable to send the message at this time.  Please try again later.";
            $app->addBlock(0, CONTENT_ZONE, "Send Message - Error", $errText);
            $this->setupForm();
            return;
        }
    }

    // Let them know the message was sent.
    $this->showErrorBlock("message_sent", "Message Sent");
}


/*
** returnToPrevPage - Returns them to the page they were previously on.
*/

function returnToPrevPage()
{
}

};  // mailtoModule class

?>
