<?php

namespace App\Services;

use App\Models\User;
use Hidehalo\Nanoid\Client;

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
            if ($item->id == $recordId) {
                $recordName = $item->text ?? $item->name;
            }
        }

        return $recordName;
    }

    public function walkTree($userId)
    {
        $childUserIds = [];
        $childs = User::where('manager_id', $userId)->pluck('id');
        foreach ($childs as $child) {
            $nextChilds = User::where('manager_id', $child)->pluck('id');
            if (count($nextChilds) > 0) {
                $this->walkTree($child);
            }
            array_push($childUserIds, $child);
        }

        return $childUserIds;
    }

    public function generateUUID()
    {
        $client = new Client;
        $alphabets = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $nanoId = $client->formattedId($alphabets, 8);

        return $nanoId;
    }
}
