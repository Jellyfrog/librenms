<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_job_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('job')->index();
            $table->string('poller_name')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->index();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedBigInteger('duration_ms')->nullable();
            $table->string('status', 16);
            $table->text('exception')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_job_runs');
    }
};
