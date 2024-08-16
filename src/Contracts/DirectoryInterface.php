<?php

namespace Maestroerror\Contracts;

interface DirectoryInterface {
    public function getFiles();
    public function getDirectories();
    public function tree();
}