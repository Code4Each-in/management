<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketEstimationApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
     public function up(): void
    {
        Schema::create('ticket_estimation_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id');   // no FK
            $table->unsignedBigInteger('approved_by'); // no FK
            $table->timestamp('approved_at')->useCurrent();
            $table->timestamps();

            // 🔒 one row per ticket → prevents duplicates
            $table->unique('ticket_id');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_estimation_approvals');
    }
}
