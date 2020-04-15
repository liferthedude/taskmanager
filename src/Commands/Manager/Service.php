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

    protected $needsTermination = false;

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
        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, [$this, 'needsTermination']);
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
            if ($this->needsTermination) {
                $this->terminate();
            }
        }
    }

    protected function needsTermination() {
        $this->needsTermination = true;
    }

    protected function terminate() {
        exit;
    }
}
