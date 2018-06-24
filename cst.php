<?php

#CST:xscesn00

/* global variables */
$first_file = __DIR__;
$output_file = STDOUT;
$file = NULL;
$nosubdir = 0;
$array_files = array();
$param = "";
$output = 0;
$file_out = NULL;
$ptrue = 0;
$boolak = false;

/* vypisanie chybovej hlasky na STDERR s navratovou hodnotou */
function exit_status($code){
	switch($code) {
		case 1 :
			fwrite(STDERR, "Zle zadane parametre! --help pre napovedu\n");
			exit(1);
			break;
		case 2 :
			fwrite(STDERR, "Chybný vstupny subor\n");
			exit(2);
			break;
		case 3 :
			fwrite(STDERR, "Chybny format vystupneho suboru\n");
			exit(3);
			break;
		case 21 :
			fwrite(STDERR, "Chybny vstupny subor, zo suboru sa neda citat\n");
			exit(21);
			break;
	}
}

/* spracovanie súborov */
function check_files($input_file)
{
	global $file,$nosubdir, $array_files, $boolak;
	
	$wdir = pathinfo($input_file);

	// spracovanie suboru z parametru bez kontroly pripony (.c|.h)
	if(is_file($input_file)){
		$array3 = array( $wdir['basename'] => $wdir['dirname']."/".$wdir['basename']);
		$array_files = array_merge($array_files, $array3);
	}
	elseif(is_dir($input_file)){
		$boolak = true;
		foreach (scandir($input_file) as $d) { 							// prehladavanie priecinku
			if($d != '.' && $d != '..'){
				if(preg_match('/^.*\.(c|h)$/',$d)){
					$aaa = $input_file.'/'.$d;
					$wd = pathinfo($aaa);
					$array2 = array( $wd['basename'] => $wd['dirname']."/".$wd['basename']);					
					$array_files = array_merge($array_files, $array2);
					}
            	if(is_dir($input_file.'/'.$d)){
            		if ($nosubdir != 1) {
            			check_files($input_file.'/'.$d);
            		}
            	}
        	}
		}
	}
	else exit_status(2);
	return $array_files;
}

/* ziskanie statistik komentarov */
function comments($content){
	$state = 0;
	$komlenght = 0;
	while (($char = fgetc($content)) !== FALSE) {
   		if ($state == 0) {
   			if ($char == "/") {
   				$state = 1;
   			}
   			else {
   				$state = 0;
   			}
   		}
   		elseif ($state == 1) {
   			if($char == "/") {
   				$state = 2;
   				$komlenght += 2;
   			}
   			elseif($char == "\\"){
   				$state = 2;
   				$komlenght += 2;
   			}
   			elseif ($char == "*") {
   				$state = 3;
   				$komlenght += 2;
   			}
   			else{
   				$state = 0;
   			}
   		}
   		elseif ($state == 2) {
   			if ($char == "\n") {
   				$state = 0;
   				$komlenght++;
   			}
   			else{
   				$state = 2;
   				$komlenght++;
   			}
   		}
   		elseif ($state == 3) {
   			if ($char == "*") {
   				$state = 4;
   				$komlenght++;
   			}
   			else{
   				$state = 3;
   				$komlenght++;
   			}
   		}
   		elseif ($state == 4) {
   			if ($char == "/") {
   				$state = 0;
   				$komlenght++;
   			}
   			elseif ($char == "*") {
   				$state = 4;
   				$komlenght++;
   			}
   			else{
   				$state = 3;
   				$komlenght++;
   			}
   		}
   	}
   	return $komlenght;
}

/* mazanie komentarov */
function without_comments($content){
	
	$contentwcom = "";
	$state = 0;
	while (($char = fgetc($content)) !== FALSE) {
   		if ($state == 0) {
   			if ($char == "/") {
   				$state = 1;
   			}
   			else {
   				$state = 0;
   				$contentwcom .= $char;
   			}
   		}
   		elseif ($state == 1) {
   			if($char == "/") {
   				$state = 2;
   			}
   			elseif ($char == "*") {
   				$state = 3;
   			}
   			else{
   				$state = 0;
   				$contentwcom .= $char;
   			}
   		}
   		elseif ($state == 2) {
   			if ($char == "\n") {
   				$state = 0;
   			}
   			else{
   				$state = 2;
   			}
   		}
   		elseif ($state == 3) {
   			if ($char == "*") {
   				$state = 4;
   			}
   			else{
   				$state = 3;
   			}
   		}
   		elseif ($state == 4) {
   			if ($char == "/") {
   				$state = 0;
   			}
   			elseif ($char == "*") {
   				$state = 4;
   			}
   			else{
   				$state = 3;
   			}
   		}
   	}

   	return $contentwcom;
}

