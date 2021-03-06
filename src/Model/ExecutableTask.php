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
    	$this->logging_tags = ["Task #{$task->id}",'Executable',str_replace(config("taskmanager.executable_tasks_namespace"),"",get_class($this))];
    }

    public final function run(): bool {
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
        return $completed;
    }

    abstract protected function __run(): void;

    protected function failed(): void {
        $this->taskLog->failed();
        $this->task->failed();
    }

    protected function completed(): void {
        $this->taskLog->completed();
        $this->task->completed();
    }

    public final function runStandaloneProcess(): void {
    	$base_path = base_path();
    	shell_exec("php {$base_path}/artisan task:run {$this->task->id} > /dev/null 2>&1 &");
    }

    public function requiresStandaloneProcess(): bool {
        return (!empty($this->standalone_process));
    }

    protected function addTaskMonologHandler(): void {
        $handler = new StreamHandler( $this->taskLog->getLogFilename(), Monolog::DEBUG);
        $handler->setFormatter(new LineFormatter(null, 'Y-m-d H:i:s', true, true));
        $monolog = \Log::getLogger();
        $monolog->pushHandler($handler);
    }

    protected function removeTaskMonologHandler(): void {
        $monolog = \Log::getLogger();
        $monolog->popHandler();
    }

    public function getDetails() {}

    public function setParams(array $params): void {
        foreach ($params as $key => $value) {
            $this->params[$key] = $value;
        }
    }
}
