<?php
/**
 * helpers of serverbench, mainly log helpers now
 *
 * @author Yuan B.J.
 */

namespace ServerBench;

function syslog($level, $message, $context = [])
{
    Logger\SysLogger::getInstance()->log($level, $message, $context);
}

function syslog_error($message, $context = [])
{
    Logger\SysLogger::getInstance()->log('error', $message, $context);
}

function syslog_info($message, $context = [])
{
    Logger\SysLogger::getInstance()->log('info', $message, $context);
}

function syslog_debug($message, $context = [])
{
    Logger\SysLogger::getInstance()->log('debug', $message, $context);
}
