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
        Schema::create('be_wrhb3syq97_care_applicants', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('name')->nullable(false);
            $table->string('father_name')->nullable(false);
            $table->string('familly')->nullable(false);
            $table->date('dob')->nullable(false);
            $table->string('address')->nullable(false);
            $table->string('mobile')->nullable(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('be_wrhb3syq97_care_applicants');
    }
};
