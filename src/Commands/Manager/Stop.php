<?php

namespace Lifer\TaskManager\Commands\Manager;

use Illuminate\Console\Command;

class Stop extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manager:stop {--kill}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Stop the task manager service';

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
        resolve('TaskManager')->stop();
        $this->comment("Manager stopped.");
        if (!empty($this->option('kill'))) {
            $this->comment('TODO: killing active tasks...');
            $this->info('done!');
        } else {
            $this->comment("All active tasks are still running. To kill them run: 'php artisan manager:stop --kill'");
        }
        
    }
}
