<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('code_prefix', 10)->unique();
            $table->string('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('asset_categories'); }
};
