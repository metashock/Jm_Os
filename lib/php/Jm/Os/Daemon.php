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
 * A daemon is a process that will run 
 *
 * @category  Os
 * @package   Jm\Os
 * @author    Thorsten Heymann <thorsten@metashock.de>
 * @copyright 2013 Thorsten Heymann <thorsten@metashock.de>
 * @license   BSD-3 http://www.opensource.org/licenses/BSD-3-Clause
 * @version   GIT: $$GITVERSION$$a
 * @link      http://www.metashock.de/
 * @since     0.1.0
 */
abstract class Jm_Os_Daemon
implements Jm_Configurable
{

    /**
     * @var integer
     */
    protected $daemonPid;


    /**
     *
     */
    protected $pidfile;


    /**
     * The log dispatcher
     *
     * @var Jm_Log;
     */
    protected $log;


    /**
     * Construct
     *
     * @param Jm_Configuration|array|NULL $configuration optional config
     * @param Jm_Log|NULL                 $log           option log
     *
     * @return Jm_Os_Daemon
     */
    public function __construct($configuration = NULL, Jm_Log $log = NULL) {
        $this->configure($configuration);
        $this->initlog($log);
    }


    /**
     * The function where you should put in the daemon code
     *
     * @return void
     */
    abstract protected function daemon();


    /**
     * Forks and daemonizes the current process. Starts daemon() in
     * the forked process.
     *
     * @return Jm_Os_Daemon
     */
    protected function daemonize() {
        // fork the current process
        $pid = pcntl_fork();
        switch($pid) {
            case -1 :
                // @codeCoverageIgnoreStart
                throw new Exception('Forking failed. Cannot spawn daemon');
            case  0 :
                // detach from terminal by obtaining a new process group
                posix_setsid();
                try {
                    $this->daemon();
                } catch (Exception $e) {
                    $this->log($e->getMessage(), Jm_Log_Level::ERROR);
                    $this->log('Aborting', Jm_Log_Level::ERROR);
                    exit(1);
                }
                exit(0); // should never happen
                // @codeCoverageIgnoreEnd
            default:
                $this->pidfile->content((string)$pid);
                $this->daemonPid = $pid;
                return $this;
        }        
    }


    /**
     * Forks the master daemon and returns
     *
     * @return boolean
     *
     * @throws Exception if the pidfile could not created or
     * opened for reading / writing
     */
    public function start() {
        $this->pidfile = new Jm_Os_Pidfile(
            $this->pidfileLocation()
        );

        if($this->pidfile->open()->lock()) {
            $this->daemonize();
            return TRUE;
        } else {
            $this->log('Failed to start daemon. Daemon is already running');
            return FALSE;
        }
    } 


    /**
     * Restarts the daemon
     *
     * @return Jm_Os_Daemon
     */
    public function restart() {
        $this->stop();
        $this->start();
        return $this;
    }


    /**
     * Stops the damon if it is running. Waits up to 5 seconds
     * for the daemon to exit
     * 
     * @return Jm_Os_Daemon
     */
    public function stop() {
        $pidfile = new Jm_Os_Pidfile(
            $this->pidfileLocation()
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
            return $this;
        }

        $this->log('Sending SIGTERM to ' . $pid, Jm_Log_Level::NOTICE);

        posix_kill($pid, SIGTERM);

        $c = 0;
        while(posix_kill($pid, 0) && $c++ < 5) {
            $this->log('waiting for daemon to exit...');
            sleep(1);
        }

        if($c === 6) {
            throw new Exception('Failed to shutdown daemon');
        }

        return $this;
    }


    /**
     * Checks if the daemon is currently running.
     *
     * @return boolean
     */
    public function running() {
         $pidfile = new Jm_Os_Pidfile(
            $this->pidfileLocation()
        );

        $pidfile->open();
        $content = $pidfile->content();

        if(!is_numeric($content)) {
            throw new Exception('Bad content in pidfile. Expected a pid');
        }

        $pid = (integer) $content;

        if($this->daemonPid) {
            $ret = pcntl_waitpid($pid, $status, WNOHANG);
            if(is_null($ret) || $ret === -1) {
                return FALSE;
            } else {
                return TRUE;
            }
        } else {
            if(!posix_kill($pid, 0)) {
                return FALSE;
            } else {
                return TRUE;
            }
        }
    }


    /**
     * Gets the pidfile location
     *
     * @return string
     */
    public function pidfileLocation() {
        $name = $this->configuration->get('name');
        return sys_get_temp_dir() . '/' . $name . '.pid';
    }


    /**
     * Implementation of Jm_Configurable::configure()
     *
     * @param Jm_Configuration|NULL|array $configuration The configuration
     *
     * @return Phish_Daemon
     */
    public function configure($configuration = NULL) {
        Jm_Util_Checktype::check(array('Jm_Configuration', 'array', 'NULL'),
            $configuration);
        if(is_null($configuration)) {
            $configuration = new Jm_Configuration_Array();
        } else if ( is_array($configuration)) {
            $configuration = new Jm_Configuration_Array($configuration);
        }
        $this->configuration = $configuration;
        return $this;
    }


    /**
     * Creates the internal log instance (or sets it);
     *
     * @param Jm_Log|NULL $log Optional logger provided via constructor. 
     *                         Overrides config values.
     * 
     * @return Jm_Os_Daemon
     */
    protected function initlog($log = NULL) {
        if(is_null($log)) {
            $this->log = new Jm_Log();
            $this->log->attach(new Jm_Log_ConsoleObserver());
            $logfile = $this->configuration->get('log');
            if(!empty($logfile)) {
                $this->log->attach(new Jm_Log_FileObserver($logfile));
            }
        } else {
            $this->log = $log;
        }
        return $this;
    }


    /**
     * Internal log function
     *
     * @param string  $message The message
     * @param integer $level   The log level
     *
     * @return string
     */
    public function log($message, $level = Jm_Log_Level::DEBUG) {
        $this->log->add($message, array(), $level);
        return $message;
    }



    /**
     * SIGTERM handler. Sets the receivedSigterm flag
     *
     * @param integer $signo SIGNUM of the the signal
     *
     * @return void
     */
    public function signalHandler($signo) {
        switch($signo) {
            case SIGTERM :
                $this->log("Recieved SIGTERM");
                $this->receivedSigterm = TRUE;
                break;
            default :
                $this->log("unknown signal: '$signo'", 'red');
        }
    }
}


