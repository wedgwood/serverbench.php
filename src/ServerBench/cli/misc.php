<?php
/**
 * cli util of serverbench's cli
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Cli;

function stop_server($pid)
{
    echo 'stop ';
    posix_kill($pid, SIGTERM);

    while (posix_kill($pid, 0)) {
        echo '.';
        sleep(1);
    }

    echo "\ndone\n";
}

function reload_server($pid)
{
    echo 'reload ';
    posix_kill($pid, SIGUSR1);
    echo "done\n";
}

function show_status($pid)
{
    \ServerBench\Console\Colorizer::color(
        'green',
        passthru(
            "ps -o pid,ppid,pgid,sess,user,start_time,time,stat,%cpu,rss,vsz,size,%mem,cmd --pid {$pid} --ppid {$pid}"
        )
    );
}

function get_usage()
{
    return <<<EOF
\n
start:  php serverbench.phar -c {path of conf} --app={path of app} --workers={num of workers} -l{address to listen}
stop:   php serverbench.phar -c {path of conf} --stop
reload: php serverbench.phar -c {path of conf} --reload
\n
EOF;
}

function get_logo()
{
    return file_get_contents(dirname(dirname(dirname(__DIR__))) . '/logo.txt');
}

function get_version()
{
    return file_get_contents(dirname(dirname(dirname(__DIR__))) . '/version.txt');
}

function check_requirements(&$not_met = [])
{
    $ret = true;

    foreach (['zmq', 'pcntl', 'posix'] as $ext) {
        if (!extension_loaded($ext)) {
            $not_met[] = $ext;
            $ret = false;
        }
    }

    return $ret;
}

function printf_and_exit()
{
    $args = func_get_args();
    vprintf($args[0], array_slice($args, 1));
    exit();
}
