<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('test_section_id')->constrained()->onDelete('cascade');
            $table->enum('type', [
                'multiple_choice',
                'true_false_notgiven',
                'yes_no_notgiven',
                'matching_headings',
                'matching_features',
                'sentence_completion',
                'summary_completion',
                'diagram_label',
                'short_answer',
                'form_completion',
                'map_plan_labeling',
                'writing'
            ]);
            $table->text('question_text');
            $table->json('options')->nullable();
            $table->json('correct_answers');
            $table->integer('points')->default(1);
            $table->integer('order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
