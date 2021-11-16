<?php

namespace Database\Factories;

use App\Models\RewardSlider;
use Illuminate\Database\Eloquent\Factories\Factory;

class RewardSliderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RewardSlider::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {

        return [
            'image' => 'googlelogo_light_color_272x92dp.png',
            'link' => $this->faker->url(),
            'sort_order' => $this->faker->randomNumber(2),
        ];
    }
}
