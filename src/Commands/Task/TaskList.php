<?php

namespace Lifer\TaskManager\Commands\Task;

use Lifer\TaskManager\Commands\AbstractCommand;
use Lifer\TaskManager\Model\Task;

class TaskList extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:list {status=all} {--name=} {--limit=30}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the list of existing tasks';

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
        if (!in_array($this->argument("status"),['all', Task::STATUS_SCHEDULED, Task::STATUS_QUEUED, Task::STATUS_DISPATCHED, Task::STATUS_RUNNING, Task::STATUS_COMPLETED, Task::STATUS_SUSPENDED, Task::STATUS_FAILED, Task::STATUS_KILLED])) {
            return $this->error("Unknown status: {$this->argument("status")}. Allowed values: ". implode(",", ['all', Task::STATUS_SCHEDULED, Task::STATUS_QUEUED, Task::STATUS_DISPATCHED, Task::STATUS_RUNNING, Task::STATUS_COMPLETED, Task::STATUS_SUSPENDED, Task::STATUS_FAILED, Task::STATUS_KILLED]));
        }

        $tasks = Task::query();
        if ("all" != $this->argument("status")) {
            $tasks = $tasks->where("status", $this->argument("status"));
        } 

        if (!empty($this->option("name"))) {
            $tasks = $tasks->where("name","like","%name%");
        }
        $tasks = $tasks->orderBy("created_at","desc")->take($this->option('limit'))->get();

        if (0 == $tasks->count()) {
            $this->output->newLine();
            $this->comment("There are no tasks that match given filters");
            $this->output->newLine();
            return true;
        }

        $data = [];

        foreach ($tasks as $task) {
            $data[] = [
                $task->getID(),
                $task->getName(),
                $this->formatStatus($task->getStatus()),
                $task->getCreatedAt()
            ];
        }

        $this->output->newLine();
        $this->info("Tasks list:");
        $this->output->newLine();

        $headers = ['ID','Name','Status', 'Created at'];
        $this->table($headers,$data);

    }
}
