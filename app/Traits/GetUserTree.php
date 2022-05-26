<?php
namespace App\Traits;
use App\Models\User;

trait GetUserTree {

    function walkTree ($userId) {
        $childUserIds = [];
        $childs = User::where('manager_id', $userId)->pluck('id');
        foreach ($childs as $child) {
            $nextChilds = User::where('manager_id', $child)->pluck('id');
            if(count($nextChilds) > 0) {
                walkTree($child);
            }
            array_push($childUserIds, $child);
        }
        array_push($childUserIds, $userId);
        return $childUserIds;
    }

   public static function StaticWalkTree ($userId) {
        $childUserIds = [];
        $childs = User::where('manager_id', $userId)->pluck('id');
        foreach ($childs as $child) {
            $nextChilds = User::where('manager_id', $child)->pluck('id');
            if(count($nextChilds) > 0) {
                walkTree($child);
            }
            array_push($childUserIds, $child);
        }
        array_push($childUserIds, $userId);
        return $childUserIds;
    }
}
