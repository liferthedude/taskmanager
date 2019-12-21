<?php

namespace Lifer\TaskManager\Model;

use Lifer\TaskManager\Support\LoggingWithTags;
use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Services\TaskLogFactory;

use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;


abstract class ExecutableTask 
{

	use LoggingWithTags;

	protected $task;

    protected $properties = [];

    protected $config = [];

	protected $standalone_process = false;

    public function __construct(Task $task) {
    	$this->task = $task;
        $this->properties = $task->getProperties();
        if (!empty($this->task->getConfigName())) {
            $this->setConfig();
        }
    	$this->logging_tags = ["Task #{$task->id}",'Executable',str_replace("App\Model\ExecutableTask\\","",get_class($this))];
    }

    protected function setConfig() {
        $filename = base_path("config/tasks_config/{$this->task->getConfigName()}.json");
        if (!file_exists($filename)) {
            throw new \Exception("Task config file {$filename} does not exist");
        }
        $this->config = json_decode(file_get_contents($filename),true);
        if (null === $this->config) {
            throw new \Exception("Task config {$this->task->getConfigName()} is not a valid JSON document");
        }
    }

    public final function run() {
        $this->task->setStatus(Task::STATUS_RUNNING);
        $this->task->setPID(getmypid());
        $this->taskLog = TaskLogFactory::new($this->task);
        $this->addTaskMonologHandler();
        try {
            $this->__run();
            $this->completed();
        } catch (\Exception $e) {
            $this->logError($e->getMessage());
            $this->logError($e->getTraceAsString());
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
    	return shell_exec("php {$base_path}/artisan task:run {$this->task->id} > /dev/null 2>&1 &");
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

}
