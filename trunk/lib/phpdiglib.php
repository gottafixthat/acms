<?php
/**
 * phpdiglib.php - A class derived from the phpdig search_function.php file
 *
 *                 The phpdig interface wasn't portable enough for use
 *                 in ACMS as it was, so this class was necessary.
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


set_time_limit(5);
global $INCLUDE_DIR;
require_once($INCLUDE_DIR . "phpdigencodings.php");

define('PHPDIG_VERSION','1.8.2');
define('PHPDIG_ENCODING','iso-8859-1');  // encoding for interface, search and indexing.
                                         // iso-8859-1, iso-8859-2, iso-8859-7, tis-620,
                                         // and windows-1251 supported in this version.
define('SEARCH_DEFAULT_MODE','start');   // default search mode (start|exact|any)
define('SMALL_WORDS_SIZE',2);            //words to not index - must be 2 or more
define('PHPDIG_DB_PREFIX','phpdig_');
define('NUMBER_OF_RESULTS_PER_SITE',-1); //max number of results per site
                                         // use -1 to display all results
define('SEARCH_PAGE','search.php');      //The name of the search page
define('CONTENT_TEXT',1);                //Activates/deactivates the
                                         //storage of text content.
define('TEXT_CONTENT_PATH','/shared/acms/phpdig/text_content/'); //Text content files path
define('DISPLAY_SNIPPETS',true);         //Display text snippets
define('DISPLAY_SNIPPETS_NUM',4);        //Max snippets to display
define('DISPLAY_SUMMARY',false);          //Display description
define('SUMMARY_DISPLAY_LENGTH',350);    //Max chars displayed in summary
define('SNIPPET_DISPLAY_LENGTH',350);    //Max chars displayed in each snippet
define('PHPDIG_DATE_FORMAT','\1-\2-\3');         // Date format for last update
                                                 // \1 is year, \2 month and \3 day
                                                 // if using rss, use date format \1-\2-\3


class phpDigClass {
    
// Configuration variables.  These would normally be found in 
// PhpDig's config.php


// Old "globals"
var $phpdigEncode;

// Localisation
//English messages for PhpDig
//Some corrections by Brien Louque
//'keyword' => 'translation'
var $phpdig_mess = array (
        'upd_sites'    =>'Update sites',
        'upd2'         =>'Update Done',
        'links_per'    =>'Links per',
        'yes'          =>'yes',
        'no'           =>'no',
        'delete'       =>'delete',
        'reindex'      =>'Re-index',
        'back'         =>'Back',
        'files'        =>'files',
        'admin'        =>'Administration',
        'warning'      =>'Warning !',
        'index_uri'    =>'Which URI would you index?',
        'spider_depth' =>'Search depth',
        'spider_warn'  =>"Please ensure that no one else is updating the same site.
        A locking mechanism will be included in a later version.",
        'site_update'  =>"Update a site or one of its branch",
        'clean'        =>'Clean',
        't_index'      =>"index",
        't_dic'        =>'dictionary',
        't_stopw'      =>'common words',
        't_dash'       =>'dashes',

        'update'       =>'Update',
        'exclude'      =>'Delete and exclude branch',
        'excludes'     =>'Exclude paths',
        'tree_found'   =>'Found tree',
        'update_mess'  =>'Re-index or delete a tree ',
        'update_warn'  =>"Exclude will delete indexed entries",
        'update_help'  =>'Click on the cross to delete the branch
        Click on the green sign to update it
        Click on the noway sign to exclude from future indexings',
        'branch_start' =>'Select the folder to display on the left side',
        'branch_help1' =>'Select there documents to update individually',
        'branch_help2' =>'Click on the cross to delete a document
        Click on the green sign to reindex it',
        'redepth'      =>'levels depth',
        'branch_warn'  =>"Erase is permanent",
        'to_admin'     =>"to admin interface",
        'to_update'    =>"to update interface",

        'search'       =>'Search',
        'results'      =>'results',
        'display'      =>'display',
        'w_begin'      =>'and operator',
        'w_whole'      =>'exact phrase',
        'w_part'       =>'or operator',
        'alt_try'      =>'Did you mean',

        'limit_to'     =>'limit to',
        'this_path'    =>'this path',
        'total'        =>'total',
        'seconds'      =>'seconds',
        'w_common_sing'     =>'is a very common word and was ignored.',
        'w_short_sing'      =>'is too short a word and was ignored.',
        'w_common_plur'     =>'are very common words and were ignored.',
        'w_short_plur'      =>'are too short of words and were ignored.',
        's_results'    =>'search results',
        'previous'     =>'Previous',
        'next'         =>'Next',
        'on'           =>'on',

        'id_start'     =>'Site indexing',
        'id_end'       =>'Indexing complete !',
        'id_recent'    =>'Was recently indexed',
        'num_words'    =>'Num words',
        'time'         =>'time',
        'error'        =>'Error',
        'no_spider'    =>'Spider not launched',
        'no_site'      =>'No such site in database',
        'no_temp'      =>'No link in temporary table',
        'no_toindex'   =>'No content indexed',
        'double'       =>'Duplicate of an existing document',

        'spidering'    =>'Spidering in progress...',
        'links_more'   =>'more new links',
        'level'        =>'level',
        'links_found'  =>'links found',
        'define_ex'    =>'Define exclusions',
        'index_all'    =>'index all',

        'end'          =>'end',
        'no_query'     =>'Please fill the search form field',
        'pwait'        =>'Please wait',
        'statistics'   =>'Statistics',

        // INSTALL
        'slogan'   =>'The smallest search engine in the universe : version',
        'installation'   =>'Installation',
        'instructions' =>'Type here the MySql parameters. Specify a valid existing user who can create databases if you choose create or update.',
        'hostname'   =>'Hostname  :',
        'port'   =>'Port (none = default) :',
        'sock'   =>'Sock (none = default) :',
        'user'   =>'User :',
        'password'   =>'Password :',
        'phpdigdatabase'   =>'PhpDig database :',
        'tablesprefix'   =>'Tables prefix :',
        'instructions2'   =>'* optional. Use lowercase characters, 16 characters max.',
        'installdatabase'   =>'Install phpdig database',
        'error1'   =>'Can\'t find connexion template. ',
        'error2'   =>'Can\'t write connexion template. ',
        'error3'   =>'Can\'t find init_db.sql file. ',
        'error4'   =>'Can\'t create tables. ',
        'error5'   =>'Can\'t find all config database files. ',
        'error6'   =>'Can\'t create database.<br />Verify user\'s rights. ',
        'error7'   =>'Can\'t connect to database<br />Verify connection datas. ',
        'createdb' =>'Create database',
        'createtables' =>'Create tables only',
        'updatedb' =>'Update existing database',
        'existingdb' =>'Write only connection parameters',
        // CLEANUP_ENGINE
        'cleaningindex'   =>'Cleaning index',
        'enginenotok'   =>' index references targeted an inexistent keyword.',
        'engineok'   =>'Engine is coherent.',
        // CLEANUP_KEYWORDS
        'cleaningdictionnary'   =>'Cleaning dictionary',
        'keywordsok'   =>'All keywords are in one or more page.',
        'keywordsnotok'   =>' keywords where not in one page at least.',
        // CLEANUP_COMMON
        'cleanupcommon' =>'Cleanup common words',
        'cleanuptotal' =>'Total ',
        'cleaned' =>' cleaned.',
        'deletedfor' =>' deleted for ',
        // INDEX ADMIN
        'digthis' =>'Dig this !',
        'databasestatus' =>'DataBase status',
        'entries' =>' Entries ',
        'updateform' =>'Update form',
        'deletesite' =>'Delete site',
        // SPIDER
        'spiderresults' =>'Spider results',
        // STATISTICS
        'mostkeywords' =>'Most keywords',
        'richestpages' =>'Richest pages',
        'mostterms'    =>'Most search terms',
        'largestresults'=>'Largest results',
        'mostempty'     =>'Most searchs giving empty results',
        'lastqueries'   =>'Last search queries',
        'lastclicks'   =>'Last search clicks',
        'responsebyhour'=>'Response time by hour',
        // UPDATE
        'userpasschanged' =>'User/Password changed !',
        'uri' =>'URI : ',
        'change' =>'Change',
        'root' =>'Root',
        'pages' =>' pages',
        'locked' => 'Locked',
        'unlock' => 'Unlock site',
        'onelock' => 'A site is locked, because of spidering. You can\'t do this for now',
        // PHPDIG_FORM
        'go' =>'Go ...',
        // SEARCH_FUNCTION
        'noresults' =>'No results'
); // phpdig_mess

// Query variables.
var $option         = "start";
var $refine         = 0;
var $refine_url     = 0;
var $lim_start      = 0;
var $limite         = 30;
var $browse         = 0;
var $site           = 0;
var $path           = '';
var $rssdf          = '';
// FIXME: This should be definable.
var $common_words_file;
var $relative_script_path = '/home/marc/src/acms/lib/phpdig';

// Constructor
function phpDigClass()
{
    global $INCLUDE_DIR;
    global $phpdig_string_subst;
    $this->phpdigEncode = array();
    $this->phpdigCreateSubstArrays($phpdig_string_subst);
    $this->common_words_file = $INCLUDE_DIR . "common_words.txt";
}



function search($id_connect, $query_string)
{
    global $app;
    // check input

    // $id_connect set in connect.php file
    // $query_string cleaned in $query_to_parse in search_function.php file
    if (ACMS_DEBUG) $app->writeLog("phpdigClass - Checking input variables");
    if (($this->option != "start") && ($this->option != "any") && ($this->option != "exact")) { $this->option = SEARCH_DEFAULT_MODE; }
    if (($this->refine != 0) && ($this->refine != 1)) { $this->refine = 0; }
    // refine_url set in search_function.php file
    settype($this->limite,'integer');
    if (($this->limite != 10) && ($this->limite != 30) && ($this->limite != 100)) { $this->limite = SEARCH_DEFAULT_LIMIT; }
    settype($limit_start,'integer');
    if (isset($limit_start)) { $limit_start = $this->limite * floor($limit_start / $this->limite); }
    if (($this->browse != 0) && ($this->browse != 1)) { $this->browse = 0; }
    if (eregi("^[0-9]+[,]",urldecode($this->site))) { $tempbust = explode(",",urldecode($this->site)); $this->site = $tempbust[0]; $this->path = $tempbust[1]; }
    settype($this->site,'integer'); // now set to integer
    if (!get_magic_quotes_gpc()) { $my_path = addslashes($this->path); }
    else { $my_path = addslashes(stripslashes($this->path)); $this->path = stripslashes($this->path); }
    // $relative_script_path set in search.php file

    // init variables
    global $phpdig_words_chars;
    settype($maxweight,'integer');
    $ignore = '';
    $ignore_common = '';
    $wheresite = '';
    $wherepath = '';
    $table_results = '';
    $final_result = '';
    $search_time = 0;
    $strings = array();
    $num_tot = 0;
    $leven_final = "";

    $mtime = explode(' ',microtime());
    $start_time = $mtime[0]+$mtime[1];

    if (!in_array($this->option,array('start','any','exact'))) {
         return 0;
    }

    // the query was filled
    if (ACMS_DEBUG) $app->writeLog("phpdigClass - Checking query string");
    if ($query_string) {
        $common_words = $this->phpdigComWords($this->common_words_file);

        $like_start = array( "start" => "", // is empty
                             "any" => "", // was a percent
                             "exact" => "" // is empty
                           );
        $like_end = array( "start" => "%", // is a percent
                           "any" => "%", // is a percent
                           "exact" => "%" // was empty
                         );
        $like_operator = array( "start" => "like", // is a like
                                "any" => "like", // is a like
                                "exact" => "like" // was an =
                              );

        if ($this->refine) {
            $query_string = urldecode($query_string);
            $wheresite = "AND spider.site_id = " . $this->site ;
            if (($this->path) && (strlen($this->path) > 0)) {
                $wherepath = "AND spider.path like '$my_path' ";
            }
            $this->refine_url = "&refine=1&site=" . $this->site . "&path=" . $this->path;
        } else {
            $this->refine_url = "";
        }

        // query string was passed by url
        if ($this->browse) {
            $query_string = urldecode($query_string);
        }

        if ($this->limite) {
            settype ($this->limite,"integer");
        } else {
            $this->limite = SEARCH_DEFAULT_LIMIT;
        }

        settype ($this->lim_start,"integer");
        if ($this->lim_start < 0) {
            $this->lim_start = 0;
        }

        $n_words = count(explode(" ",$query_string));

        $ncrit = 0;
        $tin = "0";

        if (!get_magic_quotes_gpc()) {
            $query_to_parse = addslashes($query_string);
        } else {
            $query_to_parse = $query_string;
        }
        $my_query_string_link = stripslashes($query_to_parse);

        $query_to_parse = str_replace('_','\_',$query_to_parse); // avoid '_' in the query
        //echo "1 - $query_to_parse<br>";
        $query_to_parse = str_replace('%','\%',$query_to_parse); // avoid '%' in the query
        //echo "2 - $query_to_parse<br>";
        //$query_to_parse = str_replace('\'','_',$query_to_parse); // avoid ''' in the query
        $query_to_parse = str_replace('\"',' ',$query_to_parse); // avoid '"' in the query
        //echo "3 - $query_to_parse<br>";

        //$query_to_parse = $this->phpdigStripAccents(strtolower(ereg_replace("[\"']+"," ",$query_to_parse))); //made all lowercase
        $query_to_parse = $this->phpdigStripAccents(strtolower($query_to_parse)); //made all lowercase
        //echo "4 - $query_to_parse<br>";

        //$query_to_parse = ereg_replace("([^ ])-([^ ])","\\1 \\2",$query_to_parse); // avoid '-' in the query
        //$query_to_parse = str_replace('_','\_',$query_to_parse); // avoid '_' in the query

        $what_query_chars = "[^".$phpdig_words_chars[PHPDIG_ENCODING]." \'.\_~@#$:&\%/;,=-]+"; // epure chars \'._~@#$:&%/;,=-

        /*
        if (eregi($what_query_chars,$query_to_parse)) {
            $query_to_parse = eregi_replace($what_query_chars," ",$query_to_parse);
        }
        //echo "5 - $query_to_parse<br>";
        */

        /*
        $query_to_parse = ereg_replace('(['.$phpdig_words_chars[PHPDIG_ENCODING].'])[\'.\_~@#$:&\%/;,=-]+($|[[:space:]]$|[[:space:]]['.$phpdig_words_chars[PHPDIG_ENCODING].'])','\1\2',$query_to_parse);
        //echo "6 - $query_to_parse<br>";
        */

        $query_to_parse = trim(ereg_replace(" +"," ",$query_to_parse)); // no more than 1 blank
        //echo "7 - $query_to_parse<br>";

        $query_for_strings = $query_to_parse;
        $query_for_phrase = $query_to_parse;
        $test_short = $query_to_parse;
        $query_to_parse2 = explode(" ",$query_to_parse);
        usort($query_to_parse2, "by_length");
        $query_to_parse = implode(" ",$query_to_parse2);
        unset($query_to_parse2);

        if (ACMS_DEBUG) $app->writeLog("phpdigClass - Checking small_words_size");
        if (SMALL_WORDS_SIZE >= 1) {
            $ignore_short_flag = 0;
            $test_short_counter = 0;
            $test_short2 = explode(" ",$test_short);
            for ($i=0; $i<count($test_short2); $i++) {
                $test_short2[$i] = trim($test_short2[$i]);
            }
            $test_short2 = array_unique($test_short2);
            sort($test_short2);
            $test_short3 = array();
            for ($i=0; $i<count($test_short2); $i++) {
                if ((strlen($test_short2[$i]) <= SMALL_WORDS_SIZE) && (strlen($test_short2[$i]) > 0)) {
                    $test_short2[$i].=" ";
                    $test_short_counter++;
                    $test_short3[] = $test_short2[$i];
                }
            }
            $test_short = implode(" ",$test_short3);
            unset($test_short2);
            unset($test_short3);

            //while (ereg(' ([^ ]{1,'.SMALL_WORDS_SIZE.'}) | ([^ ]{1,'.SMALL_WORDS_SIZE.'})$|^([^ ]{1,'.SMALL_WORDS_SIZE.'}) ',$test_short,$regs)) {
            while (ereg('( [^ ]{1,'.SMALL_WORDS_SIZE.'} )|( [^ ]{1,'.SMALL_WORDS_SIZE.'})$|^([^ ]{1,'.SMALL_WORDS_SIZE.'} )',$test_short,$regs)) {
                for ($n=1; $n<=3; $n++) {
                    if (($regs[$n]) || ($regs[$n] == 0)) {
                        $ignore_short_flag++;
                        if (!eregi("\"".trim(stripslashes($regs[$n]))."\", ",$ignore)) {
                            $ignore .= "\"".trim(stripslashes($regs[$n]))."\", ";
                        }
                        $test_short = trim(str_replace($regs[$n],"",$test_short));
                    }
                }
            }
            if (strlen($test_short) <= SMALL_WORDS_SIZE) {
                if (!eregi("\"".$test_short."\", ",$ignore)) {
                    $ignore_short_flag++;
                    $ignore .= "\"".stripslashes($test_short)."\", ";
                }
                $test_short = trim(str_replace($test_short,"",$test_short));
            }
        }

        $ignore = str_replace("\"\", ","",$ignore);

        if ($this->option != "exact") {
            if (($ignore) && ($ignore_short_flag > 1) && ($test_short_counter > 1)) {
                $ignore_message = $ignore.' '.$this->phpdigMsg('w_short_plur');
            } elseif ($ignore) {
                $ignore_message = $ignore.' '.$this->phpdigMsg('w_short_sing');
            }
        }

        $ignore_common_flag = 0;
        while (ereg("(-)?([^ ]{".(SMALL_WORDS_SIZE+1).",}).*",$query_for_strings,$regs)) {

            $query_for_strings = trim(str_replace($regs[2],"",$query_for_strings));
            if (!isset($common_words[stripslashes($regs[2])])) {
                $spider_in = "";
                if ($regs[1] == '-') {
                    $exclude[$ncrit] = $regs[2];
                    $query_for_phrase = trim(str_replace("-".$regs[2],"",$query_for_phrase));
                } else {
                    $strings[$ncrit] = $regs[2];
                }

                $kconds[$ncrit] = '';

                if ($this->option != 'any') {
                    $kconds[$ncrit] .= " AND k.twoletters = '".addslashes(substr(str_replace('\\','',$regs[2]),0,2))."' ";
                }
                $kconds[$ncrit] .= " AND k.keyword ".$like_operator[$this->option]." '".$like_start[$this->option].$regs[2].$like_end[$this->option]."' ";

                $ncrit++;
            } else {
                $ignore_common_flag++;
                $ignore_common .= "\"".stripslashes($regs[2])."\", ";
            }
        }

        if ($this->option != "exact") {
            if (($ignore_common) && ($ignore_common_flag > 1)) {
                $ignore_commess = $ignore_common.' '.$this->phpdigMsg('w_common_plur');
            } elseif ($ignore_common) {
                $ignore_commess = $ignore_common.' '.$this->phpdigMsg('w_common_sing');
            }
        }


        if (ACMS_DEBUG) $app->writeLog("phpdigClass - ncrit = '$ncrit'");
        //echo "ncrit = '$ncrit', strings = <br><pre>"; print_r($strings); echo "</pre>";

        if ($ncrit && is_array($strings)) {
            $query = "SET OPTION SQL_BIG_SELECTS = 1";
            if (ACMS_DEBUG) $app->writeLog("phpdigClass - query = '$query'");
            mysql_query($query,$id_connect);

            $my_spider2site_array = array();
            $my_sitecount_array = array();

            for ($n = 0; $n < $ncrit; $n++) {

                $query  = "SELECT spider.spider_id,sum(weight) as weight, spider.site_id";
                $query .= " FROM ".PHPDIG_DB_PREFIX."keywords as k,".PHPDIG_DB_PREFIX."engine as engine, ".PHPDIG_DB_PREFIX."spider as spider";
                $query .= " WHERE engine.key_id = k.key_id" .$kconds[$n];
                $query .= " AND engine.spider_id = spider.spider_id $wheresite $wherepath";
                $query .= " GROUP BY spider.spider_id,spider.site_id ";

                if (ACMS_DEBUG) $app->writeLog("phpdigClass - query = '$query'");
                $result = mysql_query($query,$id_connect);
                $num_res_temp = mysql_num_rows($result);

                if ($num_res_temp > 0) {
                    if (!isset($exclude[$n])) {
                        $num_res[$n] = $num_res_temp;
                        while (list($spider_id,$weight,$site_id) = mysql_fetch_array($result)) {
                            $s_weight[$n][$spider_id] = $weight;
                            $my_spider2site_array[$spider_id] = $site_id;
                            $my_sitecount_array[$site_id] = 0;
                        }
                    } else {
                        $num_exclude[$n] = $num_res_temp;
                        while (list($spider_id,$weight) = mysql_fetch_array($result)) {
                            $s_exclude[$n][$spider_id] = 1;
                        }
                        mysql_free_result($result);
                    }
                } elseif (!isset($exclude[$n])) {
                    $num_res[$n] = 0;
                    $s_weight[$n][0] = 0;
                }
            }


            if ($this->option != "any") {
                if (is_array($num_res)) {
                    asort ($num_res);
                    list($id_most) = each($num_res);
                    reset ($s_weight[$id_most]);
                    while (list($spider_id,$weight) = each($s_weight[$id_most]))  {
                        $weight_tot = 1;
                        reset ($num_res);
                        while(list($n) = each($num_res)) {
                            settype($s_weight[$n][$spider_id],'integer');
                            $weight_tot *= sqrt($s_weight[$n][$spider_id]);
                        }
                        if ($weight_tot > 0) {
                            $final_result[$spider_id]=$weight_tot;
                        }
                    }
                }
            } else {
                if (is_array($num_res)) {
                    asort ($num_res);
                    while (list($spider_id,$site_id) = each($my_spider2site_array))  {
                        $weight_tot = 0;
                        reset ($num_res);
                        while(list($n) = each($num_res)) {
                            settype($s_weight[$n][$spider_id],'integer');
                            $weight_tot += sqrt($s_weight[$n][$spider_id]);
                        }
                        if ($weight_tot > 0) {
                            $final_result[$spider_id]=$weight_tot;
                        }
                    }
                }
            }

            if (isset($num_exclude) && is_array($num_exclude)) {
                while (list($id) = each($num_exclude)) {
                    while(list($spider_id) = each($s_exclude[$id])) {
                        unset($final_result[$spider_id]);
                    }
                }
            }

            if ($this->option == "exact") {
                if ((is_array($final_result)) && (count($final_result) > 0)) {
                    $exact_phrase_flag = 0;
                    arsort($final_result);
                    reset($final_result);
                    $query_for_phrase_array = explode(" ",$query_for_phrase);
                    $reg_strings = str_replace('@#@',' ',$this->phpdigPregQuotes(str_replace('\\','',implode('@#@',$query_for_phrase_array))));
                    $stop_regs = "[][(){}[:blank:]=&?!&#%\$£*@+%:;,/\.'\"]";
                    $reg_strings = "($stop_regs{1}|^)($reg_strings)($stop_regs{1}|\$)";
                    while (list($spider_id,$weight) = each($final_result)) {
                        $content_file = TEXT_CONTENT_PATH.$spider_id.'.txt';
                        if (is_file($content_file)) {
                            $f_handler = fopen($content_file,'r');
                            $extract_content = preg_replace("/([ ]{2}|\n|\r|\r\n)/"," ",fread($f_handler,filesize($content_file)));
                            if(!eregi($reg_strings,$extract_content)) {
                                $exact_phrase_flag = 1;
                            }
                            fclose($f_handler);
                        }
                        if ($exact_phrase_flag == 1) {
                            unset($final_result[$spider_id]);
                            $exact_phrase_flag = 0;
                        }
                    }
                }
            }

            //if(NUMBER_OF_RESULTS_PER_SITE != -1) {
            if((!$this->refine) && (NUMBER_OF_RESULTS_PER_SITE != -1)) {
                if ((is_array($final_result)) && (count($final_result) > 0)) {
                    arsort($final_result);
                    reset($final_result);
                    while (list($spider_id,$weight) = each($final_result)) {
                        $site_id = $my_spider2site_array[$spider_id];
                        $current_site_counter = $my_sitecount_array[$site_id];
                        if ($current_site_counter < NUMBER_OF_RESULTS_PER_SITE) {
                            $my_sitecount_array[$site_id]++;
                        } else {
                            unset($final_result[$spider_id]);
                        }
                    }
                }
            }
        }


        if ((is_array($final_result)) && (count($final_result) > 0)) {
            arsort($final_result);
            $n_start = $this->lim_start+1;
            $num_tot = count($final_result);
            if ($n_start+$this->limite-1 < $num_tot) {
                $n_end = ($this->lim_start+$this->limite);
                $more_results = 1;
            } else {
                $n_end = $num_tot;
                $more_results = 0;
            }

            // ereg for text snippets and highlighting

            if ($this->option == "exact") {
                $reg_strings = str_replace('@#@',' ',$this->phpdigPregQuotes(str_replace('\\','',implode('@#@',$query_for_phrase_array))));
            } else {
                $reg_strings = str_replace('@#@','|',$this->phpdigPregQuotes(str_replace('\\','',implode('@#@',$strings))));
            }
            $stop_regs = "[][(){}[:blank:]=&?!&#%\$£*@+%:;,/\.'\"]";

            switch($this->option) {
                case 'any':
                    $reg_strings = "($stop_regs{1}|^)($reg_strings)()";
                    break;
                case 'exact':
                    $reg_strings = "($stop_regs{1}|^)($reg_strings)($stop_regs{1}|\$)";
                    break;
                default:
                    $reg_strings = "($stop_regs{1}|^)($reg_strings)()";
                    break;
            }

            //fill the results table
            reset($final_result);
            for ($n = 1; $n <= $n_end; $n++) {
                list($spider_id,$s_weight) = each($final_result);
                if (!$maxweight) {
                    $maxweight = $s_weight;
                }
                if ($n >= $n_start) {

                    $query  = "SELECT sites.site_url, sites.port, spider.path,spider.file,spider.first_words,sites.site_id,spider.spider_id,spider.last_modified,spider.md5 ";
                    $query .= "FROM ".PHPDIG_DB_PREFIX."spider AS spider, ".PHPDIG_DB_PREFIX."sites AS sites ";
                    $query .= "WHERE spider.spider_id=$spider_id AND sites.site_id = spider.site_id";
                    if (ACMS_DEBUG) $app->writeLog("phpdigClass - query = '$query'");
                    $result = mysql_query($query,$id_connect);
                    $content = mysql_fetch_array($result,MYSQL_ASSOC);
                    mysql_free_result($result);
                    if ($content['port']) {
                        $content['site_url'] = ereg_replace('/$',':'.$content['port'].'/',$content['site_url']);
                    }
                    $weight = sprintf ("%01.2f", (100*$s_weight)/$maxweight);
                    $url = eregi_replace("([a-z0-9])[/]+","\\1/",$content['site_url'].$content['path'].$content['file']);

                    $l_site = "<a class='phpdig' href='".SEARCH_PAGE."?refine=1&amp;query_string=".urlencode($my_query_string_link)."&amp;site=".$content['site_id']."&amp;limite=$this->limite&amp;option=$this->option'>".$content['site_url']."</a>";
                    if ($content['path']) {
                        $l_path = ", ".$this->phpdigMsg('this_path')." : <a class='phpdig' href='".SEARCH_PAGE."?refine=1&amp;query_string=".urlencode($my_query_string_link)."&amp;site=".$content['site_id']."&amp;path=".$content['path']."&amp;limite=$this->limite&amp;option=$this->option' >".ereg_replace('%20*',' ',$content['path'])."</a>";
                    } else {
                        $l_path="";
                    }

                    $first_words = str_replace('<','&lt;',str_replace('>','&gt;',$content['first_words']));

                    $extract = "";
                    //Try to retrieve matching lines if the content-text is set to 1
                    if (ACMS_DEBUG) $app->writeLog("phpdigClass - entering matching lines loop");
                    if (CONTENT_TEXT == 1 && DISPLAY_SNIPPETS) {
                        $content_file = TEXT_CONTENT_PATH.$content['spider_id'].'.txt';
                        if (ACMS_DEBUG) $app->writeLog("phpdigClass - checking $content_file");
                        if (is_file($content_file)) {
                            if (ACMS_DEBUG) $app->writeLog("phpdigClass - $content_file is a file");
                            $num_extracts = 0;
                            $my_extract_size = 200;
                            $my_filesize_for_while = filesize($content_file);
                            while (($num_extracts == 0) && ($my_extract_size <= $my_filesize_for_while)) { // ***
                                $f_handler = fopen($content_file,'r');
                                //while($num_extracts < DISPLAY_SNIPPETS_NUM && $extract_content = fgets($f_handler,1024)) {
                                while($num_extracts < DISPLAY_SNIPPETS_NUM && $extract_content = preg_replace("/([ ]{2}|\n|\r|\r\n)/"," ",fread($f_handler,$my_extract_size))) {
                                    if(eregi($reg_strings,$extract_content)) {
                                        $extract_content = str_replace('<','&lt;',str_replace('>','&gt;',trim($extract_content)));
                                        $match_this_spot = eregi_replace($reg_strings,"\\1<\\2>\\3",$extract_content);
                                        $first_bold_spot = strpos($match_this_spot,"<");
                                        $first_bold_spot = max($first_bold_spot - round((SNIPPET_DISPLAY_LENGTH / 2),0), 0);
                                        $extract_content = substr($extract_content,$first_bold_spot,max(SNIPPET_DISPLAY_LENGTH, 2 * strlen($query_string)));
                                        $extract .= ' ...'.$this->phpdigHighlight($reg_strings,$extract_content).'... ';
                                        $num_extracts++;
                                    }
                                }
                                fclose($f_handler);

                                if ($my_extract_size < $my_filesize_for_while) {
                                    $my_extract_size *= 100;
                                    if ($my_extract_size > $my_filesize_for_while) {
                                        $my_extract_size = $my_filesize_for_while;
                                    }
                                } else {
                                    $my_extract_size++;
                                }

                            } // ends ***
                        }
                    }

                    list($title,$text) = explode("\n",$first_words);

                    if (ACMS_DEBUG) $app->writeLog("phpdigClass - highlighting '$title'");
                    $title = $this->phpdigHighlight($reg_strings,$title);
                    if (ACMS_DEBUG) $app->writeLog("phpdigClass - done highlighting '$title'");

                    $table_results[$n] = array (
                            'weight' => $weight,
                            'page_link' => "<a href=\"".$url."\">".ereg_replace('%20*',' ',$title)."</a>",
                            'limit_links' => $this->phpdigMsg('limit_to')." ".$l_site.$l_path,
                            'filesize' => sprintf('%.1f',(ereg_replace('.*_([0-9]+)$','\1',$content['md5']))/1024),
                            'update_date' => ereg_replace('^([0-9]{4})([0-9]{2})([0-9]{2}).*',PHPDIG_DATE_FORMAT,$content['last_modified']),
                            'complete_path' => $url,
                            'link_title' => $title
                            );

                    $table_results[$n]['text'] = '';
                    if (ACMS_DEBUG) $app->writeLog("phpdigClass - extract = '$extract'");
                    if (DISPLAY_SUMMARY) {
                        $table_results[$n]['text'] = $this->phpdigHighlight($reg_strings,ereg_replace('(@@@.*)','',wordwrap($text, SUMMARY_DISPLAY_LENGTH, '@@@')));
                    }
                    if (DISPLAY_SUMMARY && DISPLAY_SNIPPETS) {
                        $table_results[$n]['text'] .= "\n<br/><br/>\n";
                    }
                    if (DISPLAY_SNIPPETS) {
                        if ($extract) {
                            $table_results[$n]['text'] .= $extract;
                        } else if (!$table_results[$n]['text']) {
                            //$table_results[$n]['text'] = $this->phpdigHighlight($reg_strings,ereg_replace('(@@@.*)','',wordwrap($text, SUMMARY_DISPLAY_LENGTH, '@@@')));
                            $table_results[$n]['text'] = $this->phpdigHighlight($reg_strings,ereg_replace('(@@@.*)','',$text));
                        }
                    }
                    if (ACMS_DEBUG) $app->writeLog("phpdigClass - done updating text");
                }
            }

            if (ACMS_DEBUG) $app->writeLog("phpdigClass - creating nav/pages/url bars");
            $nav_bar = '';
            $pages_bar = '';
            $url_bar = SEARCH_PAGE."?browse=1&amp;query_string=".urlencode($my_query_string_link)."$this->refine_url&amp;limite=$this->limite&amp;option=$this->option&amp;lim_start=";
            if ($this->lim_start > 0) {
                $previous_link = $url_bar.($this->lim_start-$this->limite);
                $nav_bar .= "<a class=\"phpdig\" href=\"$previous_link\" >&lt;&lt;".$this->phpdigMsg('previous')."</a>&nbsp;&nbsp;&nbsp; \n";
            }
            $tot_pages = ceil($num_tot/$this->limite);
            $actual_page = $this->lim_start/$this->limite + 1;
            $page_inf = max(1,$actual_page - 5);
            $page_sup = min($tot_pages,max($actual_page+5,10));
            for ($page = $page_inf; $page <= $page_sup; $page++) {
                if ($page == $actual_page) {
                    $nav_bar .= " <span class=\"phpdigHighlight\">$page</span> \n";
                    $pages_bar .= " <span class=\"phpdigHighlight\">$page</span> \n";
                    $link_actual =  $url_bar.(($page-1)*$this->limite);
                } else {
                    $nav_bar .= " <a class=\"phpdig\" href=\"".$url_bar.(($page-1)*$this->limite)."\" >$page</a> \n";
                    $pages_bar .= " <a class=\"phpdig\" href=\"".$url_bar.(($page-1)*$this->limite)."\" >$page</a> \n";
                }
            }

            if ($more_results == 1) {
                $next_link = $url_bar.($this->lim_start+$this->limite);
                $nav_bar .= " &nbsp;&nbsp;&nbsp;<a class=\"phpdig\" href=\"$next_link\" >".$this->phpdigMsg('next')."&gt;&gt;</a>\n";
            }

            $mtime = explode(' ',microtime());
            $search_time = sprintf('%01.2f',$mtime[0]+$mtime[1]-$start_time);
            $result_message = stripslashes(ucfirst($this->phpdigMsg('results'))." $n_start-$n_end, $num_tot ".$this->phpdigMsg('total').", ".$this->phpdigMsg('on')." \"".htmlspecialchars($query_string)."\" ($search_time ".$this->phpdigMsg('seconds').")");

        } else {
            $num_in_strings_arr = count($strings);
            if (($num_in_strings_arr > 0) && (strlen($this->path) == 0)) {
                $leven_final = "";
                for ($i=0; $i<$num_in_strings_arr; $i++) {
                    $soundex_query = "SELECT keyword FROM ".PHPDIG_DB_PREFIX."keywords WHERE SOUNDEX(CONCAT('Q',keyword)) = SOUNDEX(CONCAT('Q','".$strings[$i]."')) LIMIT 500";
                    if (ACMS_DEBUG) $app->writeLog("phpdigClass - soundex_query = '$query'");
                    $soundex_results = mysql_query($soundex_query,$id_connect);
                    if (mysql_num_rows($soundex_results) > 0) {
                        $leven_ind = 0;
                        $leven_amt1 = 256;
                        $leven_keyword = array();
                        while (list($soundex_keyword) = mysql_fetch_array($soundex_results)) {
                            $leven_amt2 = min(levenshtein($strings[$i],$soundex_keyword),$leven_amt1);
                            if (($leven_amt2 < $leven_amt1) && ($leven_amt2 > 0) && ($leven_amt2 <= 5)) {
                                $leven_keyword[$leven_ind] = stripslashes($soundex_keyword);
                                $leven_ind++;
                            }
                            $leven_amt1 = $leven_amt2;
                        }
                        $leven_count = count($leven_keyword);
                        if ($leven_count) {
                            $leven_final .= $leven_keyword[$leven_count-1] . " ";
                        }
                        unset($leven_keyword);
                    }
                }
            }

            $num_tot = 0;
            $result_message = $this->phpdigMsg('noresults');

            if (strlen(trim($leven_final)) > 0) {
                $leven_query = trim($leven_final);
                $result_message .= ". " . $this->phpdigMsg('alt_try') ." <a class=\"phpdigMessage\" href=\"".SEARCH_PAGE."?query_string=".urlencode($leven_query)."\"><i>".htmlspecialchars($leven_query)."</i></a>?";
            }
        }

        if (isset($tempresult)) {
            mysql_free_result($tempresult);
        }

        $title_message = $this->phpdigMsg('s_results');

        // MARC
        if (!isset($exclude)) {
            $exclude = array();
        }
        if (is_array($final_result)) {
            $this->phpdigAddLog ($id_connect,$this->option,$strings,$exclude,count($final_result),$search_time);
        } else {
            $this->phpdigAddLog ($id_connect,$this->option,$strings,$exclude,0,$search_time);
        }

    } else {
        $title_message = 'PhpDig '.PHPDIG_VERSION;
        $result_message = $this->phpdigMsg('no_query').'.';
    }

    $phpdig_version = PHPDIG_VERSION;
    $t_mstrings = compact('rss_feed_link','title_message','phpdig_version','result_message','nav_bar','ignore_message','ignore_commess','pages_bar','previous_link','next_link','templates_links','leven_query');
    $t_fstrings = ""; //phpdigMakeForm($query_string,$this->option,$this->limite,SEARCH_PAGE,$this->site,$this->path,$num_tot);
    return array_merge($t_mstrings,$t_fstrings,array('results'=>$table_results));

}

