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
        Schema::create('be_wrhb3syq97_care_applications', function (Blueprint $table) {
            $table->id();
            $table->integer('applicant_id')->unsigned()->unique();
            $table->string('media_link')->nullable(false);
            $table->string('help_type')->nullable(false);
            $table->string('treatment_cost')->nullable(false);
            $table->string('dr_name')->nullable(false);
            $table->string('dr_mobile')->nullable(false);
            $table->string('hospital_name')->nullable(false);
            $table->string('family_members_working')->nullable(false);
            $table->string('work_type')->nullable(false);
            $table->string('household_income')->nullable(false);
            $table->string('old_program')->nullable(false);
            $table->string('old_program_details')->nullable(false);
            $table->longText('social_health_situation')->nullable(false);
            $table->json('extra_fields');
            $table->timestamps();

            $table->foreign('applicant_id')
                ->references('id')
                ->on('be_wrhb3syq97_care_applicants')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('be_wrhb3syq97_care_applications');
    }
};
