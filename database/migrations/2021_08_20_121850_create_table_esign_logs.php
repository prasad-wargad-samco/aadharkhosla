<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableEsignLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('esign_api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('arn', 100)->nullable()->comment('ARN number');
            $table->string('esign_vendor', 100)->nullable()->comment('esign request send to vendor');
            $table->string('api_name', 20)->nullable()->comment('API used');
            $table->text('request')->nullable()->comment('Request');
            $table->text('response')->nullable()->comment('Response');
            $table->tinyInteger('status')->nullable()->default(1)->comment('Status: 0=Inactive, 1=Active');
            $table->dateTime('created_at')->nullable()->useCurrent()->comment('Created Date');
            $table->dateTime('updated_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('Modified Date');
            $table->index('arn');
            $table->index('esign_vendor');
            $table->index('api_name');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('esign_api_logs');
    }
}
