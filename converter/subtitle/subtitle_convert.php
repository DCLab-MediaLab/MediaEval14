<?php

if ($argc<2)
	die("Usage: php subtitle_convert.php something.xml");

$xml=json_decode(json_encode(simplexml_load_string(file_get_contents($argv[1]))),TRUE);
//yes, I just did that

function time2sec($time)
{
	$time=explode(".",$time,2);
	$firstpart=explode(":",$time[0],3);
	return $firstpart[0]*3600+$firstpart[1]*60+$firstpart[2].".".$time[1];
}

$stdout = fopen('php://output', 'w');
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
			$sp=trim($sp);
		}

		$row[]=implode(" ",$oneline["span"]);
	}else{
		$row[]=trim($oneline["span"]);
	}

	fputcsv($stdout, $row);
}
?>