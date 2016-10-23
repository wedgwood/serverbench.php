<?php
/**
 * muliter for multi request handling
 *
 * @author Yuan B.J.
 */

namespace ServerBench\App\Client;

class Multier
{
    public function fetch($clients, $ms = -1)
    {
        $poller = new Poller();
        $poll_ids = [];

        foreach ($clients as $i => $client) {
            $id = $poller->registerReadable($client);
            $poll_ids[$id] = $i;
        }

        $ms_left = $ms;
        $ret = [];

        do {
            $rset = [];
            $wset = [];
            $events = 0;

            if ($ms_left > 0) {
                $tv1_ms = gettimeofday(true) * 1000;
                $events = $poller->poll($rset, $wset, $ms_left);
                $tv2_ms = gettimeofday(true) * 1000;
                $ms_left -= ($tv2_ms - $tv1_ms);

                if ($ms_left < 0) {
                    $ms_left = 0;
                }
            } else {
                $events = $poller->poll($rset, $wset, $ms_left);
            }

            // $errors = $poller->getLastErrors();

            // if (count($errors) > 0) {
                // $errno  = -1;
                // $errstr = [];

                // foreach ($errors as $error) {
                    // $errstr[] = $error;
                // }

                // $this->setErr_($errno, implode(',', $errstr));
                // $ret = false;
            // }

            if ($events > 0) {
                foreach ($rset as $id) {
                    $i = $poll_ids[$id];
                    $msg = $clients[$i]->recv();

                    if ($msg !== false && !$client->errno()) {
                        $ret[$i] = $msg;
                    }

                    $poller->unregister($client);
                }
            }
        } while ($poller->count() && $ms_left > 0);

        return $ret;
    }
}
