<?php

namespace Lifer\TaskManager\Commands\Manager;

use Illuminate\Console\Command;

class Terminate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manager:terminate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Terminate the task manager service';

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
        $pid = resolve("TaskManager")->getPID();
        $this->info("Sending TERM Signal To Process: {$pid}");
        if (! posix_kill($pid, SIGTERM)) {
            $this->error("Failed to kill process: {$pid} (".posix_strerror(posix_get_last_error()).')');
        } else {
            $this->info("Done!");
        }
    }
}
