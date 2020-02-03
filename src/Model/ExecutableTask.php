<?php

namespace Lifer\TaskManager\Model;

use Lifer\TaskManager\Support\LoggingWithTags;
use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Services\TaskLogFactory;
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Throwable;
use Exception;

abstract class ExecutableTask 
{

	use LoggingWithTags;

	protected $task;

    protected $properties = [];

    protected $params = [];

	protected $standalone_process = false;

    protected $taskLog;

    public function __construct(Task $task) {
    	$this->task = $task;
        $this->properties = $task->getProperties();
        if (!empty($this->properties['params'])) {
            $this->setParams($this->properties['params']);
        }
    	$this->logging_tags = ["Task #{$task->id}",'Executable',str_replace("App\Model\ExecutableTask\\","",get_class($this))];
    }

    public final function run() {
        $this->task->setStatus(Task::STATUS_RUNNING);
        $this->task->setPID(getmypid());
        $this->taskLog = TaskLogFactory::new($this->task);
        $this->addTaskMonologHandler();

        $completed = false;
        $current_retry = 1;

        while (!$completed && ($current_retry <= config('taskmanager.task_run_retries'))) {
            $this->logDebug("Running task... retry #{$current_retry}");
            try {
                $this->__run();
                $this->completed();
                $completed = true;
            } catch (Exception | Throwable $e) {
                $this->logError("Exception: ".get_class($e).": ".$e->getMessage());
                $this->logError($e->getTraceAsString());
            }
            $current_retry++;
        }

        if (!$completed) {
            $this->failed();
        }
        $this->removeTaskMonologHandler();
    }

    abstract protected function __run();

    protected function failed() {
        $this->taskLog->failed();
        $this->task->failed();
    }

    protected function completed() {
        $this->taskLog->completed();
        $this->task->completed();
    }

    public final function runStandaloneProcess() {
    	$base_path = base_path();
    	return shell_exec("php {$base_path}/artisan task:run {$this->task->id} --schedule > /dev/null 2>&1 &");
    }

    public function requiresStandaloneProcess() {
        return (!empty($this->standalone_process));
    }

    protected function addTaskMonologHandler() {
        $handler = new StreamHandler( $this->taskLog->getLogFilename(), Monolog::DEBUG);
        $handler->setFormatter(new LineFormatter(null, 'Y-m-d H:i:s', true, true));
        $monolog = \Log::getLogger();
        $monolog->pushHandler($handler);
    }

    protected function removeTaskMonologHandler() {
        $monolog = \Log::getLogger();
        $monolog->popHandler();
    }

    public function getDetails() {}

    public function setParams(array $params) {
        foreach ($params as $key => $value) {
            $this->params[$key] = $value;
        }
    }
}
