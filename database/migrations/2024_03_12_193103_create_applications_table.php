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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->integer('student_id')->unsigned()->unique();
            $table->string('dr_email')->nullable(false);
            $table->string('media_link')->nullable(false);
            $table->string('school_name_address')->nullable(false);
            $table->string('study_specialty')->nullable(false);
            $table->string('current_study_year')->nullable(false);
            $table->string('study_avg')->nullable(false);
            $table->string('tuition_cost_semester')->nullable(false);
            $table->string('tuition_cost_year')->nullable(false);
            $table->string('father_work')->nullable(false);
            $table->string('mother_work')->nullable(false);
            $table->string('family_members')->nullable(false);
            $table->string('anyone_working')->nullable(false);
            $table->string('household_income')->nullable(false);
            $table->string('terms')->nullable(false);
            $table->timestamps();

            $table->foreign('student_id')
                ->references('id')
                ->on('students')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
