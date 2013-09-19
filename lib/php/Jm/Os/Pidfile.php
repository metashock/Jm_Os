<?php

class Jm_Os_Pidfile
{

    /**
     *
     */
    protected $filename;


    /**
     * File descriptor
     *
     * @var resource
     */
    protected $fd;


    /**
     * Flag that indicates whether the file is currently locked or not
     *
     * @var boolean
     */ 
    protected $locked;


    /**
     *
     */
    public function __construct($filename) {
        Jm_Util_Checktype::check('string', $filename);
        $this->filename = $filename;
        $this->locked = FALSE;
    }


    public function lock() {
        // Try to obtain an exclusive lock for the file
        // the function will not block because of LOCK_NB
        // (This will not work on windows)
        if(!flock($this->fd, LOCK_EX | LOCK_NB)) {
            fclose($this->fd);
            return FALSE;
        }
        $this->locked = TRUE;
        return TRUE;
    }


    /**
     * Releases the lock. If no lock has been obtained before
     * the method has no effect.
     *
     * @return Jm_Os_Pidfile
     */
    public function unlock() {
        if($this->locked) {
            flock($this->fd, LOCK_UN);
            $this->locked = FALSE;
        }
        return $this;
    }


    /**
     * Opens the file.
     *
     * @return boolean
     */
    public function open() {
        $this->fd = @fopen($this->filename, 'r+');
        if(!is_resource($this->fd)) {
            throw new Exception(
                'Failed to create pidfile (' . $this->filename . ')'
            );
        }
        return $this;
    }


    /**
     * Releases the lock and closes the file handle
     *
     * @return Jm_Os_Pidfile
     */
    public function close() {
        // The automatic unlocking when the file's resource handle 
        // is closed was removed in PHP 5.3.2. Unlocking now always 
        // has to be done manually.
        $this->unlock();
        fclose($this->fd);
        return $this;
    }

    /**
     * Returns the file name
     *
     * @return string
     */
    public function filename() {
        return $this->filename;
    }


    /**
     * Get's or sets the content of the file
     *
     * @return string|Jm_Os_Pidfile
     *
     * @throws InvalidArgumentException if $content is not a string
     * or NULL (or has been omitted)
     * @throws Exception if writing is desired but the 
     * file was not locked before
     */
    public function content($content = NULL) {
        Jm_Util_Checktype::check(array('string', 'NULL'), $content);
        if(is_null($content)) {
            fseek($this->fd, 0, SEEK_SET);
            return stream_get_contents($this->fd);
        } else {
            if(!$this->locked) {
                throw new Exception(
                    'Cannot write to pidfile without locking it'
                );
            }
            ftruncate($this->fd, 0);
            fseek($this->fd, 0);
            fwrite($this->fd, $content);
            fflush($this->fd);
            return $this;
        }
    }
}

