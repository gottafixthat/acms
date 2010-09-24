<?

global $phpdig_string_subst, $phpdig_words_chars;

$phpdig_string_subst['iso-8859-1'] = 'A:ÀÁÂÃÄÅ,a:אבגדהו,O:ÒÓÔÕÖØ,o:עףפץצר,E:ÈÉÊË,e:טיךכ,C:Ç,c:ח,I:ÌÍÎÏ,i:לםמן,U:ÙÚÛÜ,u:שתûü,Y:Ý,y:ÿ‎,N:Ñ,n:ס';
$phpdig_string_subst['iso-8859-2'] = 'A:ÁÂÄÃ¡,C:ÇÆÈ,D:ÏÐ,E:ÉËÊÌ,I:ÍÎ,L:Å¥£,N:ÑÒ,O:ÓÔÖÕ,R:ÀØ,S:¦×©,T:Þ«,U:ÚÜÙÛ,Y:Ý,Z:¬¯®,a:בגהד±,c:חזט,d:ןנ,e:יכךל,i:םמ,l:וµ³,n:סע,o:ףפצץ,r:אר,s:¶÷¹,t:‏»,u:תüשû,y:‎,z:¼¿¾';
$phpdig_string_subst['iso-8859-7'] = 'י:‗ת,ב:Ü,ו:Ý,ח:Þ,ן:ü,ץ:‎û,ש:‏';
$phpdig_string_subst['tis-620'] = 'Q:Q,q:q';
$phpdig_string_subst['windows-1251'] = 'À:א,Á:ב,Â:ג,Ã:ד,Ä:ה,Å:ו,Æ:ז,Ç:ח,È:ט,É:י,Ê:ך,Ë:כ,Ì:ל,Í:ם,Î:מ,Ï:ן,Ð:נ,Ñ:ס,Ò:ע,Ó:ף,Ô:פ,Õ:ץ,Ö:צ,×:ק,Ø:ר,Ù:ש,Ú:ת,Û:û,Ü:ü,Ý:‎,Þ:‏,‗:ÿ';

$phpdig_words_chars['iso-8859-1'] = '[:alnum:]נ‏‗µ';
$phpdig_words_chars['iso-8859-2'] = '[:alnum:]נ‏‗µ';
$phpdig_words_chars['iso-8859-7'] = '[:alnum:]ÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÓÔÕÖ×ØÙ¢¸¹÷¼¾¿ÚÛבגדהוזחטיךכלםמןנסףפץצקרשÜÝÞ‗ü‎‏תûÀא';
$phpdig_words_chars['tis-620'] = '[:alnum:]¡¢£¤¥¦§¨©×«¬_®¯°±²³´µ¶·¸¹÷»¼½¾¿ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÜ‗אבגדהוזחטיךכלםמןנסעףפץצקרשתû';
$phpdig_words_chars['windows-1251'] = '[:alnum:]ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞ‗אבגדהוזחטיךכלםמןנסעףפץצקרשתûü‎‏ÿ';

?>