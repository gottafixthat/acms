<?php
/**
 * mod_stories.php - The module that is responsible for handling stories
 *                   or announcements.
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
if (eregi('mod_stories.php', $_SERVER['PHP_SELF'])) die ("This file may not be loaded directly.");

// The global handlers variable that we will load ourselves into.
global $handlers, $modules;

$modules['stories'] = new storyModule();

// The module class.
class storyModule {

var $count = 5;     // The default number of stories to show in a "List" of headlines
var $rssCount = 5;
var $order = "Chunks_story.PostDate desc";   // Sort order.
var $baseWeight = 100;              // Our base weight for adding blocks.
var $showEmptyCats = false;         // Show categories with no stories?
var $pageTitle     = "Stories";
var $pageSep       = "@@page@@";
var $printable     = 0;
var $showExpired   = 1;
var $beingSpidered = 0;

var $templatePath;
var $topicList = array();

/*
** storyModule - Constructor.  This handles the setup of the various internal
**               functions.
*/

function storyModule()
{
    // FIXME: There should be an easy way to override templates with a theme.
    $parts = pathinfo(__FILE__);
    $this->templatePath = $parts["dirname"] . "/";
} // storyModule

/*
** exec - Entry point.  This is the function that gets called by the ACMS
**        kernel when the module is loaded.  It should check for arguments
**        and then call the appropriate internal function.
*/

function exec($action, $optArgs = array())
{
    global $app;

    $retVal = "";
    $args = "";

    //echo "action = '$action'<br><pre>"; print_r($args); echo "</pre>";
    //echo "optArgs = '$optArgs'<br><pre>"; print_r($optArgs); echo "</pre>";
    if (!is_array($optArgs)) {
        $cArgs = $app->parseArgs($optArgs);
        //echo "<pre>"; print_r($cArgs); echo "</pre>";
        if (isset($cArgs["print"])) $printable = 1;
    }

    if (!strcmp($action, "exec")) {
        $tmpArgs = "";
        if (is_array($optArgs)) {
            if (count($args)) {
                $tmpArgs = join("/", $args);
            } else {
                $tmpArgs = "";
            }
        } else $tmpArgs = $optArgs;
        if (!strlen($tmpArgs)) {
            $action = "";
        } else {
            list($action, $args) = split("/", $tmpArgs, 2);
        }
    }

    /*
    $weight = sprintf("%06d%s", 100000, date("YmdHis"));
    $app->addBlock($weight, CONTENT_ZONE, "Story", "A story would go here.  '$action'");
    */

    foreach($optArgs as $key => $val) {
        switch ($key) {
            case "count":
                $this->count = $val;
                break;

            default:
                $app->writeLog("stories:exec - unknown option '$key'");
                break;
        }
    }

    // If this is phpdig spidering us, don't give them the printable
    // version of the article.
    $this->beingSpidered = 0;
    if (eregi('^PhpDig', $_SERVER['HTTP_USER_AGENT'])) {
        $this->beingSpidered = 1;
    }

    //$cArgs = $app->parseArgs($action);
    //echo "<pre>"; print_r($cArgs); echo "</pre>";
    //$args = "";
    if (strpos($action, "/") !== FALSE) {
        list($action, $args) = split("/", $action, 2);
    }
    //echo "<pre>"; print_r($action); echo "</pre>";
    //echo "$action, $args<br>";

    if (strlen($args)) {
        switch($args) {
            case "print":
                $this->printable = 1;
                break;

            default:
                $app->writeLog("stories:exec - unknown option '$args'");
                break;
        }
    }

    // Check to see if our action is a ChunkName that holds a story.
    $storyName = "";
    $cc = new chunkClass("story");
    //echo "Action = '$action'...<br>";
    if ($cc->Fetch($action, "story")) {
        $storyName = $action;
        $action    = "Show";
    }


    switch ($action) {
        case "Show":
            $this->showStory($storyName);
            break;

        case "List":
            $this->listStories($args, 0);
            break;

        case "headlines.rss":
            $this->rssHeadlines();
            break;

        case "Headlines":
            $this->listStories($args, 1);
            break;

        case "HeadlinesBrief":
            $retVal = $this->headlinesBrief($args);
            break;

        case "Topics":
        default:
            $this->listTopics();
            break;
    }

    return $retVal;
}   // exec

