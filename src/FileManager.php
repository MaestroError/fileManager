<?php

namespace maestroerror;

class FileManager
{
    /**
     * PWD - Current working directory
     *
     * @var string
     */
    public string $currentDirectory;

    /**
     * Root, where fileManager starts scanning
     *
     * @var string
     */
    protected string $root;

    /**
     * Inner URI from root folder (to display)
     *
     * @var string
     */
    public string $uri;

    // ??
    public $filePath;

    /**
     * Root's absolute path
     *
     * @var string
     */
    protected string $rootPath;

    /**
     * Array with folder names (key) and locations from root (value)
     * Used for breadcrumbs
     *
     * @var array
     */
    protected array $path = [
        "files" => "/",
        "games" => "/games/"
    ];

    /**
     * Data about the current open folder (URI)
     * File/Folder name => array with info
     *
     * @var array
     */
    protected array $currentData = [
        "files" => [
            "type" => "Directory",
        ],
        "test.txt" => [
            "type" => "file",
            "format" => "text/plain",
            "real_path" => "/fol/der/test.txt",
            "uri" => "/files/test.txt",
            "updated" => "2013-23-02"
        ]
    ];

    /**
     * Instance of class to access from static
     *
     * @var FileManager
     */
    protected static FileManager $inst;

    /**
     * Constructor
     *
     * @param string $uri
     * @param string $root
     */
    public function __construct(string $uri = "", string $root = "files")
    {
        $this -> root = $root;
        $this -> rootPath = realpath($root);
        $this -> setUri($uri);
        $this -> setCurrentDirectory();
        self::$inst = $this;
        // $this -> setCurrentData();
    }

    public function setRoot($root)
    {
        $this -> root = $root;
        return $this;
    }

    public function setUri($uri)
    {
        // @todo write checkUriFormat method with DIRECTORY_SEPARATOR and use here
        $this -> uri = $uri;
        return $this;
    }

    public function getRoot()
    {
        return $this -> root;
    }

    public function open($dir = "")
    {
        $this -> setUri($dir);
        $this -> setCurrentDirectory();
    }

    public function root($dir = "")
    {
        $this -> open("");
    }

    public function save($file, $uri = false, $data = false)
    {
        if ($uri) {
            $uriw = str_replace("/", DIRECTORY_SEPARATOR, $uri);
            $this->add($uriw, true);
            $this->open($uri);
        }
        if ($data) {
            $this->fill($data, $file);
        } else {
            $this->add($file);
        }
        $this -> setCurrentDirectory();
    }

    public function read($file, $uri = false)
    {
        if ($uri) {
            $uriw = str_replace("/", DIRECTORY_SEPARATOR, $uri);
            $this->add($uriw, true);
            $this->open($uri);
        }
        return $this->getData($file);
    }

    public static function pwd()
    {
        return self::$inst->getCurrentDirectory();
    }

    public static function ls()
    {
        return self::$inst->getCurrData();
    }

    public function move($file, $newFilePath)
    {
        $this->rename(realpath($file), realpath($newFilePath));
        return $this;
    }

    public function fill($data, $fileName)
    {
        file_put_contents($this::pwd() . DIRECTORY_SEPARATOR . $fileName, $data);
        return $this;
    }

    public function append($data, $uri)
    {
        file_put_contents($uri, $data, FILE_APPEND);
        return $this;
    }

    public function getData($data, $uri)
    {
        return file_get_contents($uri);
    }

    public function add($fileName, $forceFolder = false)
    {
        $file = $this -> currentDirectory . DIRECTORY_SEPARATOR . $fileName;
        if (!file_exists($file)) {
            if (str_contains($fileName, '.') && !$forceFolder) {
                touch($file);
            } else {
                mkdir($file, 0777, true);
            }
        }
        $this -> setCurrentData();
    }

