<?php

namespace Lifer\TaskManager\Model;

use Lifer\TaskManager\Model\AbstractModel;
use Lifer\TaskManager\Services\ExecutableTaskFactory;
use Lifer\TaskManager\Model\TaskLog;
use Lifer\TaskManager\Model\TaskException;

class Task extends AbstractModel
{

    protected $guarded = [];

    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_QUEUED = 'queued';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_FAILED = 'failed';
    const STATUS_KILLED = 'killed';

    const STARTS_AFTER = 'sa';
    const STARTS_TOGETHER_WITH = 'stw';
    

    const SCHEDULE_TYPE_ONCE = 'once';
    const SCHEDULE_TYPE_PERIODICALLY = 'periodically';
    const SCHEDULE_TYPE_NONE = 'none';

    const SCHEDULE_DATE_TYPE_SECOND = 's';
    const SCHEDULE_DATE_TYPE_MINUTE = 'm';
    const SCHEDULE_DATE_TYPE_HOUR = 'h';
    const SCHEDULE_DATE_TYPE_DAY = 'd';
    const SCHEDULE_DATE_TYPE_WEEK = 'w';
    const SCHEDULE_DATE_TYPE_MONTH = 'mth';

    const MAX_PROPERTY_FAILED_RETRIES = 5;

    const PROPERTY_FAILED_RETRIES = 'fr';
    const PROPERTY_SCHEDULE = 'sch';
    const PROPERTY_PERIOD_DESCRIPTION = 'prd';
    const PROPERTY_NUMBER = 'n';
    const PROPERTY_PERIOD_TYPE = 'prt';
    const PROPERTY_RUNS_NUMBER = 'rn';
    const PROPERTY_SUCCSESSFUL_RUNS = 'rn';

    protected $casts = [
        'scheduled_at' => 'datetime',
        'properties' => 'array'
    ];

    public function taskable()
    {
        return $this->morphTo();
    }

    public function taskLog()
    {
        return $this->hasMany('Lifer\TaskManager\Model\TaskLog');
    }

    public function getStatus() {
        return $this->status;
    }

    public function getConfigName() {
        return $this->config;
    }

    public function setStatus(string $status) {
        $allowed_statuses = [
            self::STATUS_SCHEDULED,
            self::STATUS_QUEUED,
            self::STATUS_RUNNING,
            self::STATUS_COMPLETED,
            self::STATUS_SUSPENDED,
            self::STATUS_FAILED
        ];
        if (!in_array($status, $allowed_statuses)) {
            throw new \Exception("unknown status: {$status}");
        }
        if ($this->status == $status) {
            return true;
        }
        $this->status = $status;
        $this->save();
        $this->logDebug("Task status is set to '{$status}'",["Task #{$this->id}"]);
    }

    public function run() {
        if (!in_array($this->getStatus(),[self::STATUS_SCHEDULED,self::STATUS_QUEUED])) {
            throw new \Exception("Task status is not STATUS_SCHEDULED/STATUS_QUEUED. it shouldn't be running...");
        }
        $environment = \App::environment();
        $executableTask = $this->getExecutable();
        if (($environment != "testing") && $executableTask->requiresStandaloneProcess()) {
            return $executableTask->runStandaloneProcess();
        } else {
            return $executableTask->run();
        }
        
    }

    public function getExecutable() {
        return ExecutableTaskFactory::create($this);
    }

    public function getProperties() {
        return $this->properties;
    }

    public function getScheduledAt() {
        return $this->scheduled_at;
    }

    public function startsAfter(Task $task) {
        if ($task->id == $this->id) {
            throw new \Exception("lol what?");
        }
        $properties = $this->properties;
        $properties[self::STARTS_AFTER][] = $task->id;
        $this->properties = $properties;
        $this->save();
    }

    public function startsTogetherWith(Task $task) {
        if ($task->id == $this->id) {
            throw new \Exception("lol what?");
        }
        $properties = $this->properties;
        $properties[self::STARTS_TOGETHER_WITH][] = $task->id;
        $this->properties = $properties;
        $this->save();
    }

