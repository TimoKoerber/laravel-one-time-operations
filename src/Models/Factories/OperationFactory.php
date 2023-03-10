<?php

namespace TimoKoerber\LaravelOneTimeOperations\Models\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use TimoKoerber\LaravelOneTimeOperations\Models\Operation;

class OperationFactory extends Factory
{
    protected $model = Operation::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->date('Y_m_d_His').'_'.Str::snake($this->faker->words(3, true)),
            'dispatched' => Arr::random([Operation::DISPATCHED_ASYNC, Operation::DISPATCHED_ASYNC]),
            'processed_at' => $this->faker->date('Y-m-d H:i:s'),
        ];
    }
}
