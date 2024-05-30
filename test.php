<?php

require "init.php";

use Symfony\Component\Finder\Finder;

$finder = new Finder();
$finder->files()->in("files");

foreach ($finder as $file) {
    $absoluteFilePath = $file->getRealPath();
    $fileNameWithExtension = $file->getRelativePathname();
    echo $absoluteFilePath . " - " . $fileNameWithExtension . "\n";
    // ...
}

echo "\n\n";