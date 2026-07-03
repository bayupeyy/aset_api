<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('asset_status_log', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->string('old_status', 30);
            $table->string('new_status', 30);
            $table->text('notes')->nullable();
            $table->foreignUuid('changed_by')->constrained('users');
            $table->timestamp('changed_at');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('asset_status_log'); }
};