    public function getStartsAfter() {
        if (!empty($this->properties[self::STARTS_AFTER])) {
            return self::find($this->properties[self::STARTS_AFTER]);
        }
        return null;
    }

    public function getStartsTogetherWith() {
        if (!empty($this->properties[self::STARTS_TOGETHER_WITH])) {
            return self::find($this->properties[self::STARTS_TOGETHER_WITH]);
        }
        return null;
    }

    public function getDetails() {
        return $this->getExecutable()->getDetails();
    }

    public function setPID($pid) {
        $this->pid = $pid;
        $this->save();
    }

    public function getPID() {
        return $this->pid;
    }

    public function kill() {
        if ($this->status != self::STATUS_RUNNING) {
            throw new TaskException("Task #{$this->id} is not running now");
        }
        if (empty($this->pid)) {
            throw new \Exception("Task #{$this->id} does not have PID");
        }

        if (!posix_getpgid($this->pid)){
            $this->logDebug("Task PID process doesn't exist. kill() does nothing.");
        } else {
            exec("kill -9 {$this->pid}");
        }

        $taskLog = $this->taskLog()->orderBy('id', 'desc')->take(1)->first();
        $taskLog->killed();

        $this->status = self::STATUS_KILLED;
        $this->schedule();
    }

    public function getCurrentRunDuration() {
        if ($this->status != self::STATUS_RUNNING) {
            return null;
        }
        $taskLog = $this->taskLog()->orderBy('id', 'desc')->take(1)->first();
        if (empty($taskLog)) {
            throw new \Exception("task is running and there is no task log. shouldn't happen");
        }
        $runtime_duration = now()->diffInSeconds($taskLog->getCreatedAt());
        return gmdate("H:i:s", $runtime_duration);
    }

    public function getLastSuccessfulRunTime() {
        $taskLog = $this->taskLog()->where("status",TaskLog::STATUS_COMPLETED)->orderBy('id', 'desc')->take(1)->first();
        if (empty($taskLog)) {
            return null;
        }
        return $taskLog->created_at;
    }

    public function getLastCompletedAt() {
        $taskLog = $this->taskLog()->where("status",TaskLog::STATUS_COMPLETED)->orderBy('id', 'desc')->take(1)->first();
        if (empty($taskLog)) {
            return null;
        }
        return $taskLog->completed_at;
    }

    public function getLastStartedAt() {
        $taskLog = $this->taskLog()->orderBy('id', 'desc')->take(1)->first();
        if (empty($taskLog)) {
            return null;
        }
        return $taskLog->created_at;
    }

    public function completed() {
        $this->refresh();
        $this->status = self::STATUS_COMPLETED;
        $this->pid = null;
        $this->schedule();
        $this->logDebug("Task completed",["Task #{$this->id}"]);
    }

    public function failed() {
        $this->refresh();
        $this->status = self::STATUS_FAILED;
        $this->pid = null;
        $this->scheduled_at = null;
        $this->logDebug("Task failed",["Task #{$this->id}"]);
    }

    public function suspend() {
        $this->refresh();
        if ($this->status != self::STATUS_RUNNING) {
            $this->status = self::STATUS_SUSPENDED;
        }
        $this->scheduled_at = null;
        $this->save();
        $this->logDebug("Task suspended",["Task #{$this->id}"]);
    }

    public function stopSchedule() {
        $this->refresh();
        $this->schedule_type = self::SCHEDULE_TYPE_NONE;
        $this->scheduled_at = null;
        $this->save();
        $this->logDebug("Task schedule is set to SCHEDULE_TYPE_NONE",["Task #{$this->id}"]);
    }

