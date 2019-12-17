<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Lifer\TaskManager\Model\Task;
use Lifer\TaskManager\Model\Schedule;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name',100);
            $table->enum("status",[Task::STATUS_SCHEDULED, Task::STATUS_QUEUED, Task::STATUS_RUNNING, Task::STATUS_COMPLETED, Task::STATUS_SUSPENDED, Task::STATUS_FAILED, Task::STATUS_KILLED]);
            $table->string("type",100);
            $table->string("config",100)->nullable();
            $table->bigInteger('campaign_id')->nullable();
            $table->enum('schedule_type', [Task::SCHEDULE_TYPE_ONCE, Task::SCHEDULE_TYPE_PERIODICALLY]);
            $table->datetime('scheduled_at')->nullable();
            $table->mediumInteger('pid')->nullable();
            $table->text('properties')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
