<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_program_cards', function (Blueprint $table) {
            // Stores the optional dropdown button as JSON:
            // { "label": "View Services", "items": [{"title":"..","link":".."},...] }
            $table->json('dropdown_items')->nullable()->after('secondary_link');
        });
    }

    public function down(): void
    {
        Schema::table('landing_program_cards', function (Blueprint $table) {
            $table->dropColumn('dropdown_items');
        });
    }
};
