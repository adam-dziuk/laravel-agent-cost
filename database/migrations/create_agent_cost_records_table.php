<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('agent-cost.agent_tracking.table', 'agent_cost_records'), function (Blueprint $table) {
            $table->id();
            $table->string('agent')->index();
            $table->string('invocation_id')->nullable();
            $table->string('provider')->nullable();
            $table->string('model')->nullable();
            $table->decimal('cost', 16, 8);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('agent-cost.agent_tracking.table', 'agent_cost_records'));
    }
};
