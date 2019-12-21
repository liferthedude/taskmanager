<?php

namespace Lifer\TaskManager\Jobs\Manager;

use Illuminate\Support\Facades\Cache;
use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Model\Campaign;
use Lifer\TaskManager\Jobs\AbstractJob;


class RunTask extends AbstractJob
{

    protected $task;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->logging_tags = ['TaskManager','RunTask',"Task #{$task->id}"];
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $lock = Cache::lock("task:run:".$this->task->id, 5);
        if (!$lock->get()) {
            $this->logDebug("Job locked...");
            return false;
        } 
        $this->task->refresh();
        if ($this->task->getStatus() == Task::STATUS_RUNNING) {
            return true;
        }
        $this->logDebug("Running task...");
        $this->task->run();
        $lock->release();
        return true;
    }
}
