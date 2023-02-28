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
        Schema::create('meeting_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meeting_id')->nullable();
            $table->foreign('meeting_id')->references('id')->on('meetings')->onDelete('cascade');

            $table->unsignedBigInteger('note_id')->nullable();
            $table->foreign('note_id')->references('id')->on('meeting_notes')->onDelete('cascade');

            $table->unsignedBigInteger('action_id')->nullable();
            $table->foreign('action_id')->references('id')->on('action_items')->onDelete('cascade');

            $table->enum('type',['meeting','note','action'])->nullable();
            $table->string('document');
            $table->string('file_extension')->nullable();
            $table->string('file_name')->nullable();
            $table->string('uploading_file_name')->nullable();
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
        Schema::dropIfExists('meeting_documents');
    }
};