function by_length($a, $b) {
$len_a = strlen($a);
$len_b = strlen($b);
if ($len_a == $len_b) { return 0; }
return ($len_a < $len_b) ? 1 : -1;
}

//=================================================
// Create Useful arrays for different encodings
function phpdigCreateSubstArrays($subststrings) 
{
    $this->phpdigEncode = array();

    foreach($subststrings as $encoding => $subststring) {
        $tempArray = explode(',',$subststring);
        if (!isset($this->phpdigEncode[$encoding])) {
            $this->phpdigEncode[$encoding] = array();
        }
        $this->phpdigEncode[$encoding]['str'] = '';
        $this->phpdigEncode[$encoding]['tr'] = '';
        $this->phpdigEncode[$encoding]['char'] = array();
        $this->phpdigEncode[$encoding]['ereg'] = array();
        foreach ($tempArray as $tempSubstitution) {
            $chrs = explode(':',$tempSubstitution);
            $this->phpdigEncode[$encoding]['char'][strtolower($chrs[0])] = strtolower($chrs[0]);
            settype($this->phpdigEncode[$encoding]['ereg'][strtolower($chrs[0])],'string');
            $this->phpdigEncode[$encoding]['ereg'][strtolower($chrs[0])] .= $chrs[0].$chrs[1];
            for($i=0; $i < strlen($chrs[1]); $i++) {
                $this->phpdigEncode[$encoding]['str'] .= $chrs[1][$i];
                $this->phpdigEncode[$encoding]['tr']  .= $chrs[0];
            }
        }
        foreach($this->phpdigEncode[$encoding]['ereg'] as $id => $ereg) {
            $this->phpdigEncode[$encoding]['ereg'][$id] = '['.$ereg.']';
        }
    }
}

