<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->timestamp('changed_at');

            $table->index(['job_application_id', 'changed_at']);
        });

        // Existing applications start their history in their current column.
        DB::table('status_changes')->insertUsing(
            ['job_application_id', 'from_status', 'to_status', 'changed_at'],
            DB::table('job_applications')->select([
                'id',
                DB::raw('null'),
                'status',
                DB::raw('coalesce(created_at, current_timestamp)'),
            ]),
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('status_changes');
    }
};
