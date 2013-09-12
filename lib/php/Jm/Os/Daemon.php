<?php

abstract class Phish_Daemon
implements Jm_Configurable
{

    /**
     * @var integer
     */
    protected $daemonPid;

    /**
     *
     */
    public function __construct($configuration) {
        $this->configure($configuration);
    }


    /**
     * The function where you should put in the daemon code
     */
    abstract protected function daemon();


    /**
     *
     */
    protected function daemonize() {
        // fork the current process
        $pid = pcntl_fork();
        switch($pid) {
            case -1 : 
                throw new Exception('Forking failed. Cannot spawn daemon');
            case  0 :
                $this->daemon();
                exit(1); // should never happen
            default:
                $this->daemonPid = $pid;
                $this->console->write('Spawned daemon (', 'green');
                $this->console->write($pid, 'green,bold');
                $this->console->writeln(')', 'green');
                return $this;
        }        
    }


    /**
     * Forks the master daemon and returns
     * 
     * @throws Exception if the pidfile could not created or
     * opened for reading / writing
     */
    public function start($exclusive = TRUE) {
        $pidfile = new Jm_Os_Pidfile(
            $this->getPidfileLocation(), 'phish'
        );
    }


    /**
     * Restarts the daemon
     */
    public function restart() {
        $this->stop();
        $this->start();
    }


    /**
     *
     */
    public function stop() {
        if(!file_exists($this->getPidfileLocation())) {
            $this->console->writeln(
                'pidfile \'' .
                $this->getPidfileLocation() .
                '\' not found',
            'yellow');
            return;
        }

        if(!is_readable($this->getPidfileLocation())) {
            $this->console->writeln(
                'pidfile \'' .
                $this->getPidfileLocation() .
                '\' not readable',
            'yellow');
            return;
        }

        // the trim() is because if someone has editied the pidfile
        // with a text editor
        $pid = trim(file_get_contents($this->getPidfileLocation()));

        if(!is_numeric($pid)) {
            $this->console->writeln(
                'pidfile \'' .
                $this->getPidfileLocation() .
                '\' contains invalid content. Expect is a pid',
            'yellow');
            return; 
        }

        $pid = (integer) $pid;
        
        // check if the process is still running
        $ret = posix_kill($pid, 0);
        if($ret === FALSE) {
             $this->console->writeln(
                'The process (' . $pid .
                ') isn\'t running and therefore not stopping it',
            'yellow');
            return;            
        }

        $this->console->write('Stopping daemon (', 'yellow');
        $this->console->write($pid, 'yellow,bold');
        $this->console->writeln(') ', 'yellow');

        $this->console->writeln('Sending SIGTERM to ' . $pid);
        posix_kill($pid, SIGTERM);
        $this->console->write('Waiting for process to exit ');

        $try = 0;
        $maxtries = 10;
        while($try < $maxtries) {
            $this->console()->write('.');
            $ret = posix_kill($pid, 0);
            if($ret === FALSE) {
                $this->console()->writeln(' [OK]', 'green');
                break; 
            }
            $try++;
            sleep(1);
        }

        if($try === $maxtries) {
            $this->console->writeln('');
            $this->console->write('Sending');
            $this->console->writeln(' SIGKILL', 'red');
            posix_kill($pid, SIGKILL);
        } 
            
        unlink($this->getPidfileLocation());
    }


    /**
     * Gets the pidfile location
     */
    public function pidfileLocation() {
        return sys_get_temp_dir();
    }



    /**
     * @param Jm_Configuration $configuration
     +
     * @return Phish_Daemon
     */
    public function configure($configuration) {
        Jm_Util_Checktype::check(array('Jm_Configuration', 'array'),
            $configuration);
        $this->configuration = $configuration;
    }
}