/* mazanie makier */
function without_macro($content){

	$clrcontent = "";
	$do = strlen($content); 	
	$i = 0;
	$state = 0;
	while ($i != $do) {
		if($state == 0){
			if ($content[$i] == "#") {
				$state = 1;
			}
			else{
				$clrcontent .= $content[$i];
			}
		}
		elseif ($state == 1) {
			if ($content[$i] == "\n") {
				$state = 0;
			}
			else{
				$state = 1;
			}
		}
		$i++;	
	}
	return $clrcontent;
}

/* mazanie retazcov*/
function without_strings($content){

	$clrscontent = "";

	$do = strlen($content);

	$i = 0;
	$state = 0;
	while ($i != $do) {
		if($state == 0){
			if ($content[$i] == "\""){
				$state = 1;	
			} 
			elseif ($content[$i] == "'") {
				$state = 2;
			}
			else{
				$clrscontent .= $content[$i];
			}
		}
		elseif ($state == 1) {
			if($content[$i] == "\"")
			{	
				$state = 0;
			}
			elseif ($content[$i] == "\\") {
				$state = 3;
			} 
			else{
				$state = 1;
			}
		}
		elseif ($state == 2) {
			if($content[$i] == "'")
			{	
				$state = 0;
			}
			elseif ($content[$i] == "\\") {
				$state = 3;
			} 
			else{
				$state = 2;
			}
		}
		elseif ($state == 3) {
			$state = 2;
		}

		$i++;	
	}
	return $clrscontent;
}

/* mazanie klucovych slov */
function del_key($content){
	
	$dkcontent = "";
	$do = strlen($content); 	
	$i = 0;
	$state = 0;
	while ($i != $do) {
		$dkcontent .= $content[$i];
		$i++;	
	}
	
	$newcont = preg_replace('/(?<![a-zA-Z0-9])(auto|break|case|char|const|continue|default|do|double|else|enum|extern|float|for|goto|if|inline|int|long|register|restrict|return|short|signed|sizeof|static|struct|switch|typedef|union|unsigned|void|volatile|while)(?![-_a-zA-Z0-9])/', "", $dkcontent);
	return $newcont;
}

/* mazanie ukazovatelov pre lepsie pocitanie operatorov */
function del_pointers($content){

	$dpcontent = "";
	$do = strlen($content); 	
	$i = 0;
	$state = 0;
	while ($i != $do) {
		$dpcontent .= $content[$i];
		$i++;	
	}
	
	$newcont = preg_replace('/(?<![a-zA-Z0-9])(char|const|double|float|int|long|short|sizeof|static|struct|typedef|union|unsigned|void|volatile)(?![-_a-zA-Z0-9])\s[\*]\*/', "", $dpcontent);
	return $newcont;

}

/* statistika o vyskyste hladaneho vyrazu */
function search_pattern($content, $pattern){		
	$pattern_count = 0;
	$pattern_count = preg_match_all("/($pattern)/", $content);
	return $pattern_count;
}
/* statistika o vyskyste klucovych slov */
function keywords($content){
	$keywords = 0;
	$keywords = preg_match_all('/(?<![a-zA-Z0-9])(auto|break|case|char|const|continue|default|do|double|else|enum|extern|float|for|goto|if|inline|int|long|register|restrict|return|short|signed|sizeof|static|struct|switch|typedef|union|unsigned|void|volatile|while)(?![-_a-zA-Z0-9])/',$content, $matches);
	return $keywords;	
}
/* statistika o pocte indetifikatorov */
function identificators($content){
	$ident = 0;
	$ident = preg_match_all('/[_a-zA-Z]+?\w*/',$content);
	return $ident;
}
/* statistika o vyskyste operatorov */
function operators($content){	
	$operators = 0;
	$operators = preg_match_all('/(\+\+)|(--)|(\+=)|(-=)|(\*=)|(\/=)|(\/)|(%=)|(%)|(<=)|(>=)|(==)|(!=)|(!)|(&&)|(\|\|)|(<<=)|(>>=)|(<<)|(>>)|(~)|(&=)|(&)|(\|=)|(\|)|(\^=)|(\^)|(=)|(->)|(\+)|(<)|([^\.0-9]\.[^\.0-9])|(-)|(>)|(\*)/',$content,$matches);
	return $operators;
}


