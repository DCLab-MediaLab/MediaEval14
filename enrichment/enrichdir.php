<?php
include("concept5.php");
date_default_timezone_set("Europe/Budapest");

if ($argc!=2)
	die("use it as php some.php DIR");

function processLine($line)
{
	$line=str_replace(array(".",",",":",";","?","!"), " ", $line);
	for ($i=0;$i<10;$i++)
		$line=str_replace("  ", " ", $line);

	$line=strtolower($line);
	$ret=explode(" ",$line);
	foreach($ret as $key => $value)
		{
			if (trim($value)=="")
				unset($ret[$key]);
		}
	return $ret;
}


$files=scandir($argv[1]);
foreach($files as $f)
{
	echo($f."\n");
	/*
	20080401_002000_bbcthree_pulling.json.csv
	20080401_002000_bbcthree_pulling.subtitle.csv
	20080401_002000_bbcthree_pulling.tar.info.csv
	20080401_002000_bbcthree_pulling.tar.scenecut.csv
	20080401_002000_bbcthree_pulling.transcript-NST.csv
	20080401_002000_bbcthree_pulling.transcript-limsi.csv
	20080401_002000_bbcthree_pulling.transcript-lium.csv
	*/
	if (strpos($f, ".subtitle.csv")>0)
	{
		//subtitle file
		$row = 0;
		if (($handle = fopen($argv[1]."/".$f, "r")) !== FALSE) {
		    while (($data = fgetcsv($handle, 10000, ";")) !== FALSE) {
		    	$row++;
		        if ($row==1)
		        	continue;

		        //process line
		        $words=processLine($data[2]);
		        
		        foreach ($words as $word) {
		        	getConcept($word);
		        	echo(".");
		       	}
		    }
		    fclose($handle);
		}
	}
}
?>