<?php

class Jm_Os_Pidfile
{

    /**
     *
     */
    protected $location;


    /**
     * @var resource
     */
    protected $fd;


    /**
     *
     */
    public function __construct($location) {
        Jm_Util_Checktype::check('string', $location);
        $this->location = $location;
    }


    /**
     *
     */
    public function openExclusive() {
        $this->fd = @fopen($this->filename, 'w+');
        if(!is_resource($this->pidfile)) {
            throw new Exception(
                'Failed to create pidfile (' . $this->pidfile . ')'
            );
        }

        // if a lock cannot obtained
        if(!flock($this->pidfile, LOCK_EX | LOCK_NB)) {
            $pid = fgets($this->pidfile);
            $this->console()->write('Daemon is already running (', 'red');
            $this->console()->write($phishpid, 'red,bold');
            $this->console()->writeln(')', 'red');
            return;
        }
    }
}

