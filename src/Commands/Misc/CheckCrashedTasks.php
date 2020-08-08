<?php

namespace Lifer\TaskManager\Commands\Misc;

use Illuminate\Console\Command;
use Lifer\TaskManager\Support\LoggingWithTags;
use Lifer\TaskManager\Model\Task;

class CheckCrashedTasks extends Command
{

    use LoggingWithTags;

    const DISPATCHED_THRESHOLD_MINUTES = 120;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taskmanager:check_crashed_tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check tasks that may be broken';



    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->logging_tags = ['taskmanager:check_crashed_tasks'];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {   
        $tasks = Task::where("status", Task::STATUS_RUNNING)->where("updated_at","<",now()->subSeconds(5))->get();
        foreach($tasks as $task) {
            if (!$task->isRunning()) {
                $this->logError("Task #{$task->getID()} has status RUNNING but is not actually running");
            }
        }   

        $tasks = Task::where("status", Task::STATUS_DISPATCHED)->where("updated_at","<",now()->subMinutes(self::DISPATCHED_THRESHOLD_MINUTES))->get();
        foreach($tasks as $task) {
            $minutes = now()->diffInMinutes($task->updated_at);
            $this->logError("Task #{$task->getID()} has status DISPATCHED for {$minutes} minutes");
        }       

        $tasks = Task::where("status",Task::STATUS_SCHEDULED)->whereNull("scheduled_at")->get();
        foreach($tasks as $task) {
            $this->logError("Task #{$task->getID()} has status SCHEDULED but scheduled_at is null");
        }            
        $this->logDebug("done!");           
    }
}
