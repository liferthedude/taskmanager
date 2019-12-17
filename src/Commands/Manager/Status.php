<?php

namespace Lifer\TaskManager\Commands\Manager;

use Lifer\TaskManager\Commands\AbstractCommand;
use Lifer\TaskManager\Model\TaskLog;

class Status extends AbstractCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manager:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display task manager service status';

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
        $manager = resolve("TaskManager");

        if (!posix_getpgid($manager->getPID())) {
            $this->error("Manager service process is not actually running!");
        }

        $data = [];
        if ($manager->isStarted()) {
            $status = $this->formatStatus("RUNNING");
        } else {
            $status = $this->formatStatus("STOPPED");
        }
        $data[] = ["Status    ", $status];
        $data[] = ["PID", $manager->getPID()];

        $this->output->newLine();
        $this->table(null,$data,'compact');
        $this->output->newLine();

        $this->info("Tasks processed during last 24 hours:");

        $data = [];
        $data[] = ["Completed", TaskLog::where("status", TaskLog::STATUS_COMPLETED)->count()];
        $data[] = ["Failed", TaskLog::where("status", TaskLog::STATUS_FAILED)->count()];
        $data[] = ["Killed", TaskLog::where("status", TaskLog::STATUS_KILLED)->count()];
        $this->table(null,$data);
        $this->output->newLine();
    
    }
}
