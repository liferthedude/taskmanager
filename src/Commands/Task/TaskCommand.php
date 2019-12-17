<?php

namespace Lifer\TaskManager\Commands\Task;

use Illuminate\Support\Facades\Cache;
use Lifer\TaskManager\Commands\AbstractCommand;
use Lifer\TaskManager\Model\Task;

abstract class TaskCommand extends AbstractCommand
{

    protected $task;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    protected function getTask() {
        $this->task = Task::find($this->argument('task_id'));
        if (empty($this->task)) {
            $this->error("Task with ID #{$this->argument('task_id')} was not found.");
            return false;
        }
        $this->logging_tags = ["#{$this->task->id}"];
        return true;
    }
}
