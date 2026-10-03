<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'secondary_contact')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('secondary_contact')->nullable()->after('primary_contact');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'secondary_contact')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('secondary_contact');
            });
        }
    }
};
