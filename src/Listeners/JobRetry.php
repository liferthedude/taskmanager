<?php

namespace Lifer\TaskManager\Listeners;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Lifer\TaskManager\Support\LoggingWithTags;
use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Jobs\Manager\RunTask;

class JobRetry
{

    use LoggingWithTags;

    public function handle(JobExceptionOccurred $event)
    {
    	$body = json_decode($event->job->getRawBody(),true);
    	$job = unserialize($body['data']['command']);
    	if (!$job instanceof RunTask) {
    		return true;
    	}
    	$task = $job->getTask();
    	if ($task->isRunning()) {
    		$task->setStatus(Task::STATUS_DISPATCHED);
    	}
    }

}
