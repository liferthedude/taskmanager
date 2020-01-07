<?php

namespace Lifer\TaskManager\Commands\Task;

use Lifer\TaskManager\Commands\Task\TaskCommand;
use Illuminate\Support\Facades\Cache;
use Lifer\TaskManager\Model\Task;

class Run extends TaskCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:run {task_id} {--schedule} {--params=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run task as standalone process. Great for long running tasks.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!$this->getTask()) {
            return false;
        }
        $this->addTag('task:run');

        $lock = Cache::lock("task:run_external:".$this->task->id, 5);
        if (!$lock->get()) {
            $this->logDebug("Task locked...");
            return false;
        } 
        $this->logDebug("Running task...");
        $executableTask = $this->task->getExecutable();
        if (!empty($this->option("params"))) {
            $params = [];
            $_params = explode(" ", $this->option("params"));
            foreach ($_params as $_param) {
                list($key,$value) = explode(":",$_param);
                $params[$key] = $value;
            }
            $executableTask->setParams($params);
        }
        $executableTask->run();
        if (!empty($this->option("schedule"))) {
            $this->task->schedule();
        }
        $lock->release();
        $this->info("Done!");
    }
}
