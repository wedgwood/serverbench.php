<?php
/**
 * confiure center data model
 *
 * @author Yuan B.J.
 * @version 1.0
 * @copyright Yuan B.J., 2014.09.01
 */

class CC
{
    private $dbi_ = NULL;

    public function init($host, $user, $passwd, $dbname, $port = 3306)
    {
        $dbi = mysqli_connect($host, $user, $passwd, $dbname, $port);

        if (!$dbi) {
            return false;
        }

        $this->dbi_ = $dbi;
        return true;
    }

    public function fini()
    {
        if ($this->dbi_) {
            mysqli_close($this->dbi_);
            $this->dbi_ = NULL;
        }
    }

    public function handleGetVersion($keys)
    {
        $exact = array();
        $match = array();

        foreach ($keys as $key) {
            $exact[] = '"' . $key . '"';
            $match[] = '`k` LIKE "' . $key . '.%"';
        }

        $str_exact = implode(' ,', $exact);
        $str_match = implode(' OR ', $match);

        $res = mysqli_query($this->dbi_, sprintf(
            'SELECT MAX(`ts`) FROM t_conf WHERE `k` IN (%s) OR %s',
            $str_exact,
            $str_match
        ));

        $version = NULL;

        if ($res) {
            $data = mysqli_fetch_array($res);

            if ($data[0]) {
                $version = strtotime($data[0]);
            } else {
                return array('status' => 404);
            }
        } else {
            return array('status' => 500);
        }

        mysqli_free_result($res);

        return array(
            'status' => 0,
            'data'   => $version
        );
    }

    public function handleGet($keys)
    {
        $exact = array();
        $match = array();

        foreach ($keys as $key) {
            $exact[] = '"' . $key . '"';
            $match[] = '`k` LIKE "' . $key . '.%"';
        }

        $str_exact = implode(' ,', $exact);
        $str_match = implode(' OR ', $match);

        $res = mysqli_query($this->dbi_, $q = sprintf(
            'SELECT `k`, `v`, `ts` FROM t_conf WHERE `k` IN (%s) OR %s',
            $str_exact,
            $str_match
        ));

        if (false === $res) {
            return array(
                'status' => 500,
                'err' => 'failed to do select ' . mysqli_error($this->dbi_)
            );
        }

        $data = array();

        while ($obj = mysqli_fetch_object($res)) {
            $data[$obj->k] = array($obj->v, strtotime($obj->ts));
        }

        mysqli_free_result($res);

        if (empty($data)) {
            return array('status' => 404);
        } else {
            return array(
                'status' => 0,
                'data'   => $data
            );
        }
    }

    public function handlePut($req)
    {
        $status = 0;

        do {
            if (empty($req)) {
                $status = 404;
                break;
            }

            $stmt = mysqli_prepare(
                $this->dbi_,
                'REPLACE t_conf(`k`, `v`) VALUES(?, ?)'
            );

            if (!$stmt) {
                $status = 500;
                break;
            }

            if (!mysqli_autocommit($this->dbi_, false)) {
                $status = 500;
                break;
            }

            foreach ($req as $k => $v) {
                if (!mysqli_stmt_bind_param($stmt, 'ss', $k, $v)) {
                    $status = 500;
                    break;
                }

                if (!mysqli_stmt_execute($stmt)) {
                    $status = 500;
                    break;
                }
            }

            if ($status) {
                mysqli_rollback($this->dbi_);
            } else {
                mysqli_commit($this->dbi_);
            }

            mysqli_autocommit($this->dbi_, true);
        } while (0);

        return array('status' => $status);
    }

    public function handleDelete($req)
    {
        $status = 0;

        do {
            if (empty($req)) {
                $status = 404;
                break;
            }

            $stmt = mysqli_prepare(
                $this->dbi_,
                'DELETE FROM t_conf WHERE `k` = ?'
            );

            if (!$stmt) {
                $status = 500;
                break;
            }

            if (!mysqli_autocommit($this->dbi_, false)) {
                $status = 500;
                break;
            }

            foreach ($req as $k) {
                if (!mysqli_stmt_bind_param($stmt, 's', $k)) {
                    $status = 500;
                    break;
                }

                if (!mysqli_stmt_execute($stmt)) {
                    $status = 500;
                    break;
                }
            }

            if ($status) {
                mysqli_rollback($this->dbi_);
            } else {
                mysqli_commit($this->dbi_);
            }

            mysqli_autocommit($this->dbi_, true);
        } while (0);

        return array('status' => $status);
    }

    public function __destruct()
    {
        $this->fini();
    }
}