/*
** listStories - Lists the story summaries for a given topic.
**               If Headlines == 0, then all of the stories for a topic
**               will be listed, including expired.
*/

function listStories($category, $headlinesOnly)
{
    global  $app;

    $pubCount = 0;      // How many headlines have we published?
    $catID = 0;
    if (is_numeric($category)) $catID = $category;

    // Get the chunk ID's of the stories to display.
    $curDate = date("Y-m-d H-i-s");
    $expPart = "and Chunks_story.ExpireDate > '$curDate'";
    if (!$headlinesOnly) $expPart = "";

    $catTable = "";
    $catWhere = "";
    if ($catID) {
        $catTable = ", CategoryItems";
        $catWhere = " and Chunks.ChunkID = CategoryItems.ChunkID and CategoryItems.CategoryID = $catID";
    }
    $sql = "select Chunks.ChunkName, Chunks_story.PostDate from Chunks, Chunks_story $catTable where Chunks.Handler = 'story' and Chunks_story.ChunkID = Chunks.ChunkID and Chunks_story.PostDate < '$curDate' $expPart $catWhere order by " . $this->order;
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->writeLog("stories:listHeadlines - error performing query '$sql'");
        return;
    }
    
    // If there are no stories, return.
    if (!mysql_num_rows($result)) {
        return;
    }

    $storyIDs = array();
    // Load them into memory.  They are in order.
    while ($curRow = mysql_fetch_array($result)) {
        array_push($storyIDs, $curRow["ChunkName"]);
    }

    $showCount = 0;
    // Go through the chunks until we're done.
    foreach($storyIDs as $chunkName) {
        $cc = new chunkClass("story");
        // echo "$chunkName<br>";
        if ($cc->fetch($chunkName)) {
            $showIt = 1;
            // Check for a category ID
            if ($catID) {
                $showIt = $app->isInCategory($catID, $cc->get("ChunkID"));
            }
            if (!$showIt) continue;
            $showCount++;
            // Search for a page separator.
            $myChunk  = $this->categoryIcon($cc->get("ChunkID"), 1);
            //$app->writeLog("myChunk = '$myChunk'");
            $myChunk .= $cc->parse();
            $extra   = "";
            // Since its been parsed, get the whole page break.
            if (strpos($myChunk, $this->pageSep) !== FALSE) {
                list($myChunk, $extra) = split($this->pageSep, $myChunk, 2);
            }
            $weight = $this->baseWeight+$pubCount; 
            // We need to set the block we use as well.
            $tBlock = "";
            $fNav = "";
            $tNav = "";
            if ($app->hasAccess("write", $cc->get("Perms"), $cc->get("UserID"), $cc->get("GroupID"))) {
                $tNav = "<a href=\"/admin/EditChunk/" . $cc->get("ChunkID") . "\">Edit</a>&nbsp;";
            }
            /*
            if (!$this->printable && !$this->beingSpidered) {
                $tNav .= "<a href=\"/stories/" . urlencode($cc->get("ChunkName")) . "/print\"><img alt=\"Print\" title=\"Printable version\" border=\"0\" src=\"/static/icons/print.gif\"></a>";
            }
            */
            if (strlen($extra)) {
                //$tBlock = "block_content.tpl";
                $fNav   = "<a href=\"/stories/" . $cc->get("ChunkName") . "\">Read more...<a>";
                $fNav  .= " (" . strlen($extra) . " bytes more)";
            } else {
                //$tBlock = "block_content.tpl";
            }
            $title =  "<b>" . $cc->get("Title") . "</b><br>";
            // Make a friendly date.
            $fTime = $cc->get("PostDate");
            $uTime = strtotime($fTime);
            if ($uTime != -1) {
                $todayStr = date("m/d/y", strtotime("now"));
                $yestStr  = date("m/d/y", strtotime("yesterday"));
                if (!strcmp($todayStr, date("m/d/y", $uTime))) {
                    $fTime = "Today at " . date("h:ia", $uTime);
                } else {
                    if (!strcmp($yestStr, date("m/d/y", $uTime))) {
                        $fTime = "Yesterday at " . date("h:ia", $uTime);
                    } else {
                        $fTime  = "on " . date("m/d/y", $uTime);
                        $fTime .= " at " . date("h:ia", $uTime);

                    }
                }
            }

            $title .= "Posted by " . $app->getUser($cc->get("Submitter")) . " $fTime "; // . $cc->get("PostDate");
            $app->addBlock($weight, CONTENT_ZONE, $title, $myChunk, "$fNav", array("FooterNav" => $fNav, "TitleNav" => $tNav), $tBlock);
            //$app->writeLog($myChunk);
            $pubCount++;
        }
        if ($headlinesOnly && $pubCount >= $this->count) {
            break;
        }
    }

    if (!$showCount) {
        $app->addBlock(10, CONTENT_ZONE, "Stories", "No matching stories were found.");
    } else {
        // Set the page title if we need to.
        if (!strlen($app->pageTitle)) {
            if ($catID) {
                $app->setPageTitle("Stories - " . $app->getCategoryTitle($catID));
            } else {
                $app->setPageTitle($this->pageTitle);
            }
        }
    }
}

