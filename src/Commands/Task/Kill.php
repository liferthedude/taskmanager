<?php

namespace Lifer\TaskManager\Commands\Task;

use Lifer\TaskManager\Commands\Task\TaskCommand;
use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Model\TaskException;

class Kill extends TaskCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:kill {task_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kill the task';

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
        try {
            $this->task->kill();
        } catch (TaskException $e) {
            $this->error($e->getMessage());
            return false;
        }
        $this->info("Killed!");
    }
}
