<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use TimoKoerber\LaravelOneTimeOperations\Models\Operation;
use TimoKoerber\LaravelOneTimeOperations\OneTimeOperationManager;

class CreateOneTimeOperationsTable extends Migration
{
    protected string $name;

    public function __construct()
    {
        $this->name = OneTimeOperationManager::getTableName();
    }

    public function up()
    {
        Schema::create($this->name, function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->enum('dispatched', [Operation::DISPATCHED_SYNC, Operation::DISPATCHED_ASYNC]);
            $table->timestamp('processed_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists($this->name);
    }
}
