<?php

class Jm_Os_Pidfile
{

    /**
     *
     */
    protected $filename;


    /**
     * @var resource
     */
    protected $fd;


    /**
     *
     */
    public function __construct($filename) {
        Jm_Util_Checktype::check('string', $filename);
        $this->filename = $filename;
    }


    /**
     *
     */
    public function openExclusive() {
        $this->fd = @fopen($this->filename, 'w+');
        if(!is_resource($this->fd)) {
            throw new Exception(
                'Failed to create pidfile (' . $this->filename . ')'
            );
        }

        // Try to obtain an exclusive lock for the file
        // the function will not block because of LOCK_NB
        if(!flock($this->fd, LOCK_EX | LOCK_NB)) {
            $this->pid = fgets($this->fd);
            return FALSE;
        }
        return TRUE;
    }


    /**
     *
     */
    public function close() {
        fclose($this->fd);
    }


    /**
     *
     */
    public function content() {
        return $this->pid;
    }
}

