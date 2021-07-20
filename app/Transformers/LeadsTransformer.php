<?php
namespace App\Transformers;

use App\Models\Customer;
use League\Fractal;


class LeadsTransformer extends Fractal\TransformerAbstract
{
	public function transform(Customer $customer)
	{
        return [
	        'title'   => 'title_123',
            'id' => $customer->id
	    ];
	}
}
