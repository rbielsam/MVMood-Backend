<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('chat_user', function (Blueprint $table) {
            $table->timestamp('last_read_at')->nullable()->after('user_id');
        });

        DB::table('chat_user')->update(['last_read_at' => now()]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_user', function (Blueprint $table) {
            $table->dropColumn('last_read_at');
        });
    }
};
