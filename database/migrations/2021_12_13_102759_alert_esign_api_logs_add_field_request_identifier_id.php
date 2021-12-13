<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlertEsignApiLogsAddFieldRequestIdentifierId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('esign_api_logs', function (Blueprint $table) {
            $table->string('identifier_id')->nullable()->comment('Identifier id: for vendor KHOSLA it is uploaded document id, for vendor TRUECOPY it is uuid')->after('api_name');
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
        Schema::table('esign_api_logs', function (Blueprint $table) {
            $table->dropColumn(['identifier_id']);
        });
    }
}
