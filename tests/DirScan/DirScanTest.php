<?php
/**
 * This file is part of DirScan.
 *
 * @package DirScan
 */

namespace Vincepare\DirScan;

class DirScanTest extends \PHPUnit_Framework_TestCase
{
    public $sandbox;
    
    protected function setUp()
    {
        if (!isset($_ENV['DIRSCAN_TEST_SANDBOX']) || !is_dir($_ENV['DIRSCAN_TEST_SANDBOX'])) {
            throw new \Exception("DIRSCAN_TEST_SANDBOX not set");
        }
        $this->sandbox = $_ENV['DIRSCAN_TEST_SANDBOX'];
    }
    
    /**
     * Check uniquepath on a symlink
     * uniquepah should not resolve the symlink itself
     *
     * @covers \Vincepare\DirScan\DirScan::uniquepath
     */
    public function testUniquePath()
    {
        $symlinkpath = $this->sandbox.'/hulls/ln-dir-absolute-seaplane';
        $uniquepath = DirScan::uniquepath($symlinkpath);
        $this->assertEquals($symlinkpath, $uniquepath);
    }
    
    /**
     * uniquepath on a non existing path
     *
     * @covers \Vincepare\DirScan\DirScan::uniquepath
     */
    public function testUniquePathNotExists()
    {
        $uniquepath = DirScan::uniquepath($this->sandbox.'/the-no-file');
        $this->assertEquals(false, $uniquepath);
        $uniquepath = DirScan::uniquepath($this->sandbox.'/no-directory/no-file');
        $this->assertEquals(false, $uniquepath);
        $uniquepath = DirScan::uniquepath($this->sandbox.'/hulls');
        $this->assertNotEquals(false, $uniquepath);
        $uniquepath = DirScan::uniquepath($this->sandbox.'/wings/seaplane/fuel.txt');
        $this->assertNotEquals(false, $uniquepath);
        $uniquepath = DirScan::uniquepath($this->sandbox.'/ln-file-absolute-fuel');
        $this->assertNotEquals(false, $uniquepath);
        $uniquepath = DirScan::uniquepath($this->sandbox.'/ln-file-relative-fuel');
        $this->assertNotEquals(false, $uniquepath);
    }
    
    /**
     * Directory exploration
     */
    public function testDirectory()
    {
        $settings = array();
        $reporter = new TestReporter();
        $scanner = new DirScan($settings, $reporter);
        $scanner->scan($this->sandbox.'/wheels/car');
        $this->assertEmpty($reporter->errorStack);
        $this->assertCount(8, $reporter->pushStack);
    }
    
    /**
     * Directory exploration with symbolic links
     */
    public function testFullScan()
    {
        $settings = array();
        $reporter = new TestReporter();
        $scanner = new DirScan($settings, $reporter);
        $scanner->scan($this->sandbox);
        
        $pattern = '#'.preg_quote($this->sandbox, '#').'#';
        $observed = array();
        foreach ($reporter->pushStack as $key => $val) {
            $observed[] = preg_replace($pattern, '', $val['uniquepath']);
        }
        sort($observed);
        
        $expected = array(
            '',
            '/hulls',
            '/hulls/boat',
            '/hulls/jet ski',
            '/hulls/ln-dir-absolute-seaplane',
            '/hulls/ln-dir-relative-seaplane',
            '/ln-file-absolute-fuel',
            '/ln-file-relative-fuel',
            '/wheels',
            '/wheels/bike',
            '/wheels/bike/mountain bike',
            '/wheels/bike/sidecar',
            '/wheels/bike/sidecar/ln-dir-loop-absolute',
            '/wheels/bike/sidecar/ln-dir-loop-relative',
            '/wheels/car',
            '/wheels/car/convertible',
            '/wheels/car/convertible/fuel.txt',
            '/wheels/car/off-road',
            '/wheels/car/off-road/buggy',
            '/wheels/car/off-road/buggy/fuel.txt',
            '/wheels/car/off-road/monster truck',
            '/wheels/car/off-road/monster truck/fuel.txt',
            '/wings',
            '/wings/helicopter',
            '/wings/plane',
            '/wings/seaplane',
            '/wings/seaplane/canadair',
            '/wings/seaplane/canadair/fuel.txt',
            '/wings/seaplane/fuel.txt',
        );
        sort($expected);
        
        $this->assertEmpty($reporter->errorStack);
        $this->assertCount(29, $reporter->pushStack);
        $this->assertSame($expected, $observed);
    }
    
    /**
     * Flat option test (do not explore subdirectories)
     */
    public function testFlat()
    {
        $settings = array('flat' => true);
        $reporter = new TestReporter();
        $scanner = new DirScan($settings, $reporter);
        $scanner->scan($this->sandbox.'/hulls');
        $this->assertEmpty($reporter->errorStack);
        $this->assertCount(5, $reporter->pushStack);
    }
    
    /**
     * Symlink exploration
     */
    public function testDeep()
    {
        $settings = array('deep' => true);
        $reporter = new TestReporter();
        $scanner = new DirScan($settings, $reporter);
        $scanner->scan($this->sandbox.'/hulls');
        $this->assertEmpty($reporter->errorStack);
        $this->assertCount(11, $reporter->pushStack);
    }
    
    /**
     * Symlink exploration with trap inside (symlink directory loop) ^^
     */
    public function testDeepDirectoryLoopFull()
    {
        $settings = array('deep' => true);
        $reporter = new TestReporter();
        $scanner = new DirScan($settings, $reporter);
        $scanner->scan($this->sandbox);
        $this->assertCount(2, $reporter->errorStack);
        $this->assertCount(35, $reporter->pushStack);
        $this->assertStringStartsWith('Infinite loop :', $reporter->errorStack[0]['msg']);
        $this->assertStringStartsWith('Infinite loop :', $reporter->errorStack[1]['msg']);
    }
    
    /**
     * Symlink exploration, starting inside symlink target directory
     */
    public function testDeepDirectoryLoopFromChild()
    {
        $settings = array('deep' => true);
        $reporter = new TestReporter();
        $scanner = new DirScan($settings, $reporter);
        $scanner->scan($this->sandbox.'/wheels/bike');
        $this->assertCount(2, $reporter->errorStack);
        $this->assertCount(23, $reporter->pushStack);
        $this->assertStringStartsWith('Infinite loop :', $reporter->errorStack[0]['msg']);
        $this->assertStringStartsWith('Infinite loop :', $reporter->errorStack[1]['msg']);
    }
}
