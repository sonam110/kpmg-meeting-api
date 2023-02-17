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
        Schema::create('action_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meeting_id');
            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->unsignedBigInteger('note_id')->nullable();
            $table->string('mm_ref_id')->unique();
            $table->date('date_opened')->nullable();
            $table->text('task')->nullable();
            $table->string('priority')->nullable();
            $table->date('due_date')->nullable();
            $table->string('complete_percentage')->nullable();
            $table->string('image')->nullable();
            $table->tinyInteger('status')->default(0)->comment('0=No Started,1=In Progress,2=Completed,3=On Hold,4=Cancelled');
            $table->date('complete_date')->nullable();
            $table->text('comment')->nullable();
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
        Schema::dropIfExists('action_items');
    }
};
