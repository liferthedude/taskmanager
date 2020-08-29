<?php

return [
    "task_run_retries" => env("TASKMANAGER_TASK_RUN_RETRIES", 3),
    "executable_tasks_namespace" => env("TASKMANAGER_EXECUTABLE_TASKS_NAMESPACE", "\App\Model\TaskService\ExecutableTasks\\"),
    "queue" => env("TASKMANAGER_QUEUE", 'default')
];