/* spracovanie argumentov */
function parse_arguments($argc, $argv){

	global $first_file, $output_file, $array_files, $nosubdir,$param, $output, $ptrue;

	$help = 0;
	$input = 0;
	$ptrtrue = 0;
	$keytrue = 0;
	$comtrue = 0;
	$optrue = 0;
	$idtrue = 0;
	$ptrue = 0;

	$i = 1;
	if($argc == 2 && $argv[1] == "--help"){
		help();
	}
	else{
		for($i; $i < $argc; $i++){
			if($argv[$i] == "-k" || $argv[$i] == "-o" || $argv[$i] == "-i" || $argv[$i] == "-c"){
				if($param == ""){
					$param = $argv[$i];
				}
				else exit_status(1);
			}
			elseif(strncmp($argv[$i],"-w=",3)==0){
				if($param == ""){
					$param = "-w";
					$len = strlen($argv[$i]);
					if ($len > 3)	
					{
						$pattern = substr($argv[$i],3);
					}
					else exit_status(1);
				}
				else exit_status(1);
			}
			elseif ($argv[$i] === "-p") {
				if($ptrue == 0) $ptrue = 1;
				else exit_status(1);
			}
			elseif ((strncmp($argv[$i],"--input=",8)==0) && $input == 0){
			 	$input = 1;
			 	$first_file = substr($argv[$i], 8);
			}
			elseif ((strncmp($argv[$i],"--output=",9)==0) && $output == 0){
			 	$output = 1;
				$output_file= substr($argv[$i], 9);
			}
			elseif ($argv[$i] == "--nosubdir" && $nosubdir == 0) {
				$nosubdir = 1;
			}
			else exit_status(1);
		}
	}
	$array_files = check_files($first_file);
	ksort($array_files); // zoradenie podla abecedy
	switch($param) {
		case "-k" :  	 // pocitanie klucovyc slov
			$result_array = get_keywords($array_files);
			break;
		case "-o" :  	 // pocitanie operatorov
			$result_array = get_operators($array_files);
			break;
		case "-i" :  	 // pocitanie identifikatorov
			$result_array = get_identificators($array_files);
			break;
		case "-w" :		 // pocitanie vyskystu hladaneho vyrazu 
			$result_array = get_patterns($array_files, $pattern);			
			break;
		case "-c" :      // pocitanie statistiky komentarov
			$result_array = get_comments($array_files);
			break;
	}
	
	result($result_array);
}

/* funkcia na osetrenie vstupu a odstranenie nepotrebnych casti kodu a ziskanie poctu operatorov */
function get_operators($array){
	global $ptrue, $boolak;
	$overall_com = 0;
	$output_array = array();

	foreach ($array as $key => $value) {
		$input_file = $value;
		if(($file = @fopen($input_file,"r")) == FALSE){
			if($boolak) exit_status(21);
			else exit_status(2);
		}
		else{		
			$without_comments = without_comments($file);
			$without_macros = without_macro($without_comments);
			$without_strings = without_strings($without_macros);
			$without_pointrs = del_pointers($without_strings);
			$num_of_codes = operators($without_pointrs);
			$overall_com += $num_of_codes;
			if($ptrue == 1)	$pom_array = array($key => $num_of_codes); // ak je zadany parameter -p chceme len nazov suboru
			else $pom_array = array(realpath($input_file) => $num_of_codes);
			$output_array = array_merge($output_array, $pom_array);
		}		
	}
	$pom_array = array("CELKEM:" => $overall_com); //celkove scitanie statistik
	$output_array = array_merge($output_array, $pom_array);

	return $output_array;	

}

