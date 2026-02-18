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
        Schema::create('lab_analyses', function (Blueprint $table) {
            $table->id();
            $table->string('analysis_number')->unique();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('analysis_date');
            $table->enum('status', ['draft', 'final'])->default('final');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['analysis_date', 'created_at']);
        });

        Schema::create('analysis_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->constrained('lab_analyses')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['analysis_id', 'category_id']);
        });

        Schema::create('analysis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')->constrained('lab_analyses')->cascadeOnDelete();
            $table->foreignId('lab_parameter_id')->constrained()->cascadeOnDelete();
            $table->string('result_value')->nullable();
            $table->decimal('result_numeric', 12, 3)->nullable();
            $table->boolean('is_abnormal')->default(false);
            $table->timestamps();

            $table->unique(['analysis_id', 'lab_parameter_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analysis_results');
        Schema::dropIfExists('analysis_category');
        Schema::dropIfExists('lab_analyses');
    }
};
