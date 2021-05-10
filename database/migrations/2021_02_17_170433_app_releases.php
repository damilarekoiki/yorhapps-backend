<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AppReleases extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('app_releases', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('app_id');
            $table->text('description')->nullable();
            $table->string('app_version');
            $table->string('os');
            $table->string('os_versions');
            $table->string('bit_types');
            $table->string('icon_path');
            $table->string('installer_file_path')->unique();
            $table->string('added_by')->nullable(); // admin_id
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
