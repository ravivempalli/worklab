<?php
/* Sync file to CDN.
   TODO : read a config file for mapping source to destination directory
   TODO : read actions from the config file so we can process such as zip and set expires headers
   Uses onepica's models - so it expects onepica installed
   Currently will use the CDN info from onepica
   Usage : $0 -s <sourcefile> -d <dest> => copy one file from source to destination
                                           destination is a bucket in S3, etc
                                           Useful with inotifywait
   TODO : $0 -s <sourcefile> -d <dest> -sync => recursively sync all files
   TODO : $0 -s <sourcefile> -config <config_file> => read src to dest mapping and actions from config file
*/

$cp = "/usr/java/jdk1.7.0_71/jre/lib";
require_once("../app/Mage.php");
Mage::app('default');

function usage()
{
  echo "Usage : php " . $argv[0] . "  -s <sourcefile> -d <dest> -compress -minify [-sync]\n";
  echo "Sync one file from source to destination\n";
  echo "-sync is not supported as yet, but will recursively sync all files\n";
  exit;
}

if(count($argv) <= 4){
  usage();
}

$basepath = "";
$destination = "";
$compress = false;
$minify = false;
for($i = 1; $i < count($argv); $i++){
 if($argv[$i] == '-b'){
   $basepath = $argv[$i + 1];
   $i++;
 }
 else if($argv[$i] == '-s'){
   $sourcefile = $argv[$i + 1];
   $i++;
 }else if($argv[$i] == '-d'){
   $destination = $argv[$i + 1];
   $i++;
 } else if($argv[$i] == '-compress'){
   $compress = true;
 } else if($argv[$i] == '-minify'){
   $minify = true;
 }
 else{
   echo "unknown option at $i " . $argv[$i] . "\n";
   usage();
 }
}

$cds = Mage::Helper('imagecdn')->factory();
$compression = Mage::getStoreConfig('imagecdn/general/compression');

function xferfile($cds, $sourcefile, $basepath, $compress=1, $minify=0)
{
  global $cp;
  try{
    if($cds->useCdn()) {
      /* use cds->save() to save the file */
      if(!file_exists($sourcefile)){
        throw new Exception("File $sourcefile does not exist");
      }
      $fileparts = pathinfo($sourcefile);
      $filename = $fileparts['basename'];
      $extension = $fileparts['extension'];
      $headers = NULL;
      if($minify){
        $minify_extensions = array("css", "js");
        if(in_array($extension, $minify_extensions)){
          $tempminify = tempnam(sys_get_temp_dir(), 'rrapcdn') . "." . $filename;
          $cmd = "java -jar " . $cp . "/yuicompressor-2.4.8.jar $sourcefile -o $tempminify";
echo $cmd;
          system($cmd);
          $sourcefile = $tempminify;
        }
      }
      if($compress){
        $compress_extensions = array("css", "js", "eot", "ttf", "woff");
        if(in_array($extension, $compress_extensions)){
          $temp = tempnam(sys_get_temp_dir(), 'rrapcdn') . "." . $filename;
          system("gzip -9 -c $sourcefile > $temp");
          $sourcefile = $temp;
          $headers = array();
          $headers['Content-Encoding']='gzip';
echo "Source file is $sourcefile \n";
        }
      }
      $path = $fileparts['dirname'];
      if(strpos($path, strpos($path, $basepath)) == 0){
        $destpath = substr($path, strlen($basepath));
      }
      else $destpath = $path;
      $cds->save($destpath . "/" . $filename, $sourcefile, $headers);
      if($minify && isset($tempminify)){
        @unlink($tempminify);
      }
      if($compress && isset($temp)){
        @unlink($temp);
      }
    }else{
      throw new Exception( "ERROR : CDN not setup\n");
    }
  }catch(Exception $e){
    echo "Exception : " . $e->getMessage() . "\n";
  }
}

echo "Syncing $sourcefile ...";
xferfile($cds, $sourcefile, $basepath, $compress, $minify);
echo "Done\n";
