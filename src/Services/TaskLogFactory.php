<?php

namespace Lifer\TaskManager\Services;

use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Model\TaskLog;

class TaskLogFactory {

	public static function new(Task $task) {
		return TaskLog::create([
			'task_id' => $task->id,
			'status' => TaskLog::STATUS_RUNNING
		]);
	}

} 