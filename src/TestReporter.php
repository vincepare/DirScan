<?php
/**
 * This file is part of DirScan.
 *
 * Stores raw callback arguments, used for unit testing
 * @package DirScan
 */

namespace Finalclap\DirScan;

class TestReporter extends Reporter
{
    public $pushStack = array();
    public $errorStack = array();
    
    /**
     * Print node data
     *
     * @param array $node Data returned by DirScan::stat
     * @param DirScan $scanner DirScan object
     */
    public function push($node, DirScan $scanner)
    {
        $this->pushStack[] = $node;
    }
    
    /**
     * Print error messages on stderr
     *
     * @param string $msg Error message
     * @param int $code Error code
     */
    public function error($msg, $code = null)
    {
        $this->errorStack[] = array('msg' => $msg, $code => $code);
    }
}
