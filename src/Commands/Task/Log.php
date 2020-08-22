<?php

namespace Lifer\TaskManager\Commands\Task;

use Lifer\TaskManager\Commands\Task\TaskCommand;
use Lifer\TaskManager\Model\TaskLog;

class Log extends TaskCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:log {task_id : The ID of the task} {--log_id= : The ID of the particular task run} {--history : show task run history} {--number= : number of log entries to show} {--f|follow : follows the log, like tail -f }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show the task log';

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
        $this->addTag('task:log');
        if (!empty($this->option("history"))) {
            return $this->showHistory();
        }

        if (!empty($this->option("follow"))) {
            return $this->follow();
        }

        if (!empty($this->option("log_id"))) {
            $taskLog = $this->task->taskLogs()->find($this->option("log_id"));
            if (empty($taskLog)) {
                return $this->error("Task log with ID #{$this->option('log_id')} was not found.");
            }
        } else {
            $taskLog = $this->task->taskLogs()->get()->last();
            if (empty($taskLog)) {
                return $this->comment("Task with ID #{$this->task->getID()} has no logs yet");
            }
        }



        $this->output->newLine();
        $data = [];
        $data[] = ["Task ID","{$this->task->getID()}"];
        $data[] = ["Log ID ","{$taskLog->getID()}"];
        $data[] = ["Started at","{$taskLog->getCreatedAt()}"];
        $data[] = ["Status","{$this->formatStatus($taskLog->getStatus())}"];
        $data[] = ["Execution time","{$this->getExecutionTime($taskLog)}"];

        $this->info("Summary:");
        $this->table(null, $data);

        $this->output->newLine();
        $this->info("Run log:");
        $this->comment(str_repeat('-', 100));
        echo $taskLog->getLogContents();
        $this->comment(str_repeat('-', 100));
        $this->output->newLine();

    }

    protected function showHistory() {
        if (empty($this->option("number"))) {
            $this->info("Below is the history of last 10 task runs. Use '--history --number=<N>' option to display more task runs");
            $this->output->newLine();
        }
        $this->info("Use --log_id=<log_id> option to see particular log entry");
        $number = (empty($this->option("number"))) ? 10 : (int) $this->option("number");
        $taskLogs = $this->task->taskLogs()->orderBy("created_at","desc")->limit($number)->get();
        $data = [];
        foreach ($taskLogs as $taskLog) {
            $data[] = [$taskLog->getID(), $this->formatStatus($taskLog->getStatus()), $taskLog->getCreatedAt(),$this->getExecutionTime($taskLog)];
        }

        $headers = ['Task log ID', 'Status','Started at','Execution time'];
        $this->table($headers, $data);
    }

    protected function follow() {
        $filename = $this->task->taskLogs()->get()->last()->getLogFilename();
        system("tail -F $filename");
    }

    protected function getExecutionTime(TaskLog $taskLog) {
        $from = $taskLog->created_at;
        if ($taskLog->getStatus() == TaskLog::STATUS_COMPLETED) {
            $to = $taskLog->completed_at;
        } else {
            $to = now();
        }
        $diff = $to->diffInSeconds($from);
        return sprintf('%02d:%02d:%02d', ($diff/3600),($diff/60%60), $diff%60);
    }

}
