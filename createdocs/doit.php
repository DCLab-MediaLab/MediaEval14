<?php

include("../enrichment/concept5.php");
date_default_timezone_set("Europe/Budapest");

if ($argc != 3)
    die("use it as: php some.php INPUTDIR OUTPUTDIR\nWhere INPUTDIR is the dir the cutter used");

/**
 * Process one line (or words)
 * @param type $line
 * @return type
 */
function processLine($line) {
    $line = str_replace(array(".", ",", ":", ";", "?", "!"), " ", $line);
    for ($i = 0; $i < 10; $i++)
        $line = str_replace("  ", " ", $line);

    $line = strtolower($line);
    $ret = explode(" ", $line);
    foreach ($ret as $key => $value) {
        if (trim($value) == "")
            unset($ret[$key]);
    }
    return $ret;
}

/**
 * Merge weight list
 * @param type $into
 * @param type $other
 */
function mergeWordWeights(&$into, $other,$weightNormal=1) {
    foreach ($other as $word => $w) {
        if (!isset($into[$word]))
            $into[$word] = 0;

        $into[$word]+=$w*$weightNormal;
    }
}

/**
 * Process a subtitle file 
 * @param type $f_subtitle
 */
function processSubtitle($f_subtitle) {
    $ret = array();

    $row=0;
    if (($handle = fopen($f_subtitle, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 10000, ";")) !== FALSE) {
            $row++;

            if ($row == 1)
                continue;

            //process the 3rd column
            $words = processLine($data[2]);

            foreach ($words as $word) {
                $newconcept = getConcept($word);
                mergeWordWeights($ret, $newconcept);
                if (!isStopword($word))
                {
                    mergeWordWeights($ret, array($word=>1.5));
                }
            }
            echo(".");
        }
        fclose($handle);
    }

    return $ret;
}

/**
 * Process a limsi file 
 * @param type $f_subtitle
 */
function processLimsi($f_subtitle) {
    $ret = array();

    $row=0;
    if (($handle = fopen($f_subtitle, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 10000, ";")) !== FALSE) {
            $row++;

            if ($row == 1)
                continue;

            //process the 3rd column
            $words = processLine($data[11]);

            foreach ($words as $word) {
                //ignore {fw}
                if (trim($word)=="{fw}")
                    continue;
                
                $newconcept = getConcept($word);
                mergeWordWeights($ret, $newconcept,$data[8]);
                if (!isStopword($word))
                {
                    mergeWordWeights($ret, array($word=>1.5*$data[8]));
                }
            }
            echo(".");
        }
        fclose($handle);
    }

    return $ret;
}

/**
 * Process a lium file 
 * @param type $f_subtitle
 */
function processLium($f_subtitle) {
    $ret = array();

    $row=0;
    if (($handle = fopen($f_subtitle, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 10000, ";")) !== FALSE) {
            $row++;

            if ($row == 1)
                continue;

            //process the 3rd column
            $words = processLine($data[4]);

            foreach ($words as $word) {
                $newconcept = getConcept($word);
                mergeWordWeights($ret, $newconcept,$data[5]);
                if (!isStopword($word))
                {
                    mergeWordWeights($ret, array($word=>1.5*$data[5]));
                }
            }
            echo(".");
        }
        fclose($handle);
    }

    return $ret;
}

/**
 * Process a nst file 
 * @param type $f_subtitle
 */
function processNst($f_subtitle) {
    $ret = array();

    $row=0;
    if (($handle = fopen($f_subtitle, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 10000, ";")) !== FALSE) {
            $row++;

            if ($row == 1)
                continue;

            //process the 3rd column
            $words = processLine($data[5]);

            foreach ($words as $word) {
                $newconcept = getConcept($word);
                mergeWordWeights($ret, $newconcept);
                if (!isStopword($word))
                {
                    mergeWordWeights($ret, array($word=>1.5));
                }
            }
            echo(".");
        }
        fclose($handle);
    }

    return $ret;
}

/**
 * Processing one segment of the video
 * @param type $name
 * @param type $f_subtitle
 * @param type $f_limsi
 * @param type $f_lium
 * @param type $f_nst
 */
function processOneSegment($name,$f_subtitle, $f_limsi, $f_lium, $f_nst) {
    $allweight = array();
    //generate word list with weights
    echo("\t subtitle:");
    $neww = processSubtitle($f_subtitle);
    mergeWordWeights($allweight, $neww,1);
    echo("\n");
    
    echo("\t limsi:");
    $neww = processLimsi($f_limsi);
    mergeWordWeights($allweight, $neww,0.6);
    echo("\n");
    
    echo("\t lium:");
    $neww = processLium($f_lium);
    mergeWordWeights($allweight, $neww,0.6);
    echo("\n");
    
    echo("\t nst:");
    $neww = processNst($f_nst);
    mergeWordWeights($allweight, $neww,0.6);
    echo("\n");

    //sort it, than do the magic
    arsort($allweight);
    
    $ret=implode(" ",array_keys($allweight));
    
    //do an implode
    return json_encode(array(array("title"=>$name,"content"=>$ret)));
}

//grab the programs
$musorok = scandir($argv[1]);

foreach ($musorok as $m1) {
    if (($m1 == ".") || ($m1 == "..") || ($m1 == ".DS_Store") || (!is_dir($argv[1] . "/" . $m1)))
        continue;

    echo("Processing: $m1\n");

    //grab the files inside the limsi dir
    $subtitle_files = scandir($argv[1] . "/" . $m1 . "/transcript-limsi/");
    $subtitle_files = array_diff($subtitle_files, array(".", "..", ".DS_Store"));

    //on each file
    foreach ($subtitle_files as $onefile) {
        echo(" -$onefile\n");
        $name=str_replace("_transcript-limsi_", "_", $onefile);
        $name=str_replace(".csv","",$name);
        $ret = processOneSegment($name,
                //subtitle file
                $argv[1] . "/" . $m1 . "/subtitle/" . str_replace("_transcript-limsi_", "_subtitle_", $onefile),
                //limsi
                $argv[1] . "/" . $m1 . "/transcript-limsi/" . $onefile,
                //lium
                $argv[1] . "/" . $m1 . "/transcript-lium/" . str_replace("_transcript-limsi_", "_transcript-lium_", $onefile),
                //NST
                $argv[1] . "/" . $m1 . "/transcript-NST/" . str_replace("_transcript-limsi_", "_transcript-NST_", $onefile)
        );

        //generate output file
        file_put_contents($argv[2] . "/" . str_replace("_transcript-limsi_", "_", $onefile) . ".json", $ret);
    }
}