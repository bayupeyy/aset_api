<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('asset_location_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignUuid('from_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignUuid('to_location_id')->constrained('locations');
            $table->text('reason')->nullable();
            $table->foreignUuid('moved_by')->constrained('users');
            $table->timestamp('moved_at');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('asset_location_history'); }
};
