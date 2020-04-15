<?php

namespace Lifer\TaskManager\Commands\Manager;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

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
        Cache::forever("taskmanager:terminate",true);
        $this->info("Done!");
    }

}
