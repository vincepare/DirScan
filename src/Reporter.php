<?php
/**
 * This file is part of DirScan.
 *
 * @package DirScan
 */

namespace Vincepare\DirScan;

abstract class Reporter
{
    /**
     * DirScan entry handler
     *
     * @param array $node Data returned by DirScan::stat
     * @param DirScan $scanner DirScan object
     */
    public function push($node, DirScan $scanner)
    {
    }
    
    /**
     * DirScan error handler
     *
     * @param string $msg Error message
     * @param int $code Error code
     */
    public function error($msg, $code = null)
    {
        trigger_error($msg, E_USER_WARNING);
    }
}
