<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * OAuth tokens for the read-only Upwork API, encrypted with APP_KEY. The API
 * key itself (client id/secret) stays in .env. Additive only.
 */
class CreateUpworkApiCredentialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('upwork_api_credentials', function (Blueprint $table) {
            $table->id();
            $table->string('label', 50)->unique();         // 'default'
            $table->text('access_token')->nullable();      // encrypted
            $table->text('refresh_token')->nullable();     // encrypted
            $table->string('token_type', 20)->nullable();
            $table->string('tenant_id', 100)->nullable();  // X-Upwork-API-TenantId (agency organization)
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('refresh_expires_at')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->unsignedBigInteger('connected_by')->nullable();
            $table->string('last_error', 500)->nullable();
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
        Schema::dropIfExists('upwork_api_credentials');
    }
}
