<?php

namespace Database\Factories;

use App\Models\Model;
use Illuminate\Database\Eloquent\Factories\Factory;

class RewardsSliderFactory extends Factory
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
            $faker->image('images',400,300),
            'image' => $this->faker->image,
            'link' => $this->faker->link,
            'sort_order' => $this->faker->sort_order,
        ];
    }
}
