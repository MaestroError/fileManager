<?php
/**
 * Scans folder and gets information about files
 * Helps to use basic actions on file (CRUD)
 *
 * @author    MaestroError <revaz.gh@gmail.com>
 * @copyright 2021 Revaz Ghambarashvili
 * @license   https://github.com/MaestroError/fileManager/blob/maestro/LICENSE MIT License
 */

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
        $this->root = $root;
        $this->rootPath = realpath($root);
        $this->setUri($uri);
        $this->setCurrentDirectory();
        self::$inst = $this;
        // $this->setCurrentData();
    }


    public function setRoot(string $root)
    {
        $this->root = $root;
        return $this;
    }

    public function setUri(string $uri)
    {
        // @todo write checkUriFormat method with DIRECTORY_SEPARATOR and use here
        $this->uri = $uri;
        return $this;
    }

    public function getRoot()
    {
        return $this->root;
    }

    public function open($dir = "")
    {
        $this->setUri($dir);
        $this->setCurrentDirectory();
    }

    public function root($dir = "")
    {
        $this->open("");
    }

    public function save(string $file, bool|string $uri = false, bool|string $data = false)
    {
        // If uri is set open the folder, create if not exists
        if ($uri) {
            $uriw = str_replace("/", DIRECTORY_SEPARATOR, $uri);
            $this->add($uriw, true);
            $this->open($uri);
        }
        // If data is set write in file, if not just create it
        if ($data) {
            $this->fill($data, $file);
        } else {
            $this->add($file);
        }
        // Update current directory and data
        $this->setCurrentDirectory();
    }

    public function read(string $file, string|bool $uri = false)
    {
        if ($uri) {
            $uriw = str_replace("/", DIRECTORY_SEPARATOR, $uri);
            $this->add($uriw, true);
            $this->open($uri);
        }
        return $this->getData($file);
    }

    // Public statics START

    
    /**
     * Initializes a new instance of the FileManager class with a specified root directory.
     * Sets current location at root
     *  
     * @param string $root The root directory for the file manager. Default value is "files".
     * @return FileManager A new instance of the FileManager class with the specified root directory.
     */
    public static function init(string $root = "files") {
        return new self("", $root);
    }

    /**
     * Creates a new instance of the FileManager class with the specified root and URI.
     *
     * @param string $root The root directory for the file manager. Default value is "files".
     * @param string $uri The URI of the current directory. Default value is an empty string.
     * @return FileManager A new instance of the FileManager class with the specified root and URI.
     */
    public static function folder(string $root = "files", string $uri = "") {
        return new self($uri, $root);
    }

    public static function pwd()
    {
        return self::$inst->getCurrentDirectory();
    }

    public static function ls()
    {
        return self::$inst->getCurrData();
    }

    public static function files() {
        return self::$inst->getAllFilesInCurrentDir();
    }

    public static function filesAll() {
        return self::$inst->getAllFilesRecursively();
    }

    public static function back() {
        return self::$inst->goUpOneDirectory();
    }

    public static function toRoot() {
        return self::$inst->open("");
    }

    // Public statics END

    public function move(string $file, string $newFilePath)
    {
        $this->rename(realpath($file), realpath($newFilePath));
        return $this;
    }

    public function fill(string $data, string $fileName)
    {
        file_put_contents($this::pwd() . DIRECTORY_SEPARATOR . $fileName, $data);
        return $this;
    }

    public function append(string $data, string $fileName)
    {
        file_put_contents($fileName, $data, FILE_APPEND);
        return $this;
    }

    public function getData(string $data, string $fileName)
    {
        return file_get_contents($fileName);
    }

    public function add(string $fileName, bool $forceFolder = false)
    {
        $file = $this->currentDirectory . DIRECTORY_SEPARATOR . $fileName;
        if (!file_exists($file)) {
            if (str_contains($fileName, '.') && !$forceFolder) {
                touch($file);
            } else {
                mkdir($file, 0777, true);
            }
        }
        $this->setCurrentData();
    }

    public function remove(string $fileName)
    {
        $file = $this->currentDirectory . DIRECTORY_SEPARATOR . $fileName;
        if (is_file($file)) {
            unlink($file);
        }
        if (is_dir($file)) {
            $this->rmdirRecursive($file);
        }
        $this->setCurrentData();
    }

    public function rename(string $oldName, string $newname)
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
        $uri = str_replace("/", DIRECTORY_SEPARATOR, $this->uri);
        $this->currentDirectory = getcwd() . DIRECTORY_SEPARATOR . $this->root . DIRECTORY_SEPARATOR . $uri;

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
        return $this->currentDirectory;
    }

    protected function getPath(string $uri)
    {
        $steps = explode("/", $this->uri);
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
        if (!is_dir($this->currentDirectory)) {
            throw new \Exception("currentDirectory property is not directory");
        }
        $this->currentData = scandir(urldecode($this->currentDirectory));
        unset($this->currentData[0]);
        unset($this->currentData[1]);
        $newArray = [];
        foreach ($this->currentData as $item) {
            $data = [];
            $filePath = $this->currentDirectory . DIRECTORY_SEPARATOR . $item;
            $lastModified = filemtime($filePath);
            $data['lastModified'] = $lastModified;
            $data['realPath'] = $filePath;
            $data['uri'] = $this->getUriByPath($filePath);
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
        $this->currentData = $newArray;
        return $this;
    }

    protected function getCurrData()
    {
        return $this->currentData;
    }

    protected function getUriByPath(string $filePath)
    {
        // @todo test this method and fix
        $uri = str_replace($this->rootPath . "\\", '', $filePath);
        $uri = str_replace("\\", '/', $uri);
        return $uri;
    }

    protected function rmdirRecursive(string $dir)
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

    protected function dirTree(string $root)
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

    protected function dirTreeNew(string $root)
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

    /**
     * Get all files in the current directory.
     *
     * @return array
     */
    protected function getAllFilesInCurrentDir(): array
    {
        $allFiles = [];
        foreach ($this->currentData as $key => $value) {
            if ($value['type'] === 'file') {
                $allFiles[] = $value['realPath'];
            }
        }
        return $allFiles;
    }
    

    /**
     * Helper function to get files recursively.
     *
     * @param string $dir The directory to start scanning from.
     * @return array
     */
    protected function getFilesRecursiveHelper(string $dir): array
    {
        $files = [];
        foreach (scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) continue;
            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                $files = array_merge($files, $this->getFilesRecursiveHelper($filePath));
            } else {
                $files[] = $filePath;
            }
        }
        return $files;
    }

    /**
     * Get all files in the current directory and its child directories.
     *
     * @return array
     */
    protected function getAllFilesRecursively(): array
    {
        return $this->getFilesRecursiveHelper($this->currentDirectory);
    }

    /**
     * Go to the parent directory (like "cd ..")
     */
    protected function goUpOneDirectory()
    {
        if ($this->uri === "" || $this->uri === "/") {
            // Already at the root, can't go up further.
            return;
        }

        // Remove trailing slash if it exists.
        $this->uri = rtrim($this->uri, '/');

        // Get parent directory path.
        $parentPath = dirname($this->uri);

        // Update the URI and current directory.
        $this->setUri($parentPath);
        $this->setCurrentDirectory();
    }
}
