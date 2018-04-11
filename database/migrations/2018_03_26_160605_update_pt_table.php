<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdatePtTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pt', function(Blueprint $table)
        {
            $table->integer('approved_by')->nullable()->after('verified_by');
            $table->string('approved_comment', 250)->nullable()->after('verified_by');
        }); 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pt', function (Blueprint $table) {
            $table->dropColumn('approved_by');
            $table->dropColumn('approved_comment');
        });  
    }
}
