<?php

namespace Lifer\TaskManager\Services;

use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Model\ExecutableTask;

class ExecutableTaskFactory {

	public static function create(Task $task): ExecutableTask {
		$classname = config("taskmanager.executable_tasks_namespace").$task->type;
		$executable = new $classname($task);
		return $executable;
	}

} 