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
        // Index on reactions.type — used in groupBy queries for reaction summaries
        Schema::table('reactions', function (Blueprint $table) {
            $table->index('type');
        });

        // Index on media.collection — filtered in avatar/post image lookups
        Schema::table('media', function (Blueprint $table) {
            $table->index('collection');
        });

        // Composite index on posts for user profile feeds ordered by date
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reactions', function (Blueprint $table) {
            $table->dropIndex(['type']);
        });

        Schema::table('media', function (Blueprint $table) {
            $table->dropIndex(['collection']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
        });
    }
};
