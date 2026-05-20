<?php

namespace App\Repositories;

use App\Enums\GenericRequestEnum;
use App\Enums\quoteTypeCode;
use App\Models\CustomerMembers;
use App\Models\HealthQuote;
use App\Models\Payment;
use App\Traits\AuditTransformLookupCache;
use Illuminate\Support\Facades\DB;
use OwenIt\Auditing\Models\Audit;

class AuditRepository extends BaseRepository
{
    private const API_MODEL_MAP = [
        'App\Models\HealthQuoteRequestMemberDetails' => CustomerMembers::class,
        'App\Models\HealthQuoteRequest' => HealthQuote::class,
    ];

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
            quoteTypeCode::Device,
            quoteTypeCode::CYBER,
        ];
        $quoteObject = (in_array(ucfirst(strtolower(request()->quote_type)), $lobs)) ? app('\\App\\Models\\'.ucfirst(strtolower(request()->quote_type)).'Quote') : app('\\App\\Models\\'.request()->quote_type);

        $auditables = $quoteObject->getAuditables();
        $auditableId = request()->has('auditable_id') && request()->auditable_id ? request()->input('auditable_id') : null;
        $quoteType = request()->has('quote_type') && request()->quote_type ? request()->input('quote_type') : null;
        $quoteTypeId = request()->has('quote_type_id') && request()->quote_type_id ? request()->input('quote_type_id') : null;
        $isSendUpdate = $quoteType === GenericRequestEnum::SEND_UPDATE_LOG;
        $payment = null;

        if ($auditableId) {
            if ($isSendUpdate) {
                $payment = Payment::select('id')
                    ->where('send_update_log_id', $auditableId)
                    ->first();
            } elseif (isset($auditables['auditable_type'])) {
                $payment = Payment::select('id')
                    ->where('paymentable_id', $auditableId)
                    ->where('paymentable_type', $auditables['auditable_type'])
                    ->first();
            }
        }

        $showParentAuditLogs = $auditables['show_auditables'] ?? true;

        $query = DB::table('audits')
            ->select('audits.*', 'users.name')
            ->leftJoin('users', 'audits.user_id', 'users.id')
            ->when($showParentAuditLogs, function ($q) use ($auditables, $payment, $auditableId, $quoteTypeId) {
                if ($auditableId && isset($auditables['auditable_type'])) {
                    $q->where(function ($q) use ($auditables, $auditableId) {
                        $q->where('auditable_id', $auditableId)
                            ->when(isset($auditables['api_auditable_type']), function ($q) use ($auditables) {
                                $q->where(function ($q) use ($auditables) {
                                    $q->where('auditable_type', $auditables['auditable_type'])
                                        ->orWhere('auditable_type', $auditables['api_auditable_type']);
                                });
                            })
                            ->when(! isset($auditables['api_auditable_type']), function ($q) use ($auditables) {
                                $q->where('auditable_type', $auditables['auditable_type']);
                            });
                    });
                }

                if ($payment) {
                    $q->orWhere(function ($q) use ($payment) {
                        $q->where('auditable_id', $payment->id)
                            ->where('auditable_type', Payment::class);
                    });
                }
                if ($quoteTypeId && isset($auditables['auditable_type'])) {
                    $q->where('auditable_type', $auditables['auditable_type'])
                        ->where('new_values->quote_type_id', $quoteTypeId);
                }
            });
        if (! empty($auditables['relations'])) {
            foreach ($auditables['relations'] as $relation) {
                $model = $relation['auditable_type'];
                $auditRelation = $relation['relation'] ?? 'one';
                if ($auditRelation == 'many') {
                    $childRecords = $model::where($relation['key'], $auditableId)->get();
                } else {
                    $record = $model::where($relation['key'], $auditableId)->first();
                    $childRecords = $record ? [$record] : [];
                }

                if ($childRecords) {
                    foreach ($childRecords as $record) {
                        $query->orWhere(function ($q) use ($relation, $record) {
                            $q->where('auditable_id', $record->id)
                                ->when(isset($relation['api_auditable_type']), function ($q) use ($relation) {
                                    $q->where(function ($q) use ($relation) {
                                        $q->where('auditable_type', $relation['auditable_type'])
                                            ->orWhere('auditable_type', $relation['api_auditable_type']);
                                    });
                                })
                                ->when(! isset($relation['api_auditable_type']), function ($q) use ($relation) {
                                    $q->where('auditable_type', $relation['auditable_type']);
                                });
                        });
                    }
                }
            }
        }
        $results = $query->orderBy('created_at', 'desc')->get();

        $auditTransformCacheActive = false;

        try {
            $results->transform(function ($audit) use ($quoteObject, &$auditTransformCacheActive) {
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

                $modelClass = self::API_MODEL_MAP[$audit->auditable_type] ?? $audit->auditable_type;
                $model = class_exists($modelClass) ? app($modelClass) : $quoteObject;
                if (method_exists($model, 'transformAuditables')) {
                    $auditTransformCacheActive = true;

                    $data = [
                        'audit' => $audit,
                        'transformedOld' => $transformedOld,
                        'transformedNew' => $transformedNew,
                    ];
                    $data = $model->transformAuditables($data);
                    $audit = $data['audit'];
                    $transformedOld = $data['transformedOld'];
                    $transformedNew = $data['transformedNew'];
                }

                $audit->new_values = json_encode($transformedNew);
                $audit->old_values = json_encode($transformedOld);

                return $audit;
            });
        } finally {
            if ($auditTransformCacheActive) {
                AuditTransformLookupCache::flush();
            }
        }

        return $results;
    }
}