/* funkcia na osetrenie vstupu a odstranenie nepotrebnych casti kodu a ziskanie poctu identifikatorov */
function get_identificators($array){
	global $ptrue, $boolak;
	$overall_com = 0;
	$output_array = array();

	foreach ($array as $key => $value) {
		$input_file = $value;
		if(($file = @fopen($input_file,"r")) == FALSE)
			if($boolak) exit_status(21);
			else exit_status(2);
		else{			
			$without_comments = without_comments($file);
			$without_macros = without_macro($without_comments);
			$without_strings = without_strings($without_macros);
			$without_keywords = del_key($without_strings);
			$num_of_codes = identificators($without_keywords);
			$overall_com += $num_of_codes;
			if($ptrue == 1)	$pom_array = array($key => $num_of_codes); // ak je zadany parameter -p chceme len nazov suboru
			else $pom_array = array(realpath($input_file)=> $num_of_codes);
			$output_array = array_merge($output_array, $pom_array);
		}		
	}
	$pom_array = array("CELKEM:" => $overall_com);  //celkove scitanie statistik
	$output_array = array_merge($output_array, $pom_array);

	return $output_array;	
}

/* funkcia na osetrenie vstupu a odstranenie nepotrebnych casti kodu a ziskanie poctu klucovych slov */
function get_keywords($array){
	global $ptrue, $boolak;
	$overall_com = 0;
	$output_array = array();

	foreach ($array as $key => $value) {
		$input_file = $value;
		if(($file = @fopen($input_file,"r")) == FALSE)
			if($boolak) exit_status(21);
			else exit_status(2);
		else{			
			$without_comments = without_comments($file);
			$without_macros = without_macro($without_comments);
			$without_strings = without_strings($without_macros);
			$num_of_codes = keywords($without_strings);
			$overall_com += $num_of_codes;
			if($ptrue == 1)	$pom_array = array($key => $num_of_codes); // ak je zadany parameter -p chceme len nazov suboru
			else $pom_array = array(realpath($input_file) => $num_of_codes);
			$output_array = array_merge($output_array, $pom_array);
		}		
	}
	$pom_array = array("CELKEM:" => $overall_com);  //celkove scitanie statistik
	$output_array = array_merge($output_array, $pom_array);

	return $output_array;	
}

/* funkcia ziskanie statistiky o vyskyte hladaneho vyrazu */
function get_patterns($array, $pattern){
	global $ptrue, $boolak;
	$overall_com = 0;
	$output_array = array();

	foreach ($array as $key => $value) {
		$input_file = $value;
		if(($file = @fopen($input_file,"r")) == FALSE)
			if($boolak) exit_status(21);
			else exit_status(2);
		else{
			$content = file_get_contents($input_file);
			$num_of_codes = search_pattern($content, $pattern);
			$overall_com += $num_of_codes;
			if($ptrue == 1)	$pom_array = array($key => $num_of_codes); // ak je zadany parameter -p chceme len nazov suboru
			else $pom_array = array(realpath($input_file) => $num_of_codes);
			$output_array = array_merge($output_array, $pom_array);
		}		
	}
	$pom_array = array("CELKEM:" => $overall_com);  //celkove scitanie statistik
	$output_array = array_merge($output_array, $pom_array);

	return $output_array;	
}

/* funkcia ziskanie poctu komentarov */
function get_comments($array){
	global $ptrue, $boolak;
	$overall_com = 0;
	$output_array = array();

	foreach ($array as $key => $value) {
		$input_file = $value;
		if(($file = @fopen($input_file,"r")) == FALSE)
			if($boolak) exit_status(21);
			else exit_status(2);
		else{			
			$num_of_codes = comments($file);
			$overall_com += $num_of_codes;
			if($ptrue == 1)	$pom_array = array($key => $num_of_codes); // ak je zadany parameter -p chceme len nazov suboru
			else $pom_array = array(realpath($input_file) => $num_of_codes);
			$output_array = array_merge($output_array, $pom_array);
		}		
	}
	$pom_array = array("CELKEM:" => $overall_com);  //celkove scitanie statistik
	$output_array = array_merge($output_array, $pom_array);

	return $output_array;
}

