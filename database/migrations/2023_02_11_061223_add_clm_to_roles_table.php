<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClmToRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('se_name')->after('guard_name');
            $table->boolean('is_default')->default(0)->after('se_name')->comment('1:Set Default role');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropForeign(['top_most_parent_id']);
            $table->dropForeign(['user_type_id']);
            $table->dropColumn('top_most_parent_id');
            $table->dropColumn('user_type_id');
            $table->dropColumn('se_name');
            $table->dropColumn('entry_mode');
        });
    }
}
