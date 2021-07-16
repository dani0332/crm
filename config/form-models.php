<?php
return [
    'car_quote_request' => [
        'model' => CarQuote::class,
        'relation' => [
            'car_make_id' => [
                'model' => CarMake::class,
                'where' => ['id', 'car_make_id']
            ],
            'car_model_id' => [
                'model' => CarModel::class,
                'where' => ['id', 'car_model_id']
            ],
            'emirate_of_registration_id' => [
                'model' => Emirate::class,
                'where' => ['id', 'emirate_of_registration_id']
            ]
        ]
    ],
    'car_model' => [
        'model' => CarModel::class,
    ]
];
