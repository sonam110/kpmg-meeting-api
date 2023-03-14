<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('sender_id')->nullable();
            $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');

            $table->string('title')->nullable();
            $table->text('message')->nullable();
            $table->integer('data_id')->nullable();
            $table->enum('type',['meeting','note','action'])->default('meeting')->comment('meeting,note,action');
            $table->string('status_code', 50)->default('success')->nullable()->comment('success, failed, warning, primary, secondary, error, alert, info');
            $table->boolean('read_status')->default(0); 
            $table->timestamp('read_at')->nullable();
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
        Schema::dropIfExists('notifications');
    }
};
