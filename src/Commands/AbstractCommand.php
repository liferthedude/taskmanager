<?php

namespace Lifer\TaskManager\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Support\Str;

use Lifer\TaskManager\Support\LoggingWithTags;
use Lifer\TaskManager\Model\Task;

abstract class AbstractCommand extends Command
{

	use LoggingWithTags;

    public function error($string, $verbosity = null)
    {
        $this->output->newLine();
        $length = Str::length($string) + 12;
        parent::error(str_repeat(' ', $length));
        parent::error('      '.$string.'      ');
        parent::error(str_repeat(' ', $length));
        $this->output->newLine();
    }

    public function run(InputInterface $input, OutputInterface $output)
    {

        $formatter = new OutputFormatter($output->isDecorated());
        $formatter->setStyle('red', new OutputFormatterStyle('red', 'black'));
        $formatter->setStyle('green', new OutputFormatterStyle('green', 'black'));
        $formatter->setStyle('yellow', new OutputFormatterStyle('yellow', 'black'));
        $formatter->setStyle('blue', new OutputFormatterStyle('blue', 'black'));
        $formatter->setStyle('magenta', new OutputFormatterStyle('magenta', 'black'));
        $formatter->setStyle('yellow-blue', new OutputFormatterStyle('yellow', 'blue'));
        $formatter->setStyle('white-red', new OutputFormatterStyle('white', 'red'));
        $output->setFormatter($formatter);

        $result = parent::run($input, $output);
    }

    protected function formatStatus(string $status) {
        $status = strtoupper($status);
        if ("SUSPENDED" == $status) {
            $status = "<magenta>{$status}</magenta>";
        } elseif ("RUNNING" == $status) {
            $status = "<green>{$status}</green>";
        } elseif ("FAILED" == $status) {
            $status = "<white-red>{$status}</white-red>";
        } elseif ("KILLED" == $status) {
            $status = "<white-red>{$status}</white-red>";
        } elseif ("STOPPED" == $status) {
            $status = "<magenta>{$status}</magenta>";
        } elseif ("ACTIVE" == $status) {
            $status = "<green>{$status}</green>";
        } elseif ("SCHEDULED" == $status) {
            $status = "<yellow>{$status}</yellow>";
        } elseif ("QUEUED" == $status) {
            $status = "<yellow>{$status}</yellow>";
        } elseif ("COMPLETED" == $status) {
            $status = "{$status}";
        }            
        return $status;
    }

    protected function formatTaskDescriprion(Task $task) {
        $text = [];

        if (Task::STATUS_SUSPENDED == $task->getStatus()) {
            $text[] = 'This task is suspended. It will <yellow>NOT</yellow> be executed unless campaign is started or task is called manually.';
        } elseif (Task::STATUS_SCHEDULED == $task->getStatus()) {
            $text[] = 'This task will run according to its schedule. Additional conditions may apply (see description below).';
        } elseif (Task::STATUS_QUEUED == $task->getStatus()) {
            $text[] = 'This task is queued and is currently waiting for other connected tasks to be processed (see description below)';
        }
        $tasks_starts_after = $task->getStartsAfter();
        if (!empty($tasks_starts_after)) {
            $text[] = "This task will only be executed after the following tasks are processed:";
            foreach ($tasks_starts_after as $_task) {
                $text[] = " - #{$_task->getID()} ({$_task->getName()})";
            }
        }

        $tasks_starts_together_with = $task->getStartsTogetherWith();
        if (!empty($tasks_starts_together_with)) {
            $text[] = "This task will only be executed when the following tasks are started:";
            foreach ($tasks_starts_together_with as $_task) {
                $text[] = " - #{$_task->getID()} ({$_task->getName()})";
            }
        }
        $additional_details = $task->getDetails();
        if (!empty($additional_details)) {
            $text[] = '';
            $text[] = "Additional details:";
            $text[] = $additional_details;
        }
        return implode("\n", $text);
    }

}
