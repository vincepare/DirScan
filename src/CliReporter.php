<?php
/**
 * This file is part of DirScan.
 *
 * Displays scanned files path and attributes (sent by DirScan::scan) on standard output
 * @package DirScan
 */

namespace Vincepare\DirScan;

class CliReporter extends Reporter
{
    protected $access; // bool Report node access time (default false)
    protected $htime;  // bool Print human readble date/time beside unix timestamp (default false)
    protected $perms;  // bool Report file permissions (default false)
    protected $format; // string Custom output format
    protected $settings; // array Raw report settings
    
    /**
     * Short name for file types
     */
    public static $typeMapping = array(
        'dir' => 'd',
        'file' => 'f',
        'link' => 'l',
    );
    
    /**
     * Properties mapping for custom format
     */
    public static $propMapping = array(
        '%u' => 'Unique path',
        '%t' => 'Type',
        '%s' => 'Size',
        '%c' => 'ctime',
        '%C' => 'Change time',
        '%m' => 'mtime',
        '%M' => 'Modify time',
        '%a' => 'atime',
        '%A' => 'Access time',
        '%p' => 'Permissions',
        '%o' => 'UID',
        '%O' => 'Owner',
        '%g' => 'GID',
        '%G' => 'Group',
        '%i' => 'Inode',
        '%e' => 'Extended',
    );
    
    /**
     * Set report settings
     *
     * @param array $settings List of report settings
     */
    public function __construct($settings)
    {
        $this->access = isset($settings['access']) ? $settings['access'] : false;
        $this->htime  = isset($settings['htime'])  ? $settings['htime'] : false;
        $this->perms  = isset($settings['perms'])  ? $settings['perms'] : false;
        $this->format = isset($settings['format']) ? $settings['format'] : null;
        $this->settings = $settings;
        
        // Use format for full report
        if ($settings['full'] === true) {
            $this->format = implode(' ', array_keys(self::$propMapping));
        }
    }
    
    /**
     * Print report header
     *
     * @param array $targets List of target directories
     * @param array $argv Command line arguments
     */
    public function header($targets, $argv)
    {
        $header = !empty($this->format) ? $this->getRowFormatHeader($this->format) : $this->getRowHeader();
        echo "date: ".date('r (U)')."\n";
        echo "getenv(TZ): ".getenv('TZ')."\n";
        echo "date_default_timezone_get: ".date_default_timezone_get()."\n";
        if (defined('DIRSCAN_VERSION')) {
            echo "dirscan version: ".DIRSCAN_VERSION."\n";
        }
        echo "php version: ".phpversion()."\n";
        echo "uname: ".php_uname()."\n";
        echo "cwd: ".getcwd()."\n";
        echo "settings: ".json_encode($this->settings)."\n";
        echo "argv: ".json_encode($argv)."\n";
        echo "target".(count($targets) > 1 ? "s" : "").": "."\n";
        foreach ($targets as $key => $target) {
            $targetStat = lstat($target);
            $targetStat['realpath'] = realpath($target);
            echo sprintf(" - %s (realpath: %s, device: %s)\n", $target, $targetStat['realpath'], $targetStat['dev']);
        }
        echo "=====================================\n";
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
        $meta = $this->getMetadata($node, $scanner);
        $row = !empty($this->format) ? $this->getRowFormat($this->format, $node, $meta) : $this->getRow($node, $meta);
        echo implode("\t", $row)."\n";
    }
    
    /**
     * Return metadata from a node
     *
     * @param array $node Data returned by DirScan::stat
     * @param DirScan $scanner DirScan object
     * @return array
     */
    protected function getMetadata($node, DirScan $scanner)
    {
        $meta = array(
            'type' => isset(self::$typeMapping[$node['type']]) ? self::$typeMapping[$node['type']] : $node['type'],
            'perms' => substr(sprintf('%o', $node['mode']), -4),
            'hctime' => date('d/m/Y H:i:s', $node['ctime']),
            'hmtime' => date('d/m/Y H:i:s', $node['mtime']),
            'hatime' => date('d/m/Y H:i:s', $node['atime']),
            'extended' => array(),
        );
        
        if (isset($node['target'])) {
            $meta['extended']['target'] = $node['target'];
        }
        
        if ($node['dev'] != $scanner->startDevice) {
            $meta['extended']['device'] = $node['dev'];
        }
        
        return $meta;
    }
    
