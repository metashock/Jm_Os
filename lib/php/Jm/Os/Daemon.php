<?php

abstract class Jm_Os_Daemon
implements Jm_Configurable
{

    /**
     * @var integer
     */
    protected $daemonPid;


    protected $pidfile;


    /**
     *
     */
    public function __construct($configuration = NULL) {
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
                echo 'finished';
                exit(1); // should never happen
            default:
                $this->pidfile->content((string)$pid);
                $this->daemonPid = $pid;
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
        $this->pidfile = new Jm_Os_Pidfile(
            $this->getPidfileLocation()
        );

        $this->pidfile->open();
        if($this->pidfile->lock()) {
            $this->daemonize(); 
        } else {
            $this->log->error('Failed to start Daemon');
        }
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
        $pidfile = new Jm_Os_Pidfile(
            $this->getPidfileLocation()
        );

        $content = $pidfile->open()
          ->content();

        if(!is_numeric($content)) {
            throw new Exception('Bad content in pidfile. Expected a pid');            
        }

        $pid = (integer) $content;

        if(!posix_kill($pid, 0)) {
            $this->log->warning(
                'Daemon not running and therefore not stopped'
            );
        }

        echo 'Sending SIGTERM to ' . $pid, PHP_EOL;

        posix_kill($pid, SIGTERM);

        if(!posix_kill($pid, 0)) {
            $this->log->warning(
                'Daemon still running'
            );
        }
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
        Jm_Util_Checktype::check(array('Jm_Configuration', 'array', 'NULL'),
            $configuration);
        $this->configuration = $configuration;
    }
}

