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
        
        return DB::transaction(function () use ( $data) {
            $product = $this->create($data);
          
            foreach($data['quote_type_ids'] as $key=>$value){
                $placementData[$key]['quote_type_id']=$value;
            }  foreach($data['positions'] as $key=>$value){
                $placementData[$key]['position']=$value;
            }
            $product->embeddedProductPlacement()->createMany($placementData);

            return $product;
        });
    }

    /**
     * @return mixed
     */
    public function fetchUpdate($id, $data)
    {
        $product = $this->where('id', $id)->firstOrFail();

        $productData = Arr::only($data, ['insurance_provider_id', 'product_name', 'short_code', 'display_name', 'product_type',  'description',  'description2',  'commission_type',  'commission_value',  'email_template_id',  'company_documents', 'removel_confirmation', 'logic']);

        $product->update($productData);

        return $product;
    }

    /**
     * @return mixed
     */
    public function fetchGetBy($column, $value)
    {
        return $this->where($column, $value)->with(['insuranceprovider'])->firstOrFail();
    }

    /**
     * @return mixed
     */
    public function fetchGetData()
    {
        return $this->with(['insuranceprovider'])->orderBy('created_at', 'desc')->simplePaginate();
    }
}
