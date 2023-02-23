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
        Schema::create('meeting_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meeting_id');
            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');
            $table->string('duration');
            $table->text('notes');
            $table->text('decision')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('edited_by')->nullable();
            $table->boolean('status')->default('1')->comment('1:Active,2:Inactive');
            $table->timestamp('edited_date');
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
        Schema::dropIfExists('meeting_notes');
    }
};
