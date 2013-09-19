<?php

class Jm_Os_PidfileTest extends PHPUnit_Framework_TestCase
{

    protected $filename;

    /**
     *
     */
    protected function setUp() {
        $this->filename = sys_get_temp_dir() . '/' . uniqid();
    }


    /**
     *
     */
    protected function tearDown() {
        @unlink($this->filename);
    }


    /**
     * Opens a pidfile exclusive and starts a PHP sub process
     * that tries the same. It is expected that the sub process
     * won't be able to obtain the lock.
     */
    public function testOpen() {
        $pidfile = new Jm_Os_Pidfile($this->filename);
        $this->assertTrue($pidfile->open()->lock());
        $cmd = <<<EOF
require_once "Jm/Autoloader.php";
Jm_Autoloader::singleton()->prependPath("lib/php");
\$pidfile = new Jm_Os_Pidfile("$this->filename");
if(\$pidfile->open()->lock()) {
    echo "TRUE";
} else {
    echo "FALSE";
}
EOF;
        $result = `php -r '$cmd'`;
        $this->assertEquals('FALSE', $result);

        // now release the lock and try the external process again
        $pidfile->close();

        $result = `php -r '$cmd'`;
        $this->assertEquals('TRUE', $result);
    }


    /**
     *
     */
    public function test2(){
        $cmd = <<<EOF
require_once "Jm/Autoloader.php";
Jm_Autoloader::singleton()->prependPath("lib/php");
\$pidfile = new Jm_Os_Pidfile("$this->filename");
if(\$pidfile->open()->lock()) {
    echo "TRUE";
} else {
    echo "FALSE";
}
flush();
// will block
fgets(fopen("php://stdin", 'r'));
EOF;

        $descriptorspec = array(
           0 => array("pipe", "r"),
           1 => array("pipe", "w"),
           2 => array("pipe", "w") 
        );

        $process = proc_open("php -r '$cmd'", $descriptorspec, $pipes);

        if(!is_resource($process)) {
            $this->markTestFailed('failed to spawn sub process');
        }

        $output = fread($pipes[1], 4);

        // not possible for us to obtain the lock again
        $pidfile = new Jm_Os_Pidfile($this->filename);
        $ret = $pidfile->open()->lock();

        // write to stdin in order to terminate the sub process
        fwrite($pipes[0], "1\n");
        // close pipes
        fclose($pipes[1]);
        fclose($pipes[0]);
        // call proc_close to avoid deadlocks
        $return_value = proc_close($process);

        // sub process was able to obtain the lock?
        $this->assertEquals('TRUE', $output);
        // local attempt should fail
        $this->assertFalse($ret);
    }

    
    /**
     * Tests reading and writing of the pidfile
     */
    public function testContent() {
        $pidfile = new Jm_Os_Pidfile($this->filename);
        $this->assertTrue($pidfile->open()->lock());
        
        $this->assertEquals('', $pidfile->content());
        
        $pidfile->content('hello');
        $this->assertEquals('hello', $pidfile->content());

        $pidfile->content('world');
        $this->assertEquals('world', $pidfile->content());
    }


    /**
     * Tests the filename() getter 
     */
    public function testFilename() {
        $pidfile = new Jm_Os_Pidfile($this->filename);
        $this->assertEquals($this->filename, $pidfile->filename());
    }
}
 
