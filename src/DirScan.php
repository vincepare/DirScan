<?php
/**
 * This file is part of DirScan.
 *
 * File system scanning class
 * @package DirScan
 */

namespace Finalclap\DirScan;

class DirScan
{
    // Error codes
    const ERR_DIR_LOOP = 1000;
    const ERR_DIR_READ = 1001;
    const ERR_READLINK = 1002;
    
    protected $deep;        // bool Explore symlinks if true (default false)
    protected $flat;        // bool Do not explore subdirectories if true (default false)
    protected $reporter;    // Reporter Reporter object to handle DirScan output
    protected $sameDevice;  // bool Scan only directories on the same device as the start directory (default false)
    protected $startDevice; // int Device ID of the start directory (provided by lstat)
    
    /**
     * Create a new DirScan object
     *
     * @param array $settings List of scan settings
     * @param Reporter $callback Called for every scanned file system node
     */
    public function __construct($settings, Reporter $reporter)
    {
        $this->reporter = $reporter;
        $this->deep = isset($settings['deep']) ? $settings['deep'] : false;
        $this->flat = isset($settings['flat']) ? $settings['flat'] : false;
        $this->sameDevice = isset($settings['same-device']) ? $settings['same-device'] : false;
    }
    
    /**
     * Scan $path and its content if $path is a directory
     *
     * @param string $directory Path of a node to scan
     * @param array $pathstack List of parents directories, used for recursive directory loop detection
     */
    public function scan($path, $pathstack = array())
    {
        $stat = self::stat($path);
        call_user_func(array($this->reporter, 'push'), $stat, $this);
        
        // Saving start directory device
        if (empty($pathstack)) {
            $this->startDevice = $stat['dev'];
        }
        
        // Exit now if path is not a directory or flat is enabled (except for the 1st directory (target))
        if (!is_dir($path) || ($this->flat && !empty($pathstack))) {
            return;
        }
        
        // Skip symlink if deep is disabled
        if (!$this->deep && $stat['type'] === 'link') {
            return;
        }
        
        // Do not explore direcotry content if it's not on the start device
        if ($this->sameDevice && $stat['dev'] != $this->startDevice) {
            return;
        }
        
        // Directory loop prevention
        if (in_array($stat['realpath'], $pathstack)) {
            $msg = "Infinite loop : ".$path." (".$stat['uniquepath'].")";
            call_user_func(array($this->reporter, 'error'), $msg, self::ERR_DIR_LOOP);
            return;
        }
        
        // Add current directory to path stack
        $pathstack[] = $stat['realpath'];
        
        // Directory content scan
        $childs = @opendir($path);
        if ($childs === false) {
            $error = error_get_last();
            call_user_func(array($this->reporter, 'error'), $error['message'], self::ERR_DIR_READ);
            return;
        }
        while ($child = readdir($childs)) {
            // Skip . & ..
            if ($child === '.' || $child === '..') {
                continue;
            }
            
            $childPath = rtrim($path, '/').DIRECTORY_SEPARATOR.$child;
            $this->scan($childPath, $pathstack);
        }
        closedir($childs);
    }
    
    /**
     * Get file system node metadata
     *
     * @param string $path File system path of the node
     * @return array
     */
    public static function stat($path)
    {
        $lstat = lstat($path);
        if ($lstat === false) {
            return false;
        }
        $type  = filetype($path);
        $owner = function_exists('posix_getpwuid') ? posix_getpwuid($lstat['uid']) : null;
        $group = function_exists('posix_getgrgid') ? posix_getgrgid($lstat['gid']) : null;
        $uniquepath = self::uniquepath($path);
        
        clearstatcache(true);
        $result = array(
            'path' => $path,
            'uniquepath' => $uniquepath,
            'realpath' => realpath($path),
            'type'  => $type,
            'ino'   => $lstat['ino'],
            'dev'   => $lstat['dev'],
            'size'  => $lstat['size'],
            'atime' => $lstat['atime'],
            'mtime' => $lstat['mtime'],
            'ctime' => $lstat['ctime'],
            'mode'  => $lstat['mode'],
            'uid'   => $lstat['uid'],
            'gid'   => $lstat['gid'],
            'owner' => $owner['name'],
            'group' => $group['name'],
        );
        
        if ($type === 'link') {
            $target = @readlink($path);
            if ($target === false) {
                $error = error_get_last();
                $msg = $error['message']." (".$uniquepath.")";
                call_user_func(array($this->reporter, 'error'), $msg, self::ERR_READLINK);
                return;
            } else {
                $result['target'] = readlink($path);
            }
        }
        
        return $result;
    }
    
    /**
     * Get a unique path to directory, file and symbolic links
     * Returns false when path does not exists
     *
     * @param string $path
     * @return string|false
     */
    public static function uniquepath($path)
    {
        if (!file_exists($path)) {
            return false;
        }
        $info = pathinfo($path);
        clearstatcache(true);
        $parent = realpath($info['dirname']);
        $uniquepath = rtrim($parent, '/').DIRECTORY_SEPARATOR.$info['basename'];
        return $uniquepath;
    }
    
    /**
     * This is used to fetch readonly variables
     *
     * @param string $attr Attribute name to read
     * @return mixed Attribute value (or null if attribute does not exists)
     */
    public function __get($attr)
    {
        return ($attr != "instance" && isset($this->$attr)) ? $this->$attr : null;
    }
}
