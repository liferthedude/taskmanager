<?php

namespace Lifer\TaskManager\Services;

use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Jobs\Manager\RunTask;
use Lifer\TaskManager\Support\LoggingWithTags;
class TaskManager {

	use LoggingWithTags;

	protected $state;

	const STATE_STARTED = 'started';
	const STATE_PID = 'pid';

	public function __construct() {
		$this->loadState();
		$this->logging_tags = ['TaskManager'];
	}

	public function start() {
		$this->state[self::STATE_STARTED] = true;
		$this->saveState();
	}

	public function stop() {
		$this->state[self::STATE_STARTED] = false;
		$this->saveState();
	}

	public function loadState() {
		if (file_exists($this->getStateFilePath())) {
			$this->state = json_decode(file_get_contents($this->getStateFilePath()), true);
		} else {
			$this->state = [];
		}
	}

	protected function getStateFilePath() {
		return storage_path("taskmanager.state");
	}

	protected function saveState() {
		file_put_contents($this->getStateFilePath(), json_encode($this->state));
	}

	public function isStarted() {
		return (!empty($this->state[self::STATE_STARTED]));
	}

	public function setPID($pid) {
		$this->state[self::STATE_PID] = $pid;
		$this->saveState();
	}

	public function getPID() {
		if (!empty($this->state[self::STATE_PID])) {
			return $this->state[self::STATE_PID];
		}
		return null;
	}

	public function doWork() {
		$this->logging_tags = ['TaskManager','DoWork'];
		$tasks = Task::where("scheduled_at","<=", now())->whereIn("status",[Task::STATUS_SCHEDULED, Task::STATUS_QUEUED])->get();
        foreach ($tasks as $task) {
            $ok_to_run = true;
            $tasks_starts_after = $task->getStartsAfter();
            if (!empty($tasks_starts_after)) {
                foreach ($tasks_starts_after as $_task) {
                    if ($_task->getLastCompletedAt() < $task->getScheduledAt()) {
                        $ok_to_run = false;
                    }
                }
            }
            $tasks_starts_together_with = $task->getStartsTogetherWith();
            if (!empty($tasks_starts_together_with)) {
                foreach ($tasks_starts_together_with as $_task) {
                    if ($_task->getLastStartedAt() < $task->getScheduledAt()) {
                        $ok_to_run = false;
                    }
                }
            }
            if (!$ok_to_run) {
                $task->setStatus(Task::STATUS_QUEUED);
            } else {
                $this->logDebug("dispatched task #{$task->id} ({$task->name})");
                RunTask::dispatch($task);
            }
        }
	}
}