/*
** showStory - Shows a story/article to the user.
*/

function showStory($storyName)
{
    global $app, $siteConfig;
    // echo "Showing story '$storyName'...<br>";
    $cc = new chunkClass("story");
    if ($this->printable) {
        $app->setPageBlock("PrintablePage");
        $app->setGlobal("Printable", "1");
    }
    if ($cc->Fetch($storyName, "story")) {
        $tNav = "";
        if ($app->hasAccess("write", $cc->get("Perms"), $cc->get("UserID"), $cc->get("GroupID"))) {
            $tNav = "<a href=\"/admin/EditChunk/" . $cc->get("ChunkID") . "\">Edit</a>&nbsp;";
        }
        if (!$this->printable && !$this->beingSpidered) {
            $tNav .= "<a href=\"/stories/" . urlencode($storyName) . "/print\"><img alt=\"Print\" title=\"Printable version\" border=\"0\" src=\"/static/icons/print.gif\"></a>";
        }
        $myChunk  = $this->categoryIcon($cc->get("ChunkID"));
        $myChunk .= $cc->parse();
        $myChunk  = str_replace($this->pageSep, "<p>", $myChunk);
        $weight = $cc->get("Weight");
        // We need to set the block we use as well.
        $title =  "<b>" . $cc->get("Title") . "</b><br>";
        $title .= "Posted by " . $app->getUser($cc->get("Submitter")) . " on " . $cc->get("PostDate");
        $app->addBlock($weight, CONTENT_ZONE, $title, $myChunk, "", array("TitleNav" => $tNav));
        $app->setPageTitle($cc->get("Title"));

        // If we were printable, then add a block at the bottom of the page 
        // to show where things came from and how to get back.
        if ($this->printable) {
            $fBlock  = "\n----\n[[/stories/" . $cc->get("ChunkName") . " " . $cc->get("Title") . "]], printed from ";
            $fBlock .= "[[http://" . $_SERVER['SERVER_NAME'] . " " . $siteConfig['SiteName'] . "]]";
            $app->addBlock(500, FOOTER_ZONE, "", $app->parseChunk($fBlock, 1));
        }
    }

}

/*
** listTopics - Displays a list of all categories that have topics in them.
*/

