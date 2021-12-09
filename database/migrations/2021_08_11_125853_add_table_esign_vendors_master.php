<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTableEsignVendorsMaster extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // adding new table esign_vendors_master if it not exists
        if(!Schema::hasTable('esign_vendors_master')){
            Schema::create('esign_vendors_master', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('client_code')->nullable()->comment('Client Code of SAMCO created at vendors end (if they have any)');
                $table->string('uat_domain_url')->nullable()->comment('UAT Domaint URL');
                $table->string('uat_api_key')->nullable()->comment('UAT API Key');
                $table->string('live_domain_url')->nullable()->comment('Live Domain URL');
                $table->string('live_api_key')->nullable()->comment('Live API Key');
                $table->string('callback_url_success')->nullable()->comment('where to redirect after success response');
                $table->string('callback_url_failure')->nullable()->comment('where to redirect after failure response');
                $table->tinyInteger('status')->nullable()->default(0)->comment('Status: 0=Inactive, 1=Active');
                $table->dateTime('created_at')->nullable()->useCurrent()->comment('Created Date');
                $table->dateTime('updated_at')->nullable()->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'))->comment('Modified Date');
                $table->index('status');
            });

            DB::statement("ALTER TABLE `esign_vendors_master` ADD INDEX `esign_vendors_master_name_index` (`name`(191));");

            // inserting few records
            $insert_data = array(
                array('name' => 'TRUECOPY', 'client_code' => '', 'uat_domain_url' => 'https://qasamcomf.truecopy.in', 'uat_api_key' => '3M6JJAPBDX5ZVJYX', 'live_domain_url' => 'https://samcomf.truecopy.in', 'live_api_key' => 'ADEXG6WQ5H2TRBGM', 'callback_url_success' => '/account_opening/aadhar_response', 'callback_url_failure' => '/account_opening/aadhar_response', 'status' => 0),
                array('name' => 'KHOSLA', 'client_code' => 'SAMC6765', 'uat_domain_url' => 'https://sandbox.veri5digital.com', 'uat_api_key' => '6534gfrefge6de', 'live_domain_url' => 'https://prod.aadhaarbridge.com', 'live_api_key' => '6534gfrefge6de', 'callback_url_success' => '/account_opening/aadhar_response', 'callback_url_failure' => '/account_opening/aadhar_response', 'status' => 1)
            );
            DB::table('esign_vendors_master')->insert($insert_data);
        }
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
