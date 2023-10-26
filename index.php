<?php

require "init.php";

use maestroerror\FileManager;

/** 
 * Array of data needed for actions:
 *  action => METHOD NAME
 *  target => FILE/DIR NAME
 *  value => NEEDED VALUE / NULL
 *  uri => CURRENT URI (from root - empty string for root folder)
 */
$action = [
    "action" => "rename",
    "target" => "testFolder",
    "value" => "newName",
    "uri" => ""
];

/**
 * @todo add action method for following functionality and action object option
 *  Action example:
 *      FileManager::folder("files", $action['uri'])->{$action['action']}($action['target'], $action["value"]);
 *  Equals to:
 *      FileManager::folder("files", "")->rename("testFolder", "newName");
 */

$test = FileManager::folder("files", "");

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

// Test pwd
echo $test::pwd() . "\n";

// Test tree
print_r($test->getTree());

// Test ls
print_r($test::ls());

// Test files
print_r($test::files());

// Test filesAll (recursively)
print_r($test::filesAll());

// print_r($test);
