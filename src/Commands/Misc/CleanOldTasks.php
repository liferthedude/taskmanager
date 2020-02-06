<?php

namespace Lifer\TaskManager\Commands\Misc;

use Illuminate\Console\Command;
use Lifer\TaskManager\Support\LoggingWithTags;
use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Model\TaskLog;

class CleanOldTasks extends Command
{

    use LoggingWithTags;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean_old_tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean old tasks';



    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->logging_tags = ['CleanOldTasks'];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (empty(config("taskmanager.tasks_storage_period_days"))) {
            $this->comment("config taskmanager.tasks_storage_period_days property is not set");
            exit;
        }

        $tasks = Task::whereIn("status",[Task::STATUS_COMPLETED, Task::STATUS_FAILED])->where("updated_at", "<", now()->subDays(config("taskmanager.tasks_storage_period_days")))->get();
        foreach ($tasks as $task) {
            $task_logs = $task->taskLogs()->get();
            foreach ($task_logs as $task_log) {
                $task_log->delete();
            }
            $task->delete();
        }
        $this->logDebug("Cleaned ".count($tasks)." tasks");

        $task_logs = TaskLog::where("updated_at", "<", now()->subDays(config("taskmanager.tasks_storage_period_days")))->get();
        foreach ($task_logs as $task_log) {
            $task_log->delete();
        }

        $this->logDebug("Cleaned ".count($task_logs)." task logs");
    }
}