//=================================================
//load the common words in an array
function phpdigComWords($file='')
{
    $lines = @file($file);
    if (is_array($lines)) {
        while (list($id,$word) = each($lines)) {
            $common[trim($word)] = 1;
        }
    } else {
        $common['aaaa'] = 1;
    }
    return $common;
}


//=================================================
//replace all characters with an accent
function phpdigStripAccents($chaine,$encoding=PHPDIG_ENCODING)
{
    if (!isset($this->phpdigEncode[$encoding])) {
       $encoding = PHPDIG_ENCODING;
    }
    // exceptions
    if ($encoding == 'iso-8859-1') {
        $chaine = str_replace('Æ','ae',str_replace('æ','ae',$chaine));
    }
    return( strtr( $chaine,$this->phpdigEncode[$encoding]['str'],$this->phpdigEncode[$encoding]['tr']) );
}
 
//==========================================
//Create a ereg for highlighting
function phpdigPregQuotes($chaine,$encoding=PHPDIG_ENCODING) 
{
    if (!isset($this->phpdigEncode[$encoding])) {
        $encoding = PHPDIG_ENCODING;
    }
    $chaine = preg_quote(strtolower($this->phpdigStripAccents($chaine,$encoding)));
    return str_replace($this->phpdigEncode[$encoding]['char'],$this->phpdigEncode[$encoding]['ereg'],$chaine);
}

