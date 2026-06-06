<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_label', function (Blueprint $table) {
            $table->index('label_id');
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::table('contact_label', function (Blueprint $table) {
            $table->dropIndex(['label_id']);
            $table->dropIndex(['contact_id']);
        });
    }
};
