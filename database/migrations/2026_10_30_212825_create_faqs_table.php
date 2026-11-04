<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create the FAQ table and carry over the questions that were hard-coded
     * on the contact and sponsor-a-child pages, so those pages keep them.
     */
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('page', 40);
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['page', 'is_published', 'sort']);
        });

        $defaults = [
            'contact' => [
                ['Are you a registered charity?', 'Yes — the Dadaa Fifiawoto Nyamadi Foundation is a registered non-profit operating across Ghana, Togo, Benin, and the United States. We can share registration details on request.'],
                ['Can I donate goods instead of money?', 'Sometimes — it depends on the programme and current need. Reach out and let us know what you have; we\'ll either accept it or connect you with a partner who can.'],
                ['Do you offer internships or research placements?', 'We host a small number of placements every year for students and early-career professionals. Email the team with a short note about your interests and availability.'],
                ['How do I unsubscribe from newsletters?', 'Every newsletter has an unsubscribe link in the footer. You can also email the team and we\'ll remove you immediately.'],
            ],
            'sponsorship' => [
                ['How much does sponsorship cost?', 'Choose a suggested monthly pledge or enter an amount that works for you. Our team will confirm current needs and arrangements.'],
                ['Does submitting a request confirm sponsorship?', 'No. Your request starts a conversation with our team. No payment is taken and no recurring charge is set up on this website.'],
                ['Can I contact a child directly?', 'All communication is coordinated through the foundation to respect family privacy and safeguard children.'],
                ['Why are there only a few profiles?', 'Only approved introductions with recorded guardian consent appear here. Families may choose to keep their information private or withdraw consent.'],
            ],
        ];

        $now = now();
        foreach ($defaults as $page => $faqs) {
            foreach ($faqs as $sort => [$question, $answer]) {
                DB::table('faqs')->insert([
                    'page' => $page,
                    'question' => $question,
                    'answer' => $answer,
                    'is_published' => true,
                    'sort' => $sort,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