function listTopics()
{
    global  $app;
    
    // We need a second database connection.
    $this->topicList = array();
    $content = $this->getChildTopics(0, 0);

    if (!count($this->topicList)) {
        $app->addBlock(0, CONTENT_ZONE, "List Story Topics", "No stories were found.");
        return;
    }

    // Create our template.
    $tpl = new acmsTemplate($this->templatePath . "topiclist.tpl");
    $topics = array();

    foreach($this->topicList as $item) {
        $showIt = true;
        if (!$this->showEmptyCats) {
            // They don't want to show items with no categories.
            // Check to see if this particular category has any 
            // stories.
            $sql = "select Chunks.ChunkID, CategoryItems.CategoryItemID from Chunks, CategoryItems where Chunks.ChunkID = CategoryItems.ChunkID and Chunks.Handler = 'story' and CategoryItems.CategoryID = " . $item["CategoryID"];
            $result = mysql_query($sql, $app->acmsDB);
            if ($result) {
                if (!mysql_num_rows($result)) {
                    $showIt = false;
                }
            }
        }
        if (!$showIt) continue;
        $viewLink  = "<a href=\"/stories/List/" . $item["CategoryID"] . "\">";
        $viewLink .= $item["Title"];
        $viewLink .= "</a>";
        $iconLink  = "<a title=\"" . $item["Title"] . "\" href=\"/stories/List/" . $item["CategoryID"] . "\">";
        $iconLink .= $app->parseChunk($item["IconTag"]);
        $iconLink .= "</a>";
        $level = "";
        $levelOpen  = "";
        $levelClose = "";
        if ((int)$item["Level"]) {
            for ($i = 0; $i < (int)$item["Level"]; $i++) {
                $levelOpen .= "<div class=\"indent\">";
                $levelClose.= "</div>";
                $level .= "&nbsp;&nbsp;";
            }
        }
        array_push($topics, array(
                    "CategoryID"        => $item["CategoryID"],
                    "ParentID"          => $item["ParentID"],
                    "Title"             => $item["Title"],
                    "IconTag"           => $item["IconTag"],
                    "IconParsed"        => $iconLink,
                    "Description"       => $item["Description"],
                    "Level"             => $level,
                    "LevelOpen"         => $levelOpen,
                    "LevelClose"        => $levelClose,
                    "ViewLink"          => $viewLink
                    ));
    }
    $tpl->assign("Topics", $topics);

    $app->setPageTitle($this->pageTitle);
    $app->addBlock(0, CONTENT_ZONE, "Story Topics", $tpl->get());
}

function getChildTopics($parentID, $level)
{
    global $app;
    $retVal = "";
    $myDBConn = $app->newDBConnection();
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
                array_push($this->topicList, $item);

                if ($level) {
                    for ($i = 0; $i < $level; $i++) {
                        $retVal .= "*";
                    }
                }
                $retVal .= $curRow["Title"];
                $retVal .= "\n";
                $retVal .= $this->getChildTopics($curRow["CategoryID"], $level+1);
            }
        }
    }

    return $retVal;
}

/*
** categoryIcon - Returns the icon(s) for the categories that the specified
**                chunk is in.  It returns them in chunk format, so they
**                need to be sent through parseChunk.
*/

function categoryIcon($chunkID, $limit = 0, $rss = 0)
{
    global $app;
    $dbConn = $app->newDBConnection();
    $catsAdded = 0;
    $retVal = "";
    $viewLink = "";

    $tpl = new acmsTemplate($this->templatePath . "categoryiconlist.tpl");
    //$app->writeLog("Template path = '" . $this->templatePath . "cateogryiconlist.tpl'");
    $iconList = array();

    //$tpl->setCurrentBlock("CategoryIconList");

    //$retVal .= "<table align=\"right\">";
    $sql = "select distinct Categories.IconTag, Categories.RSSIMG, Categories.Title, Categories.CategoryID from Categories, CategoryItems where CategoryItems.ChunkID = $chunkID and Categories.CategoryID = CategoryItems.CategoryID";
    $result = mysql_query($sql, $dbConn);
    if ($result) {
        if (mysql_num_rows($result)) {
            while ($curRow = mysql_fetch_array($result)) {
                if ($rss) {
                    $viewLink = "<img src=\"http://" . $_SERVER["SERVER_NAME"] . $curRow["RSSIMG"] . "\" align=\"right\" class=\"right\" hspace=\"0\" vspace=\"0\" alt=\"" . $curRow["Title"] . "\">";
                } else {
                    $viewLink  = "<a class=\"nounderline\" title=\"" . $curRow["Title"] . "\" href=\"/stories/List/" . $curRow["CategoryID"] . "\">";
                    $viewLink .= $app->parseChunk($curRow["IconTag"]);
                    $viewLink .= "</a>";
                    array_push($iconList, array(
                                "IconTag" => $viewLink
                                ));
                    //$app->writeLog("Adding '$viewLink' as an IconTag");
                    //$tpl->setCurrentBlock("CategoryIconItem");
                    //$tpl->setVariable("IconTag",    $viewLink);
                    //$tpl->parseCurrentBlock("CategoryIconItem");
                }
                $catsAdded++;
                if ($limit) {
                    if ($catsAdded >= $limit) break;
                }
            }
        }
    }
    if ($rss) {
        $retVal = $viewLink;
    } else {
        //$tpl->setCurrentBlock("CategoryIconList");
        //$tpl->parseCurrentBlock("CategoryIconList");
        if (!$catsAdded) {
            $retVal = "";
        } else {
            //echo "<pre>"; print_r($iconList); echo "</pre>";
            $tpl->assign("IconList",    $iconList);
            $retVal .= $tpl->get();
        }
    }
    return $retVal;
}

