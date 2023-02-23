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
            $table->string('meetRandomId',36);
            $table->string('meeting_title');
            $table->string('meeting_ref_no')->nullable();
            $table->text('agenda_of_meeting')->nullable();
            $table->date('meeting_date');
            $table->time('meeting_time');
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
