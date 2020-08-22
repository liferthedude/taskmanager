<?php

namespace Lifer\TaskManager\Model;

use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Model\AbstractModel;

class TaskLog extends AbstractModel
{

    protected $guarded = [];

    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_RUNNING = 'running';
    const STATUS_KILLED = 'killed';

    protected $casts = [
        'completed_at' => 'datetime'
    ];

    public function task()
    {
        return $this->belongsTo('App\Model\Task');
    }

    public function getLogFilename(): string {
        return storage_path("logs/tasks/{$this->getID()}.log");
    }

    public function getLogContents(): string {
    	return file_get_contents($this->getLogFilename());
    }

    public function completed(): void {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->save();
    }

    public function failed(): void {
        $this->status = self::STATUS_FAILED;
        $this->save();
    }

    public function killed(): void {
        $this->status = self::STATUS_KILLED;
        $this->save();
    }

    public function delete() {
        if (file_exists($this->getLogFilename())) {
            unlink($this->getLogFilename());
        }
        return parent::delete();
    }

}
