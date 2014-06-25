<?php

if ($argc<2)
	die("Usage: php input_dir output_dir");
	
$input = $argv[1].'/json';
$output = $argv[2];

$files = scandir($input);

$counter = 0;

foreach($files as $file){
	if($file == '.' || $file == '..') continue;

	$counter++;
	$csv = fopen($output."/test".$file.".csv","w");
	//add BOM to fix UTF-8 in Excel
	fputs($csv, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));
	$json = file_get_contents($input.'/'.$file);
	$array = json_decode($json);
	$firstLineKeys = array('diskref','service','date','time','duration','uri','canonical','depiction','title','description','original_description','subtitles_uri','when','id','filename','source','service_name');
	 fputcsv($csv, $firstLineKeys, ';');
	 $line = array();
	 array_push($line, $array->diskref);
	 array_push($line, $array->service);
	 array_push($line, $array->date);
	 array_push($line, $array->time);
	 array_push($line, $array->duration);
	 array_push($line, $array->uri);
	 array_push($line, $array->canonical);
	 array_push($line, $array->depiction);
	 array_push($line, $array->title);
	 array_push($line, $array->description);
	 array_push($line, $array->original_description);
	 if($array->subtitles)
	 	array_push($line, $array->media->subs->uri);
	 else
	 	array_push($line, '');
	 array_push($line, $array->when);
	 array_push($line, $array->id);
	 array_push($line, $array->filename);
	 array_push($line, $array->source);
	 array_push($line, $array->service_name);

	 fputcsv($csv, $line, ';');

	fclose($csv);
	
	
}


?>