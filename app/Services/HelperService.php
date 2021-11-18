<?php

namespace App\Services;


class HelperService extends BaseService
{
    protected $dropdownSourceService;
    public function __construct(DropdownSourceService $dropdownSourceService)
    {
        $this->dropdownSourceService = $dropdownSourceService;
    }

	public static function getDropdownSource($type, $recordId)
	{
        $data = $this->dropdownSourceService->getDropdownSource($type);
        $recordName = '';
        foreach ($data as $item) {
            if($item->id == $recordId){
                $recordName = $item->text ?? $item->name;
            }
        }
        return $recordName;
	}
}
