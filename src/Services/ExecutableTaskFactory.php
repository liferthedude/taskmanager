<?php

namespace Lifer\TaskManager\Services;

use Lifer\TaskManager\Model\Task;

class ExecutableTaskFactory {

	public static function create(Task $task) {
		$classname = '\Lifer\TaskManager\Model\ExecutableTask\\'.$task->type;
		$executable = new $classname($task);
		return $executable;
	}

} 