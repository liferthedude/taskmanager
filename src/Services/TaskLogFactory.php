<?php

namespace Lifer\TaskManager\Services;

use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Model\TaskLog;

class TaskLogFactory {

	public static function new(Task $task): TaskLog {
		return $task->taskLogs()->create([
			'status' => TaskLog::STATUS_RUNNING
		]);
	}

} 