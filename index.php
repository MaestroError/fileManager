<?php 
require "src/fileManager.php";
date_default_timezone_set("Asia/Tbilisi");

$test = new Pinguinus\fileManager();

$array = [
    "action" => "rename",
    "target" => "testMariam",
    "value" => "newName"
];

// add new file if not exists
$test->add('test5.txt');
$test->remove("mariami");
$test->add("testfolder/f1/f2/f3");

// get back to root folder
$test->open("");

// save file to directory
$test->save('newFileF123.txt', "testfolder/f1/S2/S3");
// rename file
$test->rename('newFileF123.txt', "newFileRenamed.txt");

// open directory and remove directory in it
$test->open("testFolder/f1");
$test->remove("f2");

// equals $test->open("");
$test->root();

echo $test::pwd();

// print_r($test);
