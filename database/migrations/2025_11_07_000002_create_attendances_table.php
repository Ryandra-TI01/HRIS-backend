<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jalankan migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->date('date')->index();
            $table->dateTime('check_in_time')->nullable();
            $table->dateTime('check_out_time')->nullable();
            $table->decimal('work_hour', 5, 2)->nullable()->comment('Jam kerja hasil perhitungan otomatis');
            $table->timestamps();

            $table->unique(['employee_id', 'date'], 'unique_employee_date');
        });
    }

    /**
     * Rollback migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
