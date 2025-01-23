<?php

namespace App\Repositories;

use App\Enums\CustomerTypeEnum;
use App\Models\Entity;
use Illuminate\Support\Facades\DB;

class EntityRepository extends BaseRepository
{
    /**
     * @return string
     */
    public function model()
    {
        return Entity::class;
    }

    public function fetchUpdateEntityDetail($data)
    {
        $entityData = $data->only([
            'company_name',
            'company_address',
            'industry_type_code',
            'emirate_of_registration_id',
            'legal_structure',
            'country_of_corporation',
            'website',
            'id_expiry_date',
            // 'id_issuance_place',//todo: column not available in db
            'id_issuance_authority',
        ]);

        $entityData['id_type'] = $data->entity_id_type;
        $entityData['id_issuance_date'] = $data->entity_id_issuance_date;

        $entity = Entity::updateOrCreate(['trade_license_no' => $data->trade_license_no], $entityData);

        $entityId = $entity->id;

        $entity->update(['code' => CustomerTypeEnum::EntityShort.'-'.$entityId]);

        return $entity;
    }

}
