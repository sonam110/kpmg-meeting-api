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
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organised_by')->nullable();
            $table->foreign('organised_by')->references('id')->on('users')->onDelete('cascade');
            $table->string('meetRandomId',36);
            // $table->string('organizer');
            $table->string('meeting_title');
            $table->string('meeting_ref_no')->nullable();
            $table->longText('agenda_of_meeting')->nullable();
            $table->date('meeting_date');
            $table->time('meeting_time_start');
            $table->time('meeting_time_end');
            $table->integer('message_id')->nullable();
            $table->string('meeting_uid')->nullable();
            $table->string('invite_file')->nullable();
            $table->string('meeting_link')->nullable();
            $table->boolean('status')->default('1')->comment('1:Active,2:Inactive');
            $table->tinyInteger('is_repeat')->default(0)->comment('0=No,1=Yes');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('meetings');
    }
};
