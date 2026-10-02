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
        Schema::table('investments', function (Blueprint $table) {
            if (!Schema::hasColumn('investments', 'viewed_by')) {
                $table->foreignId('viewed_by')->nullable()->after('type')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('investments', 'viewed_at')) {
                $table->timestamp('viewed_at')->nullable()->after('viewed_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investments', function (Blueprint $table) {
            if (Schema::hasColumn('investments', 'viewed_by')) {
                $table->dropForeign(['viewed_by']);
                $table->dropColumn('viewed_by');
            }
            if (Schema::hasColumn('investments', 'viewed_at')) {
                $table->dropColumn('viewed_at');
            }
        });
    }
};
