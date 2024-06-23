<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;

class CreateFailedOneTimeOperationsTable extends Migration
{
    protected string $name;

    public function __construct()
    {
        $this->name = 'failed_' . OneTimeOperationManager::getTableName();
    }

    public function up(): void
    {
        Schema::create($this->name, function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('connection')->nullable();
            $table->text('queue')->nullable();
            $table->json('exception')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists($this->name);
    }
}