/*
** rssHeadlines - Lists the story summaries for a given topic.
**                If Headlines == 0, then all of the stories for a topic
**                will be listed, including expired.
*/

function rssHeadlines()
{
    global  $app, $INCLUDE_DIR, $ACMSCfg;

    // Get load the RSS generator class
    //require_once($INCLUDE_DIR . "xmlwriterclass.php");
    //require_once($INCLUDE_DIR . "rss_writer_class.php");
    require_once("feedcreator.class.php");

    $pubCount = 0;      // How many headlines have we published?
    $catID = 0;

    $rss = new UniversalFeedCreator();
    $rss->useCached();      // use cached version if age < 1 hour
    $rss->title = $ACMSCfg['mod_stories']['rssTitle'];
    $rss->description = $ACMSCfg['mod_stories']['rssDescription'];
    $rss->link        = "http://" . $_SERVER["SERVER_NAME"] . "/";
    $rss->cssStyleSheet = "http://www.w3.org/2000/08/w3c-synd/style.css";
    $rss->syndicationURL = "http://" . $_SERVER["SERVER_NAME"] . "/" . $_SERVER["PHP_SELF"]; 
    $rss->descriptionHtmlSyndicated = true;
    $rss->editor = "";
    $rss->webmaster= "";
    $rss->pubDate = "";
    $rss->category = "";
    $rss->docs = "";
    $rss->ttl = "";
    $rss->rating = "";
    $rss->skipHours = "";
    $rss->skipDays = "";

    $img = new FeedImage();
    $img->title = $ACMSCfg['mod_stories']['rssImgTitle'];
    $img->url = "http://" . $_SERVER["SERVER_NAME"] . $ACMSCfg['rssImgPath'];
    $img->link = "http://" . $_SERVER["SERVER_NAME"] . "/";
    $img->description = $ACMSCfg['mod_stories']['rssImgDesc'];
    $rss->image = $image;
    $rss->language = "";
    $rss->copyright = $ACMSCfg['mod_stories']['rssCopyright'];

    //error_reporting(0);

    // Get the chunk ID's of the stories to display.
    $curDate = date("Y-m-d H-i-s");
    $expPart = "and Chunks_story.ExpireDate > '$curDate'";

    $catTable = "";
    $catWhere = "";
    if ($catID) {
        $catTable = ", CategoryItems";
        $catWhere = " and Chunks.ChunkID = CategoryItems.ChunkID and CategoryItems.CategoryID = $catID";
    }
    $sql = "select Chunks.ChunkName, Chunks_story.PostDate from Chunks, Chunks_story $catTable where Chunks.Handler = 'story' and Chunks_story.ChunkID = Chunks.ChunkID and Chunks_story.PostDate < '$curDate' $expPart $catWhere order by " . $this->order;
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->writeLog("stories:listHeadlines - error performing query '$sql'");
        return;
    }
    
    // If there are no stories, return.
    if (!mysql_num_rows($result)) {
        return;
    }

    $storyIDs = array();
    // Load them into memory.  They are in order.
    while ($curRow = mysql_fetch_array($result)) {
        array_push($storyIDs, $curRow["ChunkName"]);
    }

    $showCount = 0;
    // Go through the chunks until we're done.
    foreach($storyIDs as $chunkName) {
        $cc = new chunkClass("story");
        // echo "$chunkName<br>";
        if ($cc->fetch($chunkName)) {
            $showIt = 1;
            // Check for a category ID
            if ($catID) {
                $showIt = $app->isInCategory($catID, $cc->get("ChunkID"));
            }
            if (!$showIt) continue;
            $showCount++;
            // Search for a page separator.
            $myChunk  = $this->categoryIcon($cc->get("ChunkID"), 1, 1);
            $myChunk .= $cc->parse();
            $extra   = "";
            // Since its been parsed, get the whole page break.
            if (strpos($myChunk, $this->pageSep) !== FALSE) {
                list($myChunk, $extra) = split($this->pageSep, $myChunk, 2);
            }
            $weight = $this->baseWeight+$pubCount; 

            // We need to set the block we use as well.
            $pubCount++;

            $item = new FeedItem();
            $item->title = $cc->get("Title");
            $item->link = "http://" . $_SERVER["SERVER_NAME"] . "/stories/" . $cc->get("ChunkName");
            $item->description = $myChunk;
            $item->source = "http://" . $_SERVER["SERVER_NAME"] . "/";
            $item->author = $ACMSCfg['mod_stories']['rssAuthor'];
            $pDate = strtotime($cc->get("PostDate"));
            $curDate = date("Y-m-d H-i-s");
            $item->pubDate   = date("Y-m-d", $pDate) . "T" . date("H-i-s");
            $item->date   = date("r", $pDate);
            $item->descriptionHtmlSyndicated =true;
            $item->descriptionTruncSize = 1000;
            $item->category = "";
            $item->comments = "";
            $item->guid = "";
            $rss->addItem($item);
        }
        if ($pubCount >= $this->rssCount) {
            break;
        }
    }

    if (!$showCount) {
        $app->addBlock(10, CONTENT_ZONE, "Stories", "No matching stories were found.");
    } else {
        // Set the page title if we need to.
        if (!strlen($app->pageTitle)) {
            if ($catID) {
                $app->setPageTitle("Stories - " . $app->getCategoryTitle($catID));
            } else {
                $app->setPageTitle($this->pageTitle);
            }
        }
    }

    $content = "";
    ob_end_clean();
    echo $rss->saveFeed("RSS2.0", "/tmp/feed.xml");
    /*
    if ($rss->writerss($content)) {
        ob_end_clean();
        header("Content-Type: text/xml; charset=\"".$rss->outputencoding."\"");
        Header("Content-Length: ".strval(strlen($content)));
        echo $content;
    } else {
        echo "Error generating rss feed.";
    }
    */
    exit;
}

