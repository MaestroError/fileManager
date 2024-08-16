<?php

namespace Maestroerror\Contracts;

interface FilesystemInterface {
    public function getName();
    public function getPath();
    public function getSize();
    public function getDate();
}