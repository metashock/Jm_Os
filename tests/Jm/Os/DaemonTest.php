<?php

class Jm_Os_DaemonTest extends PHPUnit_Framework_TestCase
{


    public function testStart() {

        $configuration = new Jm_Configuration_Array(array(
            'name' => 'phpunit_test',
            'log' => 'console'
        ));

        $log = new Jm_Log();
        $log->attach(new Jm_Log_ArrayObserver());

        $daemon = $this->getMockBuilder('Jm_Os_Daemon')
          ->setConstructorArgs(array($configuration, $log))
          ->setMethods(array('daemon'))
          ->getMockForAbstractClass();

        $daemon->expects($this->any())
          ->method('daemon')
          ->will($this->returnCallback(function() {
                file_put_contents('/tmp/output', '');
                file_put_contents('/tmp/output', 'test');
                sleep(5);
            }));


        $this->assertTrue($daemon->start());
        $this->assertTrue($daemon->running());

        // try to start again. Should fail
        $this->assertFalse($daemon->start());

        $daemon->stop();
        $this->assertFalse($daemon->running());

        $this->assertEquals('test', file_get_contents('/tmp/output'));

        

/*
        $daemon->expects($this->any())
          ->method('
          ->will($this->  */
    }

}
