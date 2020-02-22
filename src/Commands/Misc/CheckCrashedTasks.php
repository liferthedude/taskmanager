<?php

namespace Lifer\TaskManager\Commands\Misc;

use Illuminate\Console\Command;
use Lifer\TaskManager\Support\LoggingWithTags;
use Lifer\TaskManager\Model\Task;

class CheckCrashedTasks extends Command
{

    use LoggingWithTags;

    const LAST_N_HOURS = 2;

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
        $tasks = Task::whereIn("status",[Task::STATUS_RUNNING, Task::STATUS_DISPATCHED])->where("updated_at","<",now()->subHours(1))->get();

        foreach($tasks as $task) {
            if (!$task->isRunning()) {
                $this->logError("Crashed Task ID: {$task->getID()}, Status: {$task->getStatus()}");
            }
        }            
        $this->logDebug("done!");           
    }
}
