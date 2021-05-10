<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpcomingApps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('upcoming_apps', function(Blueprint $table){
            $table->id();
            $table->string('app_id');
            $table->string('app_version');
            $table->string('os');
            $table->text('description');
            $table->string('added_by')->nullable(); // admin_id
            $table->timestamp('proposed_release_date');
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
        //
    }
}
