<?php

use App\Models\Application;
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
        Schema::table('be_wrhb3syq97_applications', function (Blueprint $table) {
            $table->enum('status', Application::$statuses)->default(Application::$statuses[0]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('be_wrhb3syq97_applications', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
