<?php
/**
 * This file is part of DirScan.
 *
 * Displays scanned files path and attributes (sent by DirScan::scan) on standard output
 * @package DirScan
 */

namespace Finalclap\DirScan;

class CliReporter extends Reporter
{
    protected $access; // bool Report node access time (default false)
    protected $htime;  // bool Display human readble date/time beside unix timestamp (default false)
    protected $typeMapping; // array Short name for file types
    
    /**
     * Set report settings
     *
     * @param array $settings List of report settings
     */
    public function __construct($settings)
    {
        $this->access = isset($settings['access']) ? $settings['access'] : false;
        $this->htime  = isset($settings['htime'])  ? $settings['htime'] : false;
        $this->typeMapping = array(
            'dir' => 'd',
            'file' => 'f',
            'link' => 'l'
        );
    }
    
    /**
     * Print report header
     *
     * @param string $target Target directory
     * @param array $settings List of report settings
     * @param array $argv Command line arguments
     */
    public function header($target, $settings, $argv)
    {
        $targetStat = DirScan::stat($target);
        
        echo "time: ".time()."\n";
        echo "date: ".date('r')."\n";
        echo "getenv(TZ): ".getenv('TZ')."\n";
        echo "date_default_timezone_get: ".date_default_timezone_get()."\n";
        echo "php version: ".phpversion()."\n";
        echo "cwd: ".getcwd()."\n";
        echo "target: ".$target." (realpath: ".$targetStat['realpath'].")\n";
        echo "start device: ".$targetStat['dev']."\n";
        echo "settings: ".json_encode($settings)."\n";
        echo "argv: ".json_encode($argv)."\n";
        echo "=====================================\n";
        
        $header = array(
            'Unique path',
            'Type',
            'Size',
        );
        
        $header[] = 'ctime';
        if ($this->htime) {
            $header[] = 'Change time';
        }
        
        $header[] = 'mtime';
        if ($this->htime) {
            $header[] = 'Modify time';
        }
        
        if ($this->access) {
            $header[] = 'atime';
            if ($this->htime) {
                $header[] = 'Access time';
            }
        }
        
        $header[] = 'Extended';
        echo implode("\t", $header)."\n";
    }
    
    /**
     * Print node data
     *
     * @param array $node Data returned by DirScan::stat
     * @param DirScan $scanner DirScan object
     */
    public function push($node, DirScan $scanner)
    {
        $type = isset($this->typeMapping[$node['type']]) ? $this->typeMapping[$node['type']] : $node['type'];
        $perms = substr(sprintf('%o', $node['mode']), -4);
        if ($this->htime) {
            $hctime = date('d/m/Y H:i:s', $node['ctime']);
            $hmtime = date('d/m/Y H:i:s', $node['mtime']);
            $hatime = date('d/m/Y H:i:s', $node['atime']);
        }
        
        $extended = array();
        if (isset($node['target'])) {
            $extended['target'] = $node['target'];
        }
        $startDevice = $scanner->startDevice;
        if ($node['dev'] != $startDevice) {
            $extended['device'] = $node['dev'];
        }
        
        $row = array(
            $node['uniquepath'],
            $type,
            $node['size'],
        );
        
        $row[] = $node['ctime'];
        if ($this->htime) {
            $row[] = $hctime;
        }
        
        $row[] = $node['mtime'];
        if ($this->htime) {
            $row[] = $hmtime;
        }
        
        if ($this->access) {
            $row[] = $node['atime'];
            if ($this->htime) {
                $row[] = $hatime;
            }
        }
        
        if (!empty($extended)) {
            $row[] = json_encode($extended);
        }
        
        echo implode("\t", $row)."\n";
    }
    
    /**
     * Print error messages on stderr
     *
     * @param string $msg Error message
     * @param int $code Error code
     */
    public function error($msg, $code = null)
    {
        file_put_contents('php://stderr', $msg."\n");
    }
}
