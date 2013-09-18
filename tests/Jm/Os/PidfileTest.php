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


    public function testOpenExclusive1() {
        $pidfile = new Jm_Os_Pidfile($this->filename);
        $this->assertTrue($pidfile->openExclusive());
        var_dump(__METHOD__);
    }


    /**
     * @depends testOpenExclusive1
     * @runInSeparateProcess
     */
    public function testOpenExclusive2() {
        var_dump(__METHOD__);
        sleep(100);
        $pidfile = new Jm_Os_Pidfile($this->filename);
        $this->assertFalse($pidfile->openExclusive());
    }
}
