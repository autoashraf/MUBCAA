<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_workflow_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('membership_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step_number');
            $table->string('title');
            $table->text('description')->nullable();

            $table->unique(['membership_type_id', 'step_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_workflow_steps');
    }
};
