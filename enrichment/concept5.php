<?php

error_reporting(E_ALL | E_STRICT);

date_default_timezone_set("Europe/Budapest");

require_once(dirname(__FILE__) . '/morphy/src/common.php');
// set some options
$opts = array(
	// storage type, follow types supported
	// PHPMORPHY_STORAGE_FILE - use file operations(fread, fseek) for dictionary access, this is very slow...
	// PHPMORPHY_STORAGE_SHM - load dictionary in shared memory(using shmop php extension), this is preferred mode
	// PHPMORPHY_STORAGE_MEM - load dict to memory each time when phpMorphy intialized, this useful when shmop ext. not activated. Speed same as for PHPMORPHY_STORAGE_SHM type
	'storage' => PHPMORPHY_STORAGE_MEM,
	// Extend graminfo for getAllFormsWithGramInfo method call
	'with_gramtab' => false,
	// Enable prediction by suffix
	'predict_by_suffix' => true, 
	// Enable prediction by prefix
	'predict_by_db' => true
);

// Path to directory where dictionaries located
$dir = dirname(__FILE__) . '/morphy/dicts';

// Create descriptor for dictionary located in $dir directory with russian language
$dict_bundle = new phpMorphy_FilesBundle($dir, 'en_en');

// Create phpMorphy instance
try {
	$morphy = new phpMorphy($dict_bundle, $opts);
} catch(phpMorphy_Exception $e) {
	die('Error occured while creating phpMorphy instance: ' . $e->getMessage());
}

$stopwords=explode("\r\n",file_get_contents(dirname(__FILE__)."/stopwords.txt"));

function morpho($word)
{
	global $morphy;

	try {
		$base_form=$morphy->getBaseForm(strtoupper($word));
		if (false === $base_form)
		{
			//OMG, cannot find it
			return $word;
		}
		
		if (count($base_form)>0){
			return strtolower($base_form[0]); 
		}

	}catch(phpMorphy_Exception $e) {
		//silent error
		//die('Error occured while text processing: ' . $e->getMessage());
	}

	return $word;
}

function fetchFromWeb($word)
{
	$word=str_replace(" ", "_", $word);

	$url="http://conceptnet5.media.mit.edu/data/5.2/assoc/list/en/".$word."?limit=50&filter=/c/en";

	if (!file_exists(dirname(__FILE__)."/cache/".md5($url)))
	{
		$webdata=file_get_contents($url);
		if (strlen($webdata)>0)
		{
			file_put_contents(dirname(__FILE__)."/cache/".md5($url), $webdata);
		}
	}

	return @file_get_contents(dirname(__FILE__)."/cache/".md5($url));
}

function isStopword($word)
{
	global $stopwords;

	if (in_array($word, $stopwords))
		return true;

	return false;
}

/**
 * Returns a list of concepts with weights
 */
function getConcept($word)
{
	$word=strtolower($word);

	if (isStopword($word))
		return array();

	$word=morpho($word);
	if (isStopword($word))
		return array();	

	$web=fetchFromWeb($word);
	$list=@json_decode($web);
	$ret=array();
	foreach($list->similar as $oneSim)
	{
		$oneword=$oneSim[0];
		$oneword=substr($oneword, strlen("/c/en/"));

		if (strpos($oneword, "/neg")>0)
		{
			$oneSim[1]=$oneSim[1]*0.5;			
			$oneword=str_replace("/neg", "", $oneword);
		}

		$oneword=str_replace("_", " ", $oneword);

		if ($word==$oneword)
			continue;

		$ret[$oneword]=$oneSim[1];
	}

	return $ret;
}

?>