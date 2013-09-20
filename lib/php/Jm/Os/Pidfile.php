<?php
/**
 * Jm_Os
 *
 * Copyright (c) 2013, Thorsten Heymann <thorsten@metashock.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name Thorsten Heymann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * PHP Version >= 5.3.0
 *
 * @category  Os
 * @package   Jm\Os
 * @author    Thorsten Heymann <thorsten@metashock.de>
 * @copyright 2013 Thorsten Heymann <thorsten@metashock.de>
 * @license   BSD-3 http://www.opensource.org/licenses/BSD-3-Clause
 * @version   GIT: $$GITVERSION$$
 * @link      http://www.metashock.de/
 * @since     0.1.0
 */
/**
 * A pidfile is a file based mutex used to make sure that a system daemon
 * will run only once at a time. Currently this works on Linux/UNIX only
 *
 * @category  Os
 * @package   Jm\Os
 * @author    Thorsten Heymann <thorsten@metashock.de>
 * @copyright 2013 Thorsten Heymann <thorsten@metashock.de>
 * @license   BSD-3 http://www.opensource.org/licenses/BSD-3-Clause
 * @version   GIT: $$GITVERSION$$
 * @link      http://www.metashock.de/
 * @since     0.1.0
 */
class Jm_Os_Pidfile
{

    /**
     * The file name
     *
     * @var string
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
     * Constructor
     *
     * @param string $filename The file name
     */
    public function __construct($filename) {
        Jm_Util_Checktype::check('string', $filename);
        $this->filename = $filename;
        $this->locked = FALSE;
    }


    /**
     * Trys to obtain an exclusive lock using flock(). The method will 
     * not block if it failed to obtain the log.
     *
     * @return boolean
     */
    public function lock() {
        // Try to obtain an exclusive lock for the file
        // the function will not block because of LOCK_NB
        // (This will not work on windows)
        if(!flock($this->fd, LOCK_EX | LOCK_NB)) {
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
        $this->fd = fopen($this->filename, 'a+');
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
     * @param string|NULL $content Optional. If set the file would being
     *                             overwritten.
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

