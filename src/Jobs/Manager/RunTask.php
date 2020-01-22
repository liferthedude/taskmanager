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
        $this->logDebug("Running task...");
        $this->task->run();
        return true;
    }
}
