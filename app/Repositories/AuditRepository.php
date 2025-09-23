<?php

namespace App\Repositories;

use App\Enums\quoteTypeCode;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

class AuditRepository extends BaseRepository
{
    public function model()
    {
        return Audit::class;
    }

    public function fetchGetQuoteAudits()
    {

        $lobs = [
            quoteTypeCode::Health,
            quoteTypeCode::Car,
            quoteTypeCode::Travel,
            quoteTypeCode::Home,
            quoteTypeCode::Life,
            quoteTypeCode::Pet,
            quoteTypeCode::CORPLINE,
            quoteTypeCode::Business,
            quoteTypeCode::Cycle,
            quoteTypeCode::Bike,
            quoteTypeCode::Yacht,
            quoteTypeCode::Jetski,
            quoteTypeCode::SAVINGS,
        ];
        $quoteObject = (in_array(ucfirst(strtolower(request()->quote_type)), $lobs)) ? app('\\App\\Models\\'.ucfirst(strtolower(request()->quote_type)).'Quote') : app('\\App\\Models\\'.request()->quote_type);

        $auditables = $quoteObject->getAuditables();
        $code = isset(request()->code) ? request()->code : '';
        $auditableTypes = ['App\Models\Payment', 'App\Models\PaymentSplits'];
        $showParentAuditLogs = $auditables['show_auditables'] ?? true;

        $query = DB::table('audits')
            ->select('audits.*', 'users.name')
            ->leftJoin('users', 'audits.user_id', 'users.id')
            ->when($showParentAuditLogs, function ($q) use ($auditables) {
                if (request()->has('auditable_id') && request()->auditable_id) {
                    $q->where('auditable_id', request()->auditable_id)->where('auditable_type', $auditables['auditable_type']);
                }
                if (request()->has('quote_type_id') && request()->quote_type_id) {
                    $q->where('auditable_type', $auditables['auditable_type'])
                        ->where('new_values->quote_type_id', request()->quote_type_id);
                }
            });
        /*if ($code != '') {
            $query->orWhere(function ($query) use ($code, $auditableTypes) {
                $query->where('old_values', 'like', '%"code":"'.$code.'"%')
                    ->whereIn('auditable_type', $auditableTypes);
            });
        }*/
        if (! empty($auditables['relations'])) {
            foreach ($auditables['relations'] as $relation) {
                $model = $relation['auditable_type'];
                $auditRelation = $relation['relation'] ?? 'one';
                if($auditRelation == 'many') {
                    $childRecords = $model::where($relation['key'], request()->auditable_id)->get();
                } else {
                    $childRecords = [$model::where($relation['key'], request()->auditable_id)->first()];
                }

                if ($childRecords) {
                    foreach ($childRecords as $record) {
                        $query->orWhere(function ($q) use ($relation, $record) {
                            $q->where('auditable_type', $relation['auditable_type'])->where('auditable_id', $record->id);
                        });
                    }
                }
            }
        }
        $results = $query->orderBy('created_at', 'desc')->get();

        $results->transform(function ($audit) use ($quoteObject) {
            $newValues = json_decode($audit->new_values, true) ?? [];
            $oldValues = json_decode($audit->old_values, true) ?? [];

            $fieldMap = [
                'pcp_tag' => 'Private Client',
                'pc_qualified' => 'PC-Qualified',
            ];

            $transformValue = function ($value) {
                if ($value === 1 || $value === true) {
                    return 'Yes';
                } elseif ($value === 0 || $value === false) {
                    return 'Ex-PC';
                } elseif (is_null($value)) {
                    return 'No';
                }

                return $value;
            };

            // Extract and transform profiles from config
            $extractProfiles = function ($values) {
                if (isset($values['config'])) {
                    $config = json_decode($values['config'], true);
                    if (isset($config['profiles']) && is_array($config['profiles'])) {
                        $simplifiedProfiles = [];
                        foreach ($config['profiles'] as $profile) {
                            $simplifiedProfile = [
                                'nationalityIds' => $profile['nationalityIds'] ?? [],
                                'isDefaultCriteria' => $profile['isDefaultCriteria'] ?? false,
                            ];

                            // Extract criteria fields
                            $criteria = [];
                            foreach ($profile as $key => $value) {
                                if (is_array($value) && isset($value['label']) && isset($value['value'])) {
                                    $criteria[$value['label']] = $value['value'];
                                }
                            }
                            $simplifiedProfile['criteria'] = $criteria;

                            $simplifiedProfiles[] = $simplifiedProfile;
                        }
                        $values['profiles'] = $simplifiedProfiles;
                        unset($values['config']); // Remove the complex config
                    }
                }

                return $values;
            };

            $transformedNew = [];
            foreach ($newValues as $key => $value) {
                if (isset($fieldMap[$key])) {
                    $transformedNew[$fieldMap[$key]] = $transformValue($value);
                } else {
                    $transformedNew[$key] = $value;
                }
            }
            $transformedNew = $extractProfiles($transformedNew);

            $transformedOld = [];
            foreach ($oldValues as $key => $value) {
                if (isset($fieldMap[$key])) {
                    $transformedOld[$fieldMap[$key]] = $transformValue($value);
                } else {
                    $transformedOld[$key] = $value;
                }
            }
            $transformedOld = $extractProfiles($transformedOld);

            if (request()->quote_type == 'UserBranch') {
                $data = [
                    'audit' => $audit,
                    'transformedOld' => $transformedOld,
                    'transformedNew' => $transformedNew
                ];
                $data = $quoteObject->transformAuditables($data);
                $audit = $data['audit'];
                $transformedOld = $data['transformedOld'];
                $transformedNew = $data['transformedNew'];
            }

            $audit->new_values = json_encode($transformedNew);
            $audit->old_values = json_encode($transformedOld);

            return $audit;
        });

        return $results;
    }
}