//=================================================
//highlight a string part
function phpdigHighlight($ereg='',$string='')
{
    if ($ereg) {
        //return @eregi_replace($ereg,"\\1<span class=\"phpdigHighlight\">\\2</span>\\3",$string);
        $string = @eregi_replace($ereg,"\\1<^#_>\\2</_#^>\\3",@eregi_replace($ereg,"\\1<^#_>\\2</_#^>\\3",$string));
        $string = str_replace("^#_","span class=\"phpdigHighlight\"",str_replace("_#^","span",$string));
        return $string;
    } else {
        return $result;
    }
}

//=================================================
//returns a localized string
function phpdigMsg($string='')
{
    if (isset($this->phpdig_mess[$string])) {
        return nl2br($this->phpdig_mess[$string]);
    }
    else {
        return ucfirst($string);
    }
}

//=================================================
//insert an entry in logs
function phpdigAddLog ($id_connect,$option='start',$includes=array(),$excludes=array(),$num_results=0,$time=0) {
    if (!is_array($excludes)) {
        $excludes = array();
    }
    sort($excludes);
    if (!is_array($includes)) {
        $includes = array();
    }
    sort($includes);
    $query  = 'INSERT INTO '.PHPDIG_DB_PREFIX.'logs (l_num,l_mode,l_ts,l_includes,l_excludes,l_time) ';
    $query .= 'VALUES ('.$num_results.',\''.substr($option,0,1).'\',NOW(),';
    $query .= '\''.implode(' ',$includes).'\',\''.implode(' ',$excludes).'\','.(double)$time.')';
    mysql_query($query,$id_connect);
    return mysql_insert_id($id_connect);
}

}; // phpDigSearch

?>
