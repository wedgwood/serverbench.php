<?php
/**
 * codec decorator for process fn
 *
 * @author Yuan B.J.
 */

namespace ServerBench\Codec;

class Decorator
{
    private $codec_ = null;
    private $fn_ = null;

    public function __construct($codec, $fn)
    {
        $this->codec_ = $codec;
        $this->fn_ = $fn;
    }

    public function __invoke($message)
    {
        $req = $this->codec_->decode($message);
        $rep = call_user_func($this->fn_, $req);
        return $this->codec_->encode($rep);
    }
}