    public function remove($fileName)
    {
        $file = $this -> currentDirectory . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($file)) {
            unlink($file);
        }
        if (is_dir($file)) {
            $this->rmdirRecursive($file);
        }
        $this -> setCurrentData();
    }

    public function rename($oldName, $newname)
    {
        $rename = rename(
            $this->currentData[$oldName]['realPath'],
            $this->currentDirectory . DIRECTORY_SEPARATOR . $newname
        );
        $this->setCurrentData();
        return $rename;
    }

    protected function setCurrentDirectory()
    {
        // @todo test this method with non-project-root level dirs and dirs in other locations 
        $uri = str_replace("/", DIRECTORY_SEPARATOR, $this -> uri);
        $this -> currentDirectory = getcwd() . DIRECTORY_SEPARATOR . $this -> root . DIRECTORY_SEPARATOR . $uri;
        // echo $this -> currentDirectory;
        if (!is_dir($this->currentDirectory)) {
            trigger_error("CurrentDirectory is not exists anymore, you were redirected to Root Dir", E_USER_WARNING);
            return $this->open("");
        }
        $this->path = $this->getPath($this->uri);
        $this->setCurrentData();
        return $this;
    }

    protected function getCurrentDirectory()
    {
        return $this -> currentDirectory;
    }

    protected function getPath($uri)
    {
        $steps = explode("/", $this -> uri);
        $crumbs = [];
        $string = "";
        $i = 1;
        foreach ($steps as $step) {
            $string = $string . $step;
            if ($i != count($steps)) {
                $string .= "/";
            }
            $crumbs[$step] = $string;
            $i++;
        }
        return $crumbs;
    }

    protected function setCurrentData()
    {
        if (!is_dir($this -> currentDirectory)) {
            throw new \Exception("currentDirectory property is not directory");
        }
        $this -> currentData = scandir(urldecode($this -> currentDirectory));
        unset($this->currentData[0]);
        unset($this->currentData[1]);
        $newArray = [];
        foreach ($this -> currentData as $item) {
            $data = [];
            $filePath = $this -> currentDirectory . DIRECTORY_SEPARATOR . $item;
            $lastModified = filemtime($filePath);
            $data['lastModified'] = $lastModified;
            $data['realPath'] = $filePath;
            $data['uri'] = $this -> getUriByPath($filePath);
            if (is_dir($filePath)) {
                $data['type'] = "directory";
                $data['parent']  = pathinfo($filePath, PATHINFO_DIRNAME);
            } else {
                $data['type'] = "file";
                $info = pathinfo($filePath, PATHINFO_ALL);
                $data['format'] = mime_content_type($filePath);
                $data['ext'] = $info['extension'];
                $data['parent'] = $info['dirname'];
            }
            // echo $item . "\n";
            $newArray[$item] = $data;
        }
        $this -> currentData = $newArray;
        return $this;
    }

    protected function getCurrData()
    {
        return $this->currentData;
    }

    protected function getUriByPath($path)
    {
        $uri = str_replace($this -> rootPath . "\\", '', $path);
        $uri = str_replace("\\", '/', $uri);
        return $uri;
    }

    protected function rmdirRecursive($dir)
    {
        foreach (scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            if (is_dir("$dir" . DIRECTORY_SEPARATOR . "$file")) {
                $this->rmdirRecursive("$dir" . DIRECTORY_SEPARATOR . "$file");
            } else {
                unlink("$dir" . DIRECTORY_SEPARATOR . "$file");
            }
        }
        rmdir($dir);
    }

    protected function dirTree($root)
    {
        $tree = array();
        foreach (scandir($root) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            if (is_dir("$root" . DIRECTORY_SEPARATOR . "$file")) {
                $tree[$file] = $this->dirTree("$root" . DIRECTORY_SEPARATOR . "$file");
            };
        }
        return $tree;
    }

    protected function dirTreeNew($root)
    {
        $tree = array();
        foreach (scandir($root) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }
            if (is_dir("$root" . DIRECTORY_SEPARATOR . "$file")) {
                $branch['name'] = $file;
                $branch['children'] = $this->dirTreeNew("$root" . DIRECTORY_SEPARATOR . "$file");
                $tree[] = $branch;
            };
        }
        return $tree;
    }

    public function getTree()
    {
        return $this->dirTreeNew($this->rootPath);
    }
}