    public function schedule() {
        if (in_array($this->status,[self::STATUS_RUNNING, self::STATUS_QUEUED, self::STATUS_SCHEDULED])) {
            throw new \Exception("already scheduled or running (status='{$this->status}'), can't be scheduled now");
        }

        if (self::STATUS_KILLED == $this->status) {
            $this->scheduled_at = null;
            $this->save();
            return true;
        }

        if (self::STATUS_FAILED == $this->status) {
            $properties = $this->properties;
            if (empty($properties[self::PROPERTY_FAILED_RETRIES])) {
                $properties[self::PROPERTY_FAILED_RETRIES] = 0;
            }
            $properties[self::PROPERTY_FAILED_RETRIES]++;
            if ($properties[self::PROPERTY_FAILED_RETRIES] >= (self::MAX_PROPERTY_FAILED_RETRIES)) {
                $this->scheduled_at = null; 
            } else {
                $this->status = self::STATUS_SCHEDULED;
            }
            $this->properties = $properties;
            $this->save();
            return true;
        }

        if (self::STATUS_COMPLETED == $this->status) {
            if (self::SCHEDULE_TYPE_NONE == $this->schedule_type) {
                $this->scheduled_at = null;
                $this->save();
                return true;
            } elseif (self::SCHEDULE_TYPE_ONCE == $this->schedule_type) {
                $this->scheduled_at = null;
                $this->save();
                return true;
            } elseif (self::SCHEDULE_TYPE_PERIODICALLY == $this->schedule_type) {
                $properties = $this->properties;
                if (!empty($this->properties[self::PROPERTY_SCHEDULE][self::PROPERTY_RUNS_NUMBER])) {
                    if (empty($properties[self::PROPERTY_SUCCSESSFUL_RUNS])) {
                        $properties[self::PROPERTY_SUCCSESSFUL_RUNS] = 0;
                    }
                    $properties[self::PROPERTY_SUCCSESSFUL_RUNS]++;
                    if ($properties[self::PROPERTY_SUCCSESSFUL_RUNS] >= $properties[self::PROPERTY_SCHEDULE][self::PROPERTY_RUNS_NUMBER]) {
                        $this->scheduled_at = null;
                        $this->properties = $properties;
                        $this->save();
                        return true;
                    } 

                }
                if (self::SCHEDULE_DATE_TYPE_MINUTE == $properties[self::PROPERTY_SCHEDULE][self::PROPERTY_PERIOD_DESCRIPTION][self::PROPERTY_PERIOD_TYPE]) {
                    $this->scheduled_at = $this->scheduled_at->addMinutes($properties[self::PROPERTY_SCHEDULE][self::PROPERTY_PERIOD_DESCRIPTION][self::PROPERTY_NUMBER]);
                } elseif (self::SCHEDULE_DATE_TYPE_SECOND == $properties[self::PROPERTY_SCHEDULE][self::PROPERTY_PERIOD_DESCRIPTION][self::PROPERTY_PERIOD_TYPE]) {
                    $this->scheduled_at = $this->scheduled_at->addSeconds($properties[self::PROPERTY_SCHEDULE][self::PROPERTY_PERIOD_DESCRIPTION][self::PROPERTY_NUMBER]);
                } elseif (self::SCHEDULE_DATE_TYPE_DAY == $properties[self::PROPERTY_SCHEDULE][self::PROPERTY_PERIOD_DESCRIPTION][self::PROPERTY_PERIOD_TYPE]) {
                    $this->scheduled_at = $this->scheduled_at->addDays($properties[self::PROPERTY_SCHEDULE][self::PROPERTY_PERIOD_DESCRIPTION][self::PROPERTY_NUMBER]);
                } elseif (self::SCHEDULE_DATE_TYPE_HOUR == $properties[self::PROPERTY_SCHEDULE][self::PROPERTY_PERIOD_DESCRIPTION][self::PROPERTY_PERIOD_TYPE]) {
                    $this->scheduled_at = $this->scheduled_at->addHours($properties[self::PROPERTY_SCHEDULE][self::PROPERTY_PERIOD_DESCRIPTION][self::PROPERTY_NUMBER]);
                }

                $this->status = self::STATUS_SCHEDULED;
                $this->properties = $properties;
                $this->save();
                return true;
            }
        } else {
            if (empty($this->scheduled_at)) {
                $this->scheduled_at = now();
            }
            $this->status = self::STATUS_SCHEDULED;
            $this->save();
            return true;
        }
        
    }
}
