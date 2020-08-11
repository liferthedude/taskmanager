<?php

namespace Lifer\TaskManager\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Lifer\TaskManager\Support\LoggingWithTags;
use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Jobs\Manager\RunTask;

class JobHasFailed
{

    use LoggingWithTags;

    public function handle(JobFailed $event): void
    {
    	$body = json_decode($event->job->getRawBody(),true);
    	$job = unserialize($body['data']['command']);
    	if (!$job instanceof RunTask) {
    		return;
    	}
    	$task = $job->getTask();
    	$task->failed();
    	$task->schedule();
    }

}