    /**
     * Return the header row array
     *
     * @return array
     */
    protected function getRowHeader()
    {
        $header = array(
            'Unique path',
            'Type',
            'Size',
        );
        
        if ($this->perms) {
            $header[] = 'Permissions';
        }
        
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
        return $header;
    }
    
    /**
     * Return the header row array, using custom format
     *
     * @param string $format A format string used as row template
     * @return array
     */
    protected function getRowFormatHeader($format)
    {
        $header = preg_split('# #', $format);
        foreach ($header as $key => $val) {
            $header[$key] = strtr($val, self::$propMapping);
        }
        return $header;
    }
    
    /**
     * Return the row array for a node
     *
     * @param array $node Data returned by DirScan::stat
     * @param array $meta Data returned by self::getMetadata
     * @return array
     */
    protected function getRow($node, $meta)
    {
        $row = array(
            self::getPath($node),
            $meta['type'],
            $node['size'],
        );
        
        if ($this->perms) {
            $row[] = $meta['perms'];
        }
        
        $row[] = $node['ctime'];
        if ($this->htime) {
            $row[] = $meta['hctime'];
        }
        
        $row[] = $node['mtime'];
        if ($this->htime) {
            $row[] = $meta['hmtime'];
        }
        
        if ($this->access) {
            $row[] = $node['atime'];
            if ($this->htime) {
                $row[] = $meta['hatime'];
            }
        }
        
        if (!empty($meta['extended'])) {
            $row[] = json_encode($meta['extended']);
        }
        
        return $row;
    }
    
    /**
     * Return the row array for a node, using custom format
     *
     * @param string $format A format string used as row template
     * @param array $node Data returned by DirScan::stat
     * @param array $meta Data returned by self::getMetadata
     * @return array
     */
    protected function getRowFormat($format, $node, $meta)
    {
        $statMapping = array(
            '%u' => self::getPath($node),
            '%t' => $meta['type'],
            '%s' => $node['size'],
            '%c' => $node['ctime'],
            '%C' => $meta['hctime'],
            '%m' => $node['mtime'],
            '%M' => $meta['hmtime'],
            '%a' => $node['atime'],
            '%A' => $meta['hatime'],
            '%p' => $meta['perms'],
            '%o' => $node['uid'],
            '%O' => $node['owner'],
            '%g' => $node['gid'],
            '%G' => $node['group'],
            '%i' => $node['ino'],
            '%e' => empty($meta['extended']) ? '' : json_encode($meta['extended']),
        );
        
        $row = preg_split('# #', $format);
        foreach ($row as $key => $val) {
            $row[$key] = strtr($val, $statMapping);
        }
        
        // Trim array
        $keys = array_reverse(array_keys($row));
        foreach ($keys as $key) {
            if (empty($row[$key])) {
                unset($row[$key]);
                continue;
            }
            break;
        }
        
        return $row;
    }
    
    /**
     * Returns either node uniquepath, or path as failover if uniquepath is not available
     *
     * @param array $node Data returned by DirScan::stat
     * @return string
     */
    protected static function getPath($node)
    {
        return !empty($node['uniquepath']) ? $node['uniquepath'] : $node['path'].' *UNIQUEPATH_FAILOVER';
    }
    
    /**
     * Print error messages to stderr
     *
     * @param string $msg Error message
     * @param int $code Error code
     */
    public function error($msg, $code = null)
    {
        fwrite(STDERR, "[dirscan] ".$msg.PHP_EOL);
    }
}
