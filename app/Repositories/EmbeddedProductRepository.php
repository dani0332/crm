<?php

namespace App\Repositories;

use App\Models\EmbeddedProduct;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class EmbeddedProductRepository extends BaseRepository
{
    public function model()
    {
        return EmbeddedProduct::class;
    }

    /**
     * get all dropdown options required for form
     *
     * @return array
     */
    public function fetchGetFormOptions()
    {
        return [
            'insuranceProviders' => InsuranceProviderRepository::getList(),
            'quoteTypes' => QuoteTypeRepository::getList(),
        ];
    }

    /**
     * @param $quoteType
     * @return mixed
     */
    public function fetchCreate($data)
    {
        return DB::transaction(function () use ($data) {
            $product = $this->create($data);

            $product->placements()->createMany($data['positions']);
            $product->prices()->createMany($data['pricings']);

            return $product;
        });
    }

    /**
     * @return mixed
     */
    public function fetchUpdate($id, $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->where('id', $id)->firstOrFail();

            $productData = Arr::only($data, ['insurance_provider_id', 'product_name', 'short_code', 'display_name', 'product_type',  'description',  'description2',  'commission_type',  'commission_value',  'email_template_id',  'company_documents', 'removel_confirmation', 'logic']);

            $product->update($productData);
            $product->placements()->delete();
            $product->placements()->createMany($data['positions']);

            $product->prices()->delete();
            $product->prices()->createMany($data['pricings']);

            return $product;
        });
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        return $this->where($column, $value)->with(['insuranceProvider', 'placements.quoteType', 'prices'])->firstOrFail();
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        return $this->with(['insuranceProvider'])->orderBy('created_at', 'desc')->simplePaginate();
    }
}
