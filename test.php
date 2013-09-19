<?php

require_once 'Jm/Autoloader.php';
Jm_Autoloader::singleton()->prependPath('lib/php');

class Ticker extends Jm_Os_Daemon
{
    public function daemon() {
        while(TRUE) {
            echo date('c') . PHP_EOL;
            sleep(1);
        }
    }

    public function getPidfileLocation() {
        return sys_get_temp_dir() . '/ticker.pid';
    }
}

$t = new Ticker();
$t->start();
sleep(1);
$t->stop();

