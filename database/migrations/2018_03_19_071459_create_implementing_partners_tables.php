<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateImplementingPartnersTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agencies', function (Blueprint $table) {
          $table->increments('id');
          $table->string('name');
          $table->softDeletes();
          $table->timestamps();
        });

        Schema::create('implementing_partners', function (Blueprint $table) {
          $table->increments('id');
          $table->string('name');
          $table->integer('agency_id')->unsigned();
          $table->softDeletes();
          $table->timestamps();
        });

        Schema::create('county_implementing_partner', function (Blueprint $table) {
          $table->integer('implementing_partner_id')->unsigned();
          $table->integer('county_id')->unsigned();
        });

        Schema::table('users', function (Blueprint $table) {
          $table->integer('implementing_partner_id')->unsigned()->nullable();
        });

        \DB::disableQueryLog();
        \DB::unprepared(file_get_contents(base_path() . "/database/seeds/agencies.sql"));
        echo "agencies seeded!\n";
        \DB::unprepared(file_get_contents(base_path() . "/database/seeds/implementing_partners.sql"));
        echo "implementing partners seeded!\n";
        \DB::enableQueryLog();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('implementing_partner_user');
        Schema::drop('implementing_partner_county');
        Schema::drop('implementing_partners');
        Schema::drop('agencies');
    }
}