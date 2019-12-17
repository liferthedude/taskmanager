<?php

namespace Lifer\TaskManager\Commands\Manager;

use Illuminate\Console\Command;


class Service extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'manager';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starts the manager service';

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
        $manager->setPID(getmypid());
        while (true) {
            $manager->loadState();
            if ($manager->isStarted()) {
                $manager->doWork();
            }
            sleep(2);
        }
    }
}
