<?php

if ($argc != 3)
    die("Use it like php fastcut.php <indir> <outdir>");

//grab scenecut file and translate it to list with numbers
function grabKeyframelist($indir,$outdir,$filepart)
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

function processSubtitle($indir,$outdir,$filepart,&$frames){
    //load subtitle and create the following format
    $subtitle=explode("\n",  file_get_contents($indir."/".$filepart.".subtitle.csv"));
    foreach($subtitle as $key=>&$line)
    {
        $csv=explode(";",$line,3);
        if (count($csv)!=3)
        {
            unset($subtitle[$key]);
            continue;
        }
        
        if ($key==0)
        {
            $line=array((float)-1,(float)-1,$line);
            continue;
        }
        
        $line=array((float)$csv[0],(float)$csv[1],$line);
    }
    
    //create dirs
    @mkdir($outdir."/".$filepart."/subtitle/",0777,true);
    for ($id=0;$id<count($frames)-1;$id++)
    {
        $start=$frames[$id];
        $end=$frames[$id+1];
        
        $out="";
        foreach($subtitle as $line)
        {
            //first line
            if ($line[0]==-1)
            {
                $out.="_filename_;".$line[2]."\n";
                continue;
            }
            
            //matching lines
            if (($line[0]>=$start) && ($line[1]<$end))
            {
                $out.=$filepart.";".$line[2]."\n";
            }
        }
        
        //write it out
        $fname=$outdir."/".$filepart."/subtitle/".$filepart."_".sprintf("%03d",$id).".csv";
        file_put_contents($fname, $out);
    }
}

function processLimsi($indir,$outdir,$filepart,&$frames){
    //load subtitle and create the following format
    $subtitle=explode("\n",  file_get_contents($indir."/".$filepart.".transcript-limsi.csv"));
    foreach($subtitle as $key=>&$line)
    {
        $csv=explode(";",$line,12);
        if (count($csv)!=12)
        {
            unset($subtitle[$key]);
            continue;
        }
        
        if ($key==0)
        {
            $line=array((float)-1,(float)-1,$line);
            continue;
        }
        
        $line=array((float)$csv[10],(float)$csv[9]+(float)$csv[10],$line);
    }
    
    //create dirs
    @mkdir($outdir."/".$filepart."/transcript-limsi/",0777,true);
    for ($id=0;$id<count($frames)-1;$id++)
    {
        $start=$frames[$id];
        $end=$frames[$id+1];
        
        $out="";
        foreach($subtitle as $line)
        {
            //first line
            if ($line[0]==-1)
            {
                $out.="_filename_;".$line[2]."\n";
                continue;
            }
            
            //matching lines
            if (($line[0]>=$start) && ($line[1]<$end))
            {
                $out.=$filepart.";".$line[2]."\n";
            }
        }
        
        //write it out
        $fname=$outdir."/".$filepart."/transcript-limsi/".$filepart."_".sprintf("%03d",$id).".csv";
        file_put_contents($fname, $out);
    }
}

function processLium($indir,$outdir,$filepart,&$frames){
    //load subtitle and create the following format
    $subtitle=explode("\n",  file_get_contents($indir."/".$filepart.".transcript-lium.csv"));
    foreach($subtitle as $key=>&$line)
    {
        $csv=explode(";",$line,6);
        if (count($csv)!=6)
        {
            unset($subtitle[$key]);
            continue;
        }
        
        if ($key==0)
        {
            $line=array((float)-1,(float)-1,$line);
            continue;
        }
        
        $line=array((float)$csv[2],(float)$csv[2]+(float)$csv[3],$line);
    }
        
    //create dirs
    @mkdir($outdir."/".$filepart."/transcript-lium/",0777,true);
    for ($id=0;$id<count($frames)-1;$id++)
    {
        $start=$frames[$id];
        $end=$frames[$id+1];
        
        $out="";
        foreach($subtitle as $line)
        {
            //first line
            if ($line[0]==-1)
            {
                $out.="_filename_;".$line[2]."\n";
                continue;
            }
            
            //matching lines
            if (($line[0]>=$start) && ($line[1]<$end))
            {
                $out.=$filepart.";".$line[2]."\n";
            }
        }
        
        //write it out
        $fname=$outdir."/".$filepart."/transcript-lium/".$filepart."_".sprintf("%03d",$id).".csv";
        file_put_contents($fname, $out);
    }
}

function processNST($indir,$outdir,$filepart,&$frames){
    //load subtitle and create the following format
    $subtitle=explode("\n",  file_get_contents($indir."/".$filepart.".transcript-NST.csv"));
    foreach($subtitle as $key=>&$line)
    {
        $csv=explode(";",$line,6);
        if (count($csv)!=6)
        {
            unset($subtitle[$key]);
            continue;
        }
        
        if ($key==0)
        {
            $line=array((float)-1,(float)-1,$line);
            continue;
        }
        
        $line=array((float)$csv[4],(float)$csv[3],$line);
    }
    
    
        
    //create dirs
    @mkdir($outdir."/".$filepart."/transcript-NST/",0777,true);
    for ($id=0;$id<count($frames)-1;$id++)
    {
        $start=$frames[$id];
        $end=$frames[$id+1];
        
        $out="";
        foreach($subtitle as $line)
        {
            //first line
            if ($line[0]==-1)
            {
                $out.="_filename_;".$line[2]."\n";
                continue;
            }
            
            //matching lines
            if (($line[0]>=$start) && ($line[1]<$end))
            {
                $out.=$filepart.";".$line[2]."\n";
            }
        }
        
        //write it out
        $fname=$outdir."/".$filepart."/transcript-NST/".$filepart."_".sprintf("%03d",$id).".csv";
        file_put_contents($fname, $out);
    }
}

function processOne($indir,$outdir,$filepart){    
    //grab frames
    $frames=grabKeyframelist($indir, $outdir, $filepart);   
    
    echo(".");
    processSubtitle($indir, $outdir, $filepart,$frames);
    echo(".");
    processLimsi($indir, $outdir, $filepart,$frames);
    echo(".");
    processLium($indir, $outdir, $filepart, $frames);
    echo(".");
    processNST($indir, $outdir, $filepart, $frames);
    echo(".");
    echo("\n");
}

$files = scandir($argv[1]);
$file_list=array();
foreach ($files as $f) {
    if (strpos($f, ".tar.scenecut.csv") > 0) {
        $file_list[]=str_replace(".tar.scenecut.csv","",$f);
    }
}

echo("Let the hunger games begin!\n");
for($i=0;$i<count($file_list);$i++)
{
        echo("Processing (".($i+1)."/".count($file_list).": ".$file_list[$i]." ");
        processOne($argv[1], $argv[2],$file_list[$i]);
}
?>