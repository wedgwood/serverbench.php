<?php
namespace ServerBench\Timer;

class TaskQueue extends \SplMinHeap
{
    public function compare($task1, $task2)
    {
        return $task1->ts < $task2->ts ? 1 : ($task1->ts == $task2->ts ? 0 : -1);
    }
}
