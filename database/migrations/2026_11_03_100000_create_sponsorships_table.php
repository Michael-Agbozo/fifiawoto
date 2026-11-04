<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sponsorships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('beneficiary_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sponsor_name', 130);
            $table->string('sponsor_email');
            $table->string('sponsor_phone', 40)->nullable();
            $table->string('country', 80)->nullable();
            $table->text('mailing_address')->nullable();
            $table->unsignedInteger('monthly_amount_cents');
            $table->unsignedInteger('one_time_gift_cents')->default(0);
            $table->string('currency', 3);
            $table->string('status', 20)->default('pending')->index();
            $table->date('started_on')->nullable();
            $table->date('next_due_on')->nullable()->index();
            $table->date('ended_on')->nullable();
            $table->boolean('reminders_enabled')->default(true);
            $table->date('due_reminder_sent_for')->nullable();
            $table->date('overdue_reminder_sent_for')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['beneficiary_id', 'status']);
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->foreignId('sponsorship_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('months_covered')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sponsorship_id');
            $table->dropColumn('months_covered');
        });

        Schema::dropIfExists('sponsorships');
    }
};
