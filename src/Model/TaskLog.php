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

    public function task()
    {
        return $this->belongsTo('App\Model\Task');
    }

    public function getLogFilename() {
        return storage_path("logs/tasks/{$this->getID()}.log");
    }

    public function getLogContents() {
    	return file_get_contents($this->getLogFilename());
    }

    public function completed() {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->save();
    }

    public function failed() {
        $this->status = self::STATUS_FAILED;
        $this->save();
    }

    public function killed() {
        $this->status = self::STATUS_KILLED;
        $this->save();
    }

    public function delete() {
        unlink($this->getLogFilename());
        return parent::delete();
    }

}