/* funkcia na zobrazovanie vysledko */
function result($array){
	global $output, $file_out, $output_file;
	$overall_com = 0;

	$max_file_length = max_file_spaces($array); // ziskanie dlzky najdlzsej adresy cesty
	$max_num_length = max_num_spaces($array);	// ziskanie dlzky najdlzsieho cisla
	if($output == 0){ 							// ak chceme zobrazit vysldky na STDOUT
		foreach ($array as $key => $value) {
			$left_spaces = spaces($key, $max_file_length);	// spravne obsadenie nazvu suborov;
			$right_spaces = stats_spaces($value, $max_num_length); // spravne obsadenie cisel	
			$overall_com += $value;
			echo $key.$left_spaces.$right_spaces.$value."\n";
		}
	}
	else{										// vypis do suboru
		$file_out = $output_file;
		if(($write = @fopen($file_out,"w")) == FALSE)	// v pripade ze sa subor neda otvorit
			exit_status(3);								// vrati 3
		else{											// inak zapisujeme do suboru
			foreach ($array as $key => $value) {
				$left_spaces = spaces($key, $max_file_length);
				$right_spaces = stats_spaces($value, $max_num_length);
				$overall_com += $value;
				fwrite($write, $key.$left_spaces.$right_spaces.$value."\n");	// zapis do suboru
			}
		}
	}
}

/* spravne obsadenie nazvov suborov */
function spaces($in_file, $num){
	$result = "";

	for ($i = 0; $i <= $num-strlen($in_file); $i++){
		$result .= " ";
	}
	return $result;	
}
/* spravne obsadenie cisel */
function stats_spaces($in_file, $num){
	$result = "";

	for ($i = 1; $i <= $num-strlen($in_file); $i++){
		$result .= " ";
	}
	return $result;	
}
/* ziskanie dlzky najdlzsieho suboru */
function max_file_spaces($array){

	$max_length = 0;

	foreach ($array as $key => $value) {
		if($max_length < strlen($key)) $max_length = strlen($key);
	}
	return $max_length;
}

/* ziskanie dlzky najvacsieho ziskaneho cisla */
function max_num_spaces($array){

	$max_length = 0;

	foreach ($array as $key => $value) {
		if($max_length < strlen($value)) $max_length = strlen($value);
	}
	return $max_length;
}
/* funkcia na vypis help*/
function help(){
	for($j=0;$j<15;$j++) echo "----";
	echo "\n";
	echo "--help - vypise na standartny vystup napovedu skriptu\n";
	echo "--input=fileordir - zadany vstupny subor alebo adresar so zdrojovym kodom v jazyku C\n";
	echo "--nosubdir - prehladavanie bude pouzite len v zadanom adresari, ale uz nie v podadresaroch, nesmie sa kombinovat so zadanim konkretneho subor pomocou --input\n";
	echo "--output=filename - zadany textovy vystupny subor\n";
	echo "-k - vypise pocet vsetkych vyskytov klucovych slov (vyskytujucich sa mimo poznamky a retazci) v kazdom zdrojovom subore a celkom (pre vsetky analyzovane subory)\n";
	echo "-o - vypise pocet vyskytov jednoduchych operatorov (nikoliv oddelovacov apod.) okrem poznanmky, znakove literaly a retazce v kazdom zdrojovom subore a celkom (pre vsetky analyzovane subory)\n";
	echo "-i - vypise pocet vyskytov identifikatorov\n";
	echo "-w=pattern - vyhlada presny textovy retazec pattern vo vsetkych zdrojovych kodoch a vypise pocet neprekryvajucich sa vyskystov na subor i celkom\n";
	echo "-c - vypise celkovy pocet znakov komentarov vratane uvadzajucich znakov komentarov (//, /* a */) na subor a celkom\n";
	echo "-p v kombinaci s predchadzajucimi sposobi, ze subory sa budu vypisovat bez uplnej (absolutnej) cesty k suboru (teda len samotne mena suborov)\n";
	for($k=0;$k<15;$k++) echo "----";
	echo "\n";
	exit(0);
}

parse_arguments($argc, $argv);

?>