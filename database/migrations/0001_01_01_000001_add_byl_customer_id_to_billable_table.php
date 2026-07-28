<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table($this->table(), function (Blueprint $table) {
            $table->unsignedBigInteger('byl_customer_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        // SQLite баганыг устгахаас өмнө индексийг салангид салгах шаардлагатай.
        Schema::table($this->table(), function (Blueprint $table) {
            $table->dropIndex($this->table().'_byl_customer_id_index');
        });

        Schema::table($this->table(), function (Blueprint $table) {
            $table->dropColumn('byl_customer_id');
        });
    }

    private function table(): string
    {
        return config('byl.billable.table', 'users');
    }
};
