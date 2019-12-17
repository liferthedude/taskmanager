<?php

namespace Lifer\TaskManager\Commands\Manager;

use Illuminate\Console\Command;

class Start extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manager:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start the task manager';

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
        resolve('TaskManager')->start();
        $this->comment("Manager started");
    }
}
