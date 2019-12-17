<?php

namespace Lifer\TaskManager\Model\ExecutableTask;

use Lifer\TaskManager\Model\ExecutableTask;
use Lifer\TaskManager\Extra\LoggingWithTags;
use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Model\ExecutableTask\Exception;

class RunExternalCommand extends ExecutableTask 
{

	protected $standalone_process = true;

    const CONFIG_CMD = 'cmd';

    protected function __run() {
        $cmd = $this->getCMD();
        $this->logDebug("running external command: '{$cmd}'");
        $cmd = "php ".base_path()."/artisan task:fake";
        if (false !== strpos($this->task->campaign->name,"fail")) {
            throw new Exception("Fake process failed");
        }
        exec("$cmd >> {$this->taskLog->getLogFilename()} 2>&1", $output, $return_status);
        if ($return_status != 0) {
            throw new Exception("return status is '{$return_status}'. Cmd: {$cmd}");
        }
    }

    public function getDetails() {
        return "Command: '{$this->getCMD()}'";
    }

    protected function getCMD() {
        if (empty($this->config['properties'][self::CONFIG_CMD])) {
            throw new \Exception("CONFIG_CMD is empty");
        }
        return $this->parseVariables($this->config['properties'][self::CONFIG_CMD]);
    }

    protected function parseVariables($cmd) {
        $vars = $this->task->campaign->getProperties();
        $vars['campaign'] = $this->task->campaign->getName();
        $vars['origin'] = $this->task->campaign->snapchatAccount->getName();
        foreach ($vars as $variable => $value) {
            if (false !== strpos($value," ")) {
                $value = "\"$value\"";
            }
            $cmd = str_replace(":{$variable}", $value, $cmd);
        }
        return $cmd;
    }

}
