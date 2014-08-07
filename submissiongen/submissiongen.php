<?php

include("../enrichment/concept5.php");

$transcripts = array(
    'subtitle' => 'M',
    'limsi' => "I",
    //'lium' => "U",
    'nst' => "S",
    'content' => 'IMSU');
/*
 * Run names should follow this scheme:
  me14sh_TEAM_RunType_Priority_Segmentation_TranscriptType_AdditionalFeatures_Description.txt, where
  TEAM is your team identifier.
  RunType is one of
  S: Search run
  L: Linking run
  Priority is a number (low = high priority) assigned by the participant
  1: highest priority
  9: lowest priority
  Segmentation is a combination of
  Ss: speech sentence segmentation
  Sp: speech segment segmentation
  Sh: shot segmentation
  F: fixed length segmentation
  L: lexical cohesian segmentation
  P: use prosodic features for segmentation
  O: other segmentation
  TranscriptType is one of
  I: LIMSI transcripts
  M: Manual subtitles
  S: NST/Sheffield
  U: LIUM transcripts
  N: No speech information
  AdditionalFeatures is a combination of
  M: Metadata
  V: Visual features
  O: Other information
  N: No additional features
  Description is a very short for the approach that produced the run ()
 */

function getSubtitleForCut($name) {
    if (file_exists("cache/e" . md5($name))) {
        return unserialize(file_get_contents("cache/e" . md5($name)));
    }
    $r = json_decode(file_get_contents("http://hiddenmon.cloudapp.net:8983/solr/mm-test/select?q=title:" . urlencode($name) . "&wt=json"), true);


    file_put_contents("cache/e" . md5($name), serialize($r));
    return $r;
}

function getResultFromSolr($search) {
    if (file_exists("cache/" . md5($search))) {
        return unserialize(file_get_contents("cache/" . md5($search)));
    }
    $r = file_get_contents("http://hiddenmon.cloudapp.net:8983/solr/mm-test/select?q=" . urlencode($search) . "&rows=30&fl=fileName%3Atitle%2C&wt=csv&indent=true");
    if (strlen($r) == 0)
        return array();

    $r = explode("\n", $r);
    unset($r[0]);
    file_put_contents("cache/" . md5($search), serialize($r));
    return $r;
}

function genTimes() {
    if (file_exists("data/times/20080527_170000_bbcone_newsround"))
        return;
    echo("Generate times...\n");
    $lines = explode("\n", file_get_contents("data/times.csv"));
    echo("Processing times...\n");
    $ret = "";
    $lastold = null;

    foreach ($lines as $l1) {
        $vals = explode(",", $l1, 3);
        if (count($vals) < 3)
            continue;

        foreach ($vals as &$v1) {
            $v1 = trim($v1);
        }

        $origfile = explode("_", $vals[0]);
        array_pop($origfile);
        $origfile = implode("_", $origfile);

        if ($lastold != $origfile) {
            if (!is_null($lastold))
                file_put_contents("data/times/" . $lastold, $ret);

            $ret = "";
            $lastold = $origfile;
        }

        $ret.=$l1 . "\n";
    }
    echo("Generation done done.\n");
}

function getSegmentStartEnd($segment) {
    $origfile = explode("_", $segment);
    array_pop($origfile);
    $origfile = implode("_", $origfile);

    $lines = explode("\n", file_get_contents("data/times/" . $origfile));
    foreach ($lines as $l1) {
        $vals = explode(",", $l1, 3);
        if ((count($vals) < 3) || (trim($vals[0]) != $segment))
            continue;

        foreach ($vals as &$v1) {
            $v1 = trim($v1);
        }

        return array((float) $vals[1], (float) $vals[2]);
    }


    echo("ERROR IN TIMES");
    return array(0, 0);
}

function getTimeFromSeconds($sec) {
    $sec = floor($sec);

    return floor($sec / 60) . "." . ($sec % 60);
}

function getFormattedResult($res, $identifier, $hasjumpin, $run, $filterone = '') {
    $ret = "";
    //result list
    $rank = 1;
    foreach ($res as $oneres) {
        if ($oneres == '')
            continue;

        echo(".");

        //generate original filename
        $origfile = explode("_", $oneres);
        array_pop($origfile);
        $origfile = implode("_", $origfile);

        if ($origfile == $filterone)
            continue;

        //get the time
        $time = getSegmentStartEnd($oneres);

        //10 sec rule
        if ($time[1] - $time[0] < 10)
            $time[1] = $time[0] + 10;

        if ($time[1] - $time[0] > 120) {
            $time[1] = $time[0] + 120;
        }

        //get one line
        $line = array();
        $line[] = $identifier;
        $line[] = $origfile;
        $line[] = "Q0";
        $line[] = $origfile;
        $line[] = getTimeFromSeconds($time[0]);
        $line[] = getTimeFromSeconds($time[1]);



        if ($hasjumpin)
            $line[] = getTimeFromSeconds($time[0]);

        $line[] = $rank;
        $line[] = 1 - (($rank - 1) / count($res)) * 0.8;
        $line[] = $run;

        $ret.=implode(" ", $line) . "\n";
        $rank++;
    }

    return $ret;
}

