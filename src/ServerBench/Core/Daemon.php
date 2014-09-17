<?php
/**
 * util for process daemonizing
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Core;

class Daemon
{
    static public function start($nochdir = true, $noclose = true)
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

        if ($nochdir) {
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
