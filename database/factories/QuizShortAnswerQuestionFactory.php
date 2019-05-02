<?php


/* @var $factory \Illuminate\Database\Eloquent\Factory */


use Faker\Generator as Faker;

$factory->define(\App\QuizShortAnswerQuestion::class, function (Faker $faker) {
    return [
        'content' => $faker->paragraph($nbSentences = 15, $variableNbSentences = true)
    ];
});