/*
** headlinesBrief - Lists the story summaries for a given topic.
*/

function headlinesBrief($category)
{
    global  $app;
    $retVal = "";

    $pubCount = 0;      // How many headlines have we published?
    $catID = 0;
    $sList = array();   // The list of stories we will pass to the template
    
    if (is_numeric($category)) $catID = $category;

    // Get the chunk ID's of the stories to display.
    $curDate = date("Y-m-d H-i-s");
    $expPart = "and Chunks_story.ExpireDate > '$curDate'";

    $catTable = "";
    $catWhere = "";
    if ($catID) {
        $catTable = ", CategoryItems";
        $catWhere = " and Chunks.ChunkID = CategoryItems.ChunkID and CategoryItems.CategoryID = $catID";
    }
    $sql = "select Chunks.ChunkName, Chunks_story.PostDate from Chunks, Chunks_story $catTable where Chunks.Handler = 'story' and Chunks_story.ChunkID = Chunks.ChunkID and Chunks_story.PostDate < '$curDate' $expPart $catWhere order by " . $this->order;
    $result = mysql_query($sql, $app->acmsDB);
    if (!$result) {
        $app->writeLog("stories:headlinesBrief - error performing query '$sql'");
        return;
    }
    
    // If there are no stories, return.
    if (!mysql_num_rows($result)) {
        return;
    }

    $storyIDs = array();
    // Load them into memory.  They are in order.
    while ($curRow = mysql_fetch_array($result)) {
        array_push($storyIDs, $curRow["ChunkName"]);
    }

    $showCount = 0;
    // Go through the chunks until we're done.
    foreach($storyIDs as $chunkName) {
        $curStory = array();
        $cc = new chunkClass("story");
        // echo "$chunkName<br>";
        if ($cc->fetch($chunkName)) {
            $showIt = 1;
            // Check for a category ID
            if ($catID) {
                $showIt = $app->isInCategory($catID, $cc->get("ChunkID"));
            }
            if (!$showIt) continue;
            $showCount++;
            // Search for a page separator.
            $myChunk  = $this->categoryIcon($cc->get("ChunkID"), 1);
            //$app->writeLog("myChunk = '$myChunk'");
            $myChunk .= $cc->parse();
            $extra   = "";
            // Since its been parsed, get the whole page break.
            if (strpos($myChunk, $this->pageSep) !== FALSE) {
                list($myChunk, $extra) = split($this->pageSep, $myChunk, 2);
            }
            $weight = $this->baseWeight+$pubCount; 
            // We need to set the block we use as well.
            $tBlock = "";
            $fNav = "";
            $tNav = "";
            if ($app->hasAccess("write", $cc->get("Perms"), $cc->get("UserID"), $cc->get("GroupID"))) {
                $tNav = "<a href=\"/admin/EditChunk/" . $cc->get("ChunkID") . "\">Edit</a>&nbsp;";
            }
            /*
            if (!$this->printable && !$this->beingSpidered) {
                $tNav .= "<a href=\"/stories/" . urlencode($cc->get("ChunkName")) . "/print\"><img alt=\"Print\" title=\"Printable version\" border=\"0\" src=\"/static/icons/print.gif\"></a>";
            }
            */
            $curStory['chunkname'] = $cc->get("ChunkName");
            $curStory['chunkid']   = $cc->get("ChunkID");
            if (strlen($extra)) {
                //$tBlock = "block_content.tpl";
                $curStory['fullstorylink'] = "/stories/" . $cc->get("ChunkName");
                $curStory['additionalbytes'] = strlen($extra);
                //$fNav   = "<a href=\"/stories/" . $cc->get("ChunkName") . "\">Read more...<a>";
                //$fNav  .= " (" . strlen($extra) . " bytes more)";
            } else {
                //$tBlock = "block_content.tpl";
            }
            $curStory['title'] = $cc->get("Title");
            //$title =  "<b>" . $cc->get("Title") . "</b><br>";
            // Make a friendly date.
            $fTime = $cc->get("PostDate");
            $uTime = strtotime($fTime);
            if ($uTime != -1) {
                $todayStr = date("m/d/y", strtotime("now"));
                $yestStr  = date("m/d/y", strtotime("yesterday"));
                if (!strcmp($todayStr, date("m/d/y", $uTime))) {
                    $fTime = "Today at " . date("h:ia", $uTime);
                } else {
                    if (!strcmp($yestStr, date("m/d/y", $uTime))) {
                        $fTime = "Yesterday at " . date("h:ia", $uTime);
                    } else {
                        $fTime  = "on " . date("m/d/y", $uTime);
                        $fTime .= " at " . date("h:ia", $uTime);

                    }
                }
            }
            $curStory['postdate'] = $fTime;
            $curStory['author'] = $app->getUser($cc->get("Submitter"));
            $curStory['content'] = str_replace("\$", "&#036;", $myChunk);

            if (!$pubCount) $curStory['expanded'] = 1;
            else $curStory['expanded'] = 0;

            array_push($sList, $curStory);
            //$title .= "Posted by " . $app->getUser($cc->get("Submitter")) . " $fTime "; // . $cc->get("PostDate");
            //$app->addBlock($weight, CONTENT_ZONE, $title, $myChunk, "$fNav", array("FooterNav" => $fNav, "TitleNav" => $tNav), $tBlock);
            //$app->writeLog($myChunk);
            $pubCount++;
        }
        if ($pubCount >= $this->count) {
            break;
        }
    }

    if ($showCount) {
        // We have some articles to show.
        $tpl = new acmsTemplate($this->templatePath . "briefheadlines.tpl");
        $tpl->assign("Stories", $sList);
        $retVal = $tpl->get();
    }

    return $retVal;
}

};  // storyModule class

?>
