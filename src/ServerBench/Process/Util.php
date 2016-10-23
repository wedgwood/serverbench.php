<?php
/**
 * Util for process control
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Process;

class Util
{
    public static function setTitle($title)
    {
        $ret = true;

        if (function_exists('cli_set_process_title') && PHP_OS != 'Darwin') {
            cli_set_process_title($title);
        } elseif (function_exists('setproctitle')) {
            setproctitle($title);
        } else {
            $ret = false;
        }

        return $ret;
    }

    public static function daemon($nochdir = true, $noclose = true)
    {
        umask(0);

        $pid = pcntl_fork();

        if ($pid > 0) {
            exit();
        } elseif ($pid < 0) {
            return false;
        } else {
            // nothing to do ...
        }

        $pid = pcntl_fork();

        if ($pid > 0) {
            exit();
        } elseif ($pid < 0) {
            return false;
        } else {
            // nothing to do ...
        }

        $sid = posix_setsid();

        if ($sid < 0) {
            return false;
        }

        if (!$nochdir) {
            chdir('/');
        }

        umask(0);

        if (!$noclose) {
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);
        }

        return true;
    }
}
