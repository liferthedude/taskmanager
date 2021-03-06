<?php

namespace Lifer\TaskManager\Model;

use Lifer\TaskManager\Model\AbstractModel;
use Lifer\TaskManager\Services\ExecutableTaskFactory;
use Lifer\TaskManager\Model\TaskLog;
use Lifer\TaskManager\Model\TaskException;
use Lifer\TaskManager\Model\ExecutableTask;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class Task extends AbstractModel
{

    protected $guarded = [];

    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_QUEUED = 'queued';
    const STATUS_DISPATCHED = 'dispatched';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_FAILED = 'failed';
    const STATUS_KILLED = 'killed';

    const STARTS_AFTER = 'sa';
    const STARTS_TOGETHER_WITH = 'stw';

    const SCHEDULE_TYPE_DAILY = 'd';
    const SCHEDULE_TYPE_HOURLY = 'h';
    const SCHEDULE_TYPE_EVERY_N_MINUTES = 'nm';
    const SCHEDULE_TYPE_EVERY_MINUTE = 'm';

    const PROPERTY_SCHEDULE = 'sch';
    const PROPERTY_SCHEDULE_TYPE = 'sch.t';
    const PROPERTY_SCHEDULE_AT = 'sch.at';
    const PROPERTY_SCHEDULE_PERIOD_DURATION = 'sch.pd';
    const PROPERTY_MAX_RUNS_NUMBER = 'mrn';
    const PROPERTY_SUCCSESSFUL_RUNS = 'srn';

    protected $casts = [
        'scheduled_at' => 'datetime',
        'properties' => 'array',
    ];

    public function taskable()
    {
        return $this->morphTo();
    }

    public function taskLogs()
    {
        return $this->hasMany('Lifer\TaskManager\Model\TaskLog');
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function setProperty(string $path, $value): void {
        $properties = $this->properties;
        if (!is_null($value)) {
            Arr::set($properties, $path, $value);
        } else {
            $path = "'".implode("']['", explode(".", $path))."'";
            $cmd = 'unset($properties['.$path.']);';
            eval($cmd);
        }
        $this->properties = $properties;
    }

    public function unsetProperty(string $path): void {
        $this->setProperty($path, null);
    }

    public function getProperty(string $path) {
        return Arr::get($this->properties, $path);
    }

    public function hasProperty(string $path): bool {
        return !empty($this->getProperty($path));
    }

    public function setName(string $name): void {
        $this->name = $name;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setStatus(string $status): void {
        $allowed_statuses = [
            self::STATUS_SCHEDULED,
            self::STATUS_QUEUED,
            self::STATUS_DISPATCHED,
            self::STATUS_RUNNING,
            self::STATUS_COMPLETED,
            self::STATUS_SUSPENDED,
            self::STATUS_FAILED
        ];
        if (!in_array($status, $allowed_statuses)) {
            throw new \Exception("unknown status: {$status}");
        }
        if ($this->status == $status) {
            return;
        }
        $this->status = $status;
        $this->save();
        $this->logDebug("Task status is set to '{$status}'",["Task #{$this->id}"]);
    }

    public function run(): void {
        if ($this->isRunning()) {
            throw new \Exception("Task is already running");
        }
        $environment = \App::environment();
        $executableTask = $this->getExecutable();
        if (($environment != "testing") && $executableTask->requiresStandaloneProcess()) {
            $executableTask->runStandaloneProcess();
        } else {
            $executableTask->run();
            $this->schedule();
        }
    }

    public function isRunning(): bool {
        if ($this->getStatus() != self::STATUS_RUNNING) {
            return false;
        }
        if (!empty($this->pid) && !posix_getpgid($this->pid)) {
            return false;
        }
        return true;
    }

    public function getExecutable(): ExecutableTask {
        return ExecutableTaskFactory::create($this);
    }

    public function getProperties() {
        return $this->properties;
    }

    public function getScheduledAt(): ?Carbon {
        return $this->scheduled_at;
    }

    public function isScheduled(): bool {
        return !empty($this->scheduled_at);
    }

    public function startsAfter(Task $task): void {
        if ($task->id == $this->id) {
            throw new \Exception("lol what?");
        }
        $properties = $this->properties;
        $properties[self::STARTS_AFTER][] = $task->id;
        $this->properties = $properties;
        $this->save();
    }

    public function startsTogetherWith(Task $task): void {
        if ($task->id == $this->id) {
            throw new \Exception("lol what?");
        }
        $properties = $this->properties;
        $properties[self::STARTS_TOGETHER_WITH][] = $task->id;
        $this->properties = $properties;
        $this->save();
    }

    public function getStartsAfter(): ?Task {
        if (!empty($this->properties[self::STARTS_AFTER])) {
            return self::find($this->properties[self::STARTS_AFTER]);
        }
        return null;
    }

    public function getStartsTogetherWith(): ?Task {
        if (!empty($this->properties[self::STARTS_TOGETHER_WITH])) {
            return self::find($this->properties[self::STARTS_TOGETHER_WITH]);
        }
        return null;
    }

    public function getDetails() {
        return $this->getExecutable()->getDetails();
    }

    public function setPID($pid): void {
        $this->pid = $pid;
        $this->save();
    }

    public function getPID(): ?int {
        return $this->pid;
    }

    public function kill(): void {
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

        $taskLog = $this->taskLogs()->orderBy('id', 'desc')->take(1)->first();
        $taskLog->killed();

        $this->status = self::STATUS_KILLED;
        $this->save();
        $this->logDebug("Task killed",["Task #{$this->id}"]);
    }

    public function getCurrentRunDuration(): ?string {
        if ($this->status != self::STATUS_RUNNING) {
            return null;
        }
        $taskLog = $this->taskLogs()->orderBy('id', 'desc')->take(1)->first();
        if (empty($taskLog)) {
            throw new \Exception("task is running and there is no task log. shouldn't happen");
        }
        $runtime_duration = now()->diffInSeconds($taskLog->getCreatedAt());
        return gmdate("H:i:s", $runtime_duration);
    }

    public function getLastSuccessfulRunTime(): ?Carbon {
        $taskLog = $this->taskLogs()->where("status",TaskLog::STATUS_COMPLETED)->orderBy('id', 'desc')->take(1)->first();
        if (empty($taskLog)) {
            return null;
        }
        return $taskLog->created_at;
    }

    public function getLastCompletedAt(): ?Carbon {
        $taskLog = $this->taskLogs()->where("status",TaskLog::STATUS_COMPLETED)->orderBy('id', 'desc')->take(1)->first();
        if (empty($taskLog)) {
            return null;
        }
        return $taskLog->completed_at;
    }

    public function getLastStartedAt(): ?Carbon {
        $taskLog = $this->taskLogs()->orderBy('id', 'desc')->take(1)->first();
        if (empty($taskLog)) {
            return null;
        }
        return $taskLog->created_at;
    }

    public function setMaxRunsNumber(int $number): void {
        $this->setProperty(self::PROPERTY_MAX_RUNS_NUMBER, $number);
        $this->save();
    }

    public function completed(): void {
        $this->refresh();
        if (!in_array($this->status, [self::STATUS_SUSPENDED,self::STATUS_SCHEDULED])) {
            $this->status = self::STATUS_COMPLETED;
            $this->scheduled_at = null;
        }
        $this->pid = null;
        $this->setProperty(self::PROPERTY_SUCCSESSFUL_RUNS, (int) $this->getProperty(self::PROPERTY_SUCCSESSFUL_RUNS)+1);
        $this->save();
        $this->logDebug("Task completed",["Task #{$this->id}"]);
    }

    public function failed(): void {
        $this->refresh();
        $this->status = self::STATUS_FAILED;
        $this->pid = null;
        $this->scheduled_at = null;
        $this->save();
        #$this->removeSchedule();
        $this->logError("Task failed",["Task #{$this->id}"]);
    }

    public function removeSchedule(): void {
        $this->refresh();
        $this->unsetProperty(self::PROPERTY_SCHEDULE);
        $this->scheduled_at = null;
        $this->save();
        $this->logDebug("Task schedule removed",["Task #{$this->id}"]);
    }

    public function scheduleDailyAt(string $time): void {
        $this->unsetProperty(self::PROPERTY_SCHEDULE);
        list($hours, $minutes, $seconds) = explode(":",$time);
        $this->setProperty(self::PROPERTY_SCHEDULE_AT, ['h' => (int) $hours, 'm' => (int) $minutes, 's' => (int) $seconds]);
        $this->setProperty(self::PROPERTY_SCHEDULE_TYPE, self::SCHEDULE_TYPE_DAILY);
        $this->save();
        $this->schedule();
    }

    public function scheduleHourlyAt(string $time): void {
        $this->unsetProperty(self::PROPERTY_SCHEDULE);
        list($minutes, $seconds) = explode(":",$time);
        $this->setProperty(self::PROPERTY_SCHEDULE_AT, ['m' => (int) $minutes, 's' => (int) $seconds]);
        $this->setProperty(self::PROPERTY_SCHEDULE_TYPE, self::SCHEDULE_TYPE_HOURLY);
        $this->save();
        $this->schedule();
    }

    public function scheduleEveryFiveMinutes(): void {
        $this->unsetProperty(self::PROPERTY_SCHEDULE);
        $this->setProperty(self::PROPERTY_SCHEDULE_TYPE, self::SCHEDULE_TYPE_EVERY_N_MINUTES);
        $this->setProperty(self::PROPERTY_SCHEDULE_PERIOD_DURATION, 5);
        $this->save();
        $this->schedule();
    }

    public function scheduleEveryMinute(): void {
        $this->unsetProperty(self::PROPERTY_SCHEDULE);
        $this->setProperty(self::PROPERTY_SCHEDULE_TYPE, self::SCHEDULE_TYPE_EVERY_MINUTE);
        $this->save();
        $this->schedule();
    }

    public function scheduleAt(Carbon $scheduled_at): void {
        $this->unsetProperty(self::PROPERTY_SCHEDULE);
        $this->status = self::STATUS_SCHEDULED;
        $this->scheduled_at = $scheduled_at;
        $this->save();
        $this->logDebug("Task scheduled at ".$this->scheduled_at->toDateTimeString(),["Task #{$this->id}"]);
    }

    public function schedule(): bool {

        if (empty($this->getProperty(self::PROPERTY_SCHEDULE_TYPE))) {
            return false;
        }

        if ($this->hasProperty(self::PROPERTY_MAX_RUNS_NUMBER) && ($this->getProperty(self::PROPERTY_SUCCSESSFUL_RUNS) >= $this->getProperty(self::PROPERTY_MAX_RUNS_NUMBER))) {
            return false;
        }

        if (in_array($this->status,[self::STATUS_RUNNING, self::STATUS_QUEUED, self::STATUS_SCHEDULED])) {
            return true;
        }

        if (self::SCHEDULE_TYPE_DAILY == $this->getProperty(self::PROPERTY_SCHEDULE_TYPE)) {
            $at = $this->getProperty(self::PROPERTY_SCHEDULE_AT);
            if (empty($at)) {
                $at = ['h'=>0,'m'=>0,'s'=>0];
            }
            if (now() < now()->startOfDay()->addHours($at['h'])->addMinutes($at['m'])->addSeconds($at['s'])) {
                $this->scheduled_at = now()->startOfDay()->addHours($at['h'])->addMinutes($at['m'])->addSeconds($at['s']);
            } else {
                $this->scheduled_at = now()->startOfDay()->addDays(1)->addHours($at['h'])->addMinutes($at['m'])->addSeconds($at['s']);
            }
        } elseif (self::SCHEDULE_TYPE_HOURLY == $this->getProperty(self::PROPERTY_SCHEDULE_TYPE)) {
            $at = $this->getProperty(self::PROPERTY_SCHEDULE_AT);
            if (empty($at)) {
                $at = ['m'=>0,'s'=>0];
            }
            if (now() < now()->startOfHour()->addMinutes($at['m'])->addSeconds($at['s'])) {
                $this->scheduled_at = now()->startOfHour()->addMinutes($at['m'])->addSeconds($at['s']);
            } else {
                $this->scheduled_at = now()->startOfHour()->addHours(1)->addMinutes($at['m'])->addSeconds($at['s']);
            }
        } elseif (self::SCHEDULE_TYPE_EVERY_N_MINUTES == $this->getProperty(self::PROPERTY_SCHEDULE_TYPE)) {
                $this->scheduled_at = now()->addMinutes($this->getProperty(self::PROPERTY_SCHEDULE_PERIOD_DURATION));
        } elseif (self::SCHEDULE_TYPE_EVERY_MINUTE == $this->getProperty(self::PROPERTY_SCHEDULE_TYPE)) {
                $this->scheduled_at = now()->startOfMinute()->addMinutes(1);
        } else {
            throw new \Exception("schedule type not implemented");
        }

        $this->status = self::STATUS_SCHEDULED;
        $this->save();
        $this->logDebug("Task scheduled at ".$this->scheduled_at->toDateTimeString(),["Task #{$this->id}"]);
        return true;
    }
}
