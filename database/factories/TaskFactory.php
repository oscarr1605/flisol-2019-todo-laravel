<?php

/* @var $factory \Illuminate\Database\Eloquent\Factory */

use App\Task;
use Faker\Generator as Faker;

$factory->define(Task::class, function (Faker $faker) {
    return [
        'name' => $faker->sentence(10),
        'completed' => $faker->boolean,
        'user_id' => factory(App\User::class)->create()->id
    ];
});
