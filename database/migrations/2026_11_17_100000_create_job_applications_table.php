<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('company');
            $table->string('position');
            $table->string('job_url', 2048)->nullable();
            $table->string('location')->nullable();
            $table->string('work_mode', 20)->nullable();
            $table->string('salary_range')->nullable();
            $table->string('status', 20)->default('wishlist');
            $table->unsignedInteger('position_in_column')->default(0);
            $table->date('applied_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'position_in_column']);
        });

        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('type', 20);
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['job_application_id', 'scheduled_at']);
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('interview_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dateTime('remind_at');
            $table->string('message');
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['remind_at', 'queued_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('job_applications');
    }
};
