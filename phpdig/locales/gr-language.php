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

//------------------------------------------
//Greek language file from Sofoklis Magoulas
//------------------------------------------
//'keyword' => 'translation'
$phpdig_mess = array (
'upd_sites'    =>'Update sites',
'upd2'         =>'Update Done',
'links_per'    =>'Links per',
'yes'          =>'���',
'no'           =>'���',
'delete'       =>'��������',
'reindex'      =>'�����������������',
'back'         =>'���������',
'files'        =>'������',
'admin'        =>'��������������',
'warning'      =>'������� !',
'index_uri'    =>'���� URL ��� �� ���������������� ?',
'spider_depth' =>'����� ����������',
'spider_warn'  =>"���������� ��� ������ ����� ��� ����� ��������� ��� �������, ���������� �� ���� �� ����������� �������������� ����������",
'site_update'  =>"���������� ��� ������� � ���� ��� ��� ����� ���",
'clean'        =>'���������',
't_index'      =>"���������������",
't_dic'        =>'������',
't_stopw'      =>'������ ������',
't_dash'       =>'dashes',

'update'       =>'�������������',
'tree_found'   =>'������ ���������',
'update_mess'  =>'����������������� � �������� ��� ��������� ',
'update_warn'  =>"���������� ���� ��������",
'update_help'  =>'�������� ��� ������ ��� �� ��������� ��� �����
����� ��� ������� ������ ��� �� �� ���������� ',
'branch_start' =>'�������� �� ������� �� ��������� ���� �������� ������ ',
'branch_help1' =>'�������� ���� �� ������� ��� ������������� ������� ',
'branch_help2' =>'�������� ��� ������ ��� �� ��������� ��� �������
����� ��� ������� ������ ��� �� �� ������������
�� ����� ������� ',
'redepth'      =>'����� �������� ',
'branch_warn'  =>"� �������� ����� ������",
'to_admin'     =>"���� ������ �����������",

'search'       =>'���������',
'results'      =>'������������',
'display'      =>'��������',
'w_begin'      =>'������ ���������� ��',
'w_whole'      =>'�������� ����',
'w_part'       =>'���� ������� �����',
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

'id_start'     =>'��������������� Site',
'id_end'       =>'��������������� ������������ !',
'id_recent'    =>'���������� ��������',
'num_words'    =>'������� ������',
'time'         =>'������',
'error'        =>'������',
'no_spider'    =>'Spider ��� �����������',
'no_site'      =>'��� ������� ���� �� site ���� ���� ���������',
'no_temp'      =>'��� �������� ��������� ��� ��������� ������',
'no_toindex'   =>'������ ��� ���������������',
'double'       =>'���� �� ������� �������',

'spidering'    =>'Spidering �� �������...',
'links_more'   =>'����� ���� ���������',
'level'        =>'�������',
'links_found'  =>'��������� ��������',
'define_ex'    =>'��������� ���� ������������',
'index_all'    =>'��������������� ����',

'end'          =>'�����',
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
'cleaningdictionnary'   =>'Cleaning dictionnary',
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
);
?>