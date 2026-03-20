<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCategoryBannerToEmailTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('category')->default('festival')->after('name');
            $table->string('banner_image')->nullable()->after('body');
        });
    }

    public function down()
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn(['category', 'banner_image']);
        });
    }
}
