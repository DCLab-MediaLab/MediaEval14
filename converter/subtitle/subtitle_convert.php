<?php

if ($argc<3)
	die("Usage: php subtitle_convert.php data_root outputdir\n");

$infiles = scandir($argv[1]."/subtitles");
foreach($infiles as $key => $value)
{
	if (strlen($value)<4)
	{
		unset($infiles[$key]);
		continue;
	}
	if (strtolower(substr($value,-4))!=".xml")
	{
		unset($infiles[$key]);
		continue;
	}
}

function time2sec($time)
{
	$time=explode(".",$time,2);
	$firstpart=explode(":",$time[0],3);
	return $firstpart[0]*3600+$firstpart[1]*60+$firstpart[2].".".$time[1];
}

function doConvert($from,$to)
{
	$xml=json_decode(json_encode(simplexml_load_string(file_get_contents($from))),TRUE);
	//yes, I just did that


	$stdout = fopen($to, 'w');
	foreach($xml['body']['div']['p'] as $oneline)
	{
		$row=array();

		//date convert
		$row[]=time2sec($oneline["@attributes"]["begin"]);
		$row[]=time2sec($oneline["@attributes"]["end"]);

		if (is_array($oneline["span"]))
		{
			//trim whitespace before/after
			foreach($oneline["span"] as &$sp)
			{
				if (is_array($sp))
				{
					$sp="";
				}else{
					$sp=trim($sp);
				}
			}

			$row[]=implode(" ",$oneline["span"]);
		}else{
			$row[]=trim($oneline["span"]);
		}

		fputcsv($stdout, $row);
	}
}

foreach($infiles as $onefile)
{
	$out=$argv[2]."/".str_replace(".xml",".subtitle.csv",basename($onefile));
	doConvert($argv[1]."/subtitles/".$onefile,$out);
	echo("Processing: ".basename($onefile)."\n");
}
?>