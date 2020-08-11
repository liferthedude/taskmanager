<?php

namespace Lifer\TaskManager\Services;

use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Model\ExecutableTask;

class ExecutableTaskFactory {

	public static function create(Task $task): ExecutableTask {
		$classname = '\App\Model\ExecutableTask\\'.$task->type;
		$executable = new $classname($task);
		return $executable;
	}

} 