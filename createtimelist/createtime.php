<?php

if ($argc != 2)
    die("Use it like php createtime.php <indir>");

//grab scenecut file and translate it to list with numbers
function grabKeyframelist($indir,$filepart)
{
    //frames
    $lines=explode("\n",  file_get_contents($indir."/".$filepart.".tar.scenecut.csv"));
    foreach($lines as $key=>&$l1)
    {
        $l1=explode(";",$l1,2);
        if (count($l1)!=2)
        {
            unset($lines[$key]);
            continue;
        }
        if ($key==0)
        {
            $l1[1]="0";
        }
        $l1=(float)$l1[1];
    }
    
    //fps
    $info=explode("\n",  file_get_contents($indir."/".$filepart.".tar.info.csv"));
    $info=explode(";",$info[1]);
    $fps=(float)$info[2];
    
    //use fps on times
    foreach($lines as &$l1)
    {
        $l1/=$fps;
    }
    
    return $lines;
}

function processOne($indir,$filepart){    
    //grab frames
    $frames=grabKeyframelist($indir, $filepart);   
    
    for ($id=0;$id<count($frames)-1;$id++)
    {
        $start=$frames[$id];
        $end=$frames[$id+1];
        
        echo($filepart."_".sprintf("%03d",$id)." , ".number_format($start,2,".",'')." , ".number_format($end,2,".",'')."\n");
    }
}

$files = scandir($argv[1]);
$file_list=array();
foreach ($files as $f) {
    if (strpos($f, ".tar.scenecut.csv") > 0) {
        $file_list[]=str_replace(".tar.scenecut.csv","",$f);
    }
}

for($i=0;$i<count($file_list);$i++)
{
        processOne($argv[1],$file_list[$i]);
}
?>