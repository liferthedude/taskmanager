<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use Lifer\TaskManager\Model\TaskLog;

class CreateTaskLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger("task_id");
            $table->enum("status",[TaskLog::STATUS_COMPLETED,TaskLog::STATUS_RUNNING,TaskLog::STATUS_FAILED, TaskLog::STATUS_KILLED]);
            $table->datetime("completed_at")->nullable();
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
        Schema::dropIfExists('task_logs');
    }
}
