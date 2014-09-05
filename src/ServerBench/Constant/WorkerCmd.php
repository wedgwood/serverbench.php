<?php
/**
 * const worker cmd, used for internal message exchanging
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

namespace ServerBench\Constant;

class WorkerCmd
{
    const READY     = 0x01;
    const HEARTBEAT = 0x02;
}