function searchSubtask() {
    global $transcripts;
    //load xml
    $xml = simplexml_load_string(file_get_contents("data/me14sh_search_testSet_queries.xml"));
    $json = json_encode($xml);
    $array = json_decode($json, TRUE);

    //for each transcript
    $prio = 1;
    foreach ($transcripts as $transcriptname => $transcriptcode) {
        $filename = "me14sh_DCLab2014_S_" . $prio . "_Sh_" . $transcriptcode . "_N_ConceptEnrichment";
        echo($filename . "\n");
        $prio++;

        $res = "";
        //for each query
        foreach ($array['top'] as $top) {

            //create the query
            $top['queryText'] = str_replace("  ", " ", $top['queryText']);
            $q = explode(" ", $top['queryText']);
            foreach ($q as $k => &$q1) {
                if (trim($q1) == '') {
                    unset($q[$k]);
                    continue;
                }
                $q1 = strtolower(morpho(trim($q1)));
                if ($transcriptname != 'content') {
                    $q1 = $transcriptname . "_concept:" . trim($q1) . "";
                } else {
                    $q1 = $transcriptname . ":" . trim($q1) . "";
                }
            }
            $q = implode(" AND ", $q);

            $resSolr = getResultFromSolr($q);
            //echo($q." (".count($resSolr).")\n");
            $res.=getFormattedResult($resSolr, $top['queryId'], false, $filename);
        }

        //write to file
        file_put_contents('result/' . $filename . ".txt", $res);
    }
}

function anchorSubtask() {
    global $transcripts;
    //load xml
    $xml = simplexml_load_string(file_get_contents("data/me14sh_linking_testSet_anchors.xml"));
    $json = json_encode($xml);
    $array = json_decode($json, TRUE);

    //for each transcript
    $prio = 1;
    foreach ($transcripts as $transcriptname => $transcriptcode) {
        $filename = "me14sh_DCLab2014_L_" . $prio . "_Sh_" . $transcriptcode . "_N_ConceptEnrichment";
        echo($filename . "\n");
        $prio++;

        $res = "";
        //for each query
        foreach ($array['anchor'] as $anchor) {

            $times = explode("\n", file_get_contents("data/times/" . substr($anchor['fileName'], 1)));
            $allcuts = array();

            $from = explode(".", $anchor['startTime']);
            $from = $from[0] * 60 + $from[1];

            $to = explode(".", $anchor['endTime']);
            $to = $to[0] * 60 + $to[1];

            foreach ($times as $t1) {
                $vals = explode(",", $t1, 3);
                if (count($vals) < 3)
                    continue;

                foreach ($vals as &$v1) {
                    $v1 = trim($v1);
                }
                //getSubtitleForCut(

                for ($i = $from; $i < $to; $i++) {
                    if (($vals[1] <= $i) && ($i <= $vals[2]) && (!in_array($vals[0], $allcuts)))
                        $allcuts[] = $vals[0];
                }
            }

            //new document from parts
            $allwords = array();
            foreach ($allcuts as $c1) {
                $doc = getSubtitleForCut($c1);
                $thedoc = $doc['response']['docs'][0];

                //concept / transcript
                $words = explode(" ", $thedoc[$transcriptname][0]);
                foreach ($words as $k => $word) {
                    $word = strtolower($word);

                    if (isStopword($word)) {
                        unset($words[$k]);
                        continue;
                    }

                    $word = morpho($word);
                    if (isStopword($word)) {
                        unset($words[$k]);
                        continue;
                    }

                    if (in_array($word, $allwords))
                        continue;
                    $allwords[] = $word;
                }
            }

            //use the words in a query
            foreach ($words as $k => &$q1) {
                if (trim($q1) == '') {
                    unset($q[$k]);
                    continue;
                }
                $q1 = strtolower(morpho(trim($q1)));
                if ($transcriptname != 'content') {
                    $q1 = $transcriptname . "_concept:" . trim($q1) . "";
                } else {
                    $q1 = $transcriptname . ":" . trim($q1) . "";
                }
            }
            $words=array_slice($words,0,15);
            $q = implode(" AND ", $words);

            $resSolr = getResultFromSolr($q);
            $res.=getFormattedResult($resSolr, $anchor['anchorId'], false, $filename, substr($anchor['fileName'], 1));

            //create the query
        }

        //write to file
        file_put_contents('result/' . $filename . ".txt", $res);
    }
}

genTimes();
//searchSubtask();
anchorSubtask();
?>