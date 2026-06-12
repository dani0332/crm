<?php

namespace Database\Seeders;

use App\Models\HealthGroupNationality;
use App\Models\HealthNationalityGroup;
use Illuminate\Database\Seeder;

class HealthGroupNationalitySeeder extends Seeder
{
    public function run(): void
    {
        // Get nationality groups
        $groups = HealthNationalityGroup::get();
        $this->fillHealthGroupNationality($groups);
    }

    private function fillHealthGroupNationality($groups): void
    {
        $data = [];

        foreach ($groups as $group) {
            switch ($group->group_code) {
                case 'GCC':
                    $canonicalCodes = ['CN0015', 'CN0062', 'CN0104', 'CN0137', 'CN0147', 'CN0155'];
                    $data = $this->prepareData($group->id, $data, $canonicalCodes);
                    break;
                case 'SUBCONT':
                    $canonicalCodes = ['CN0001', 'CN0017', 'CN0088', 'CN0130', 'CN0138', 'CN0167'];
                    $data = $this->prepareData($group->id, $data, $canonicalCodes);
                    break;
                case 'ASIA':
                    $canonicalCodes = ['CN0001', 'CN0011', 'CN0014', 'CN0015', 'CN0017', 'CN0027', 'CN0036', 'CN0038', 'CN0046', 'CN0055', 'CN0061', 'CN0062', 'CN0068', 'CN0073', 'CN0085', 'CN0088', 'CN0089', 'CN0090', 'CN0091', 'CN0093', 'CN0097', 'CN0098', 'CN0099', 'CN0102', 'CN0104', 'CN0105', 'CN0107', 'CN0115', 'CN0116', 'CN0125', 'CN0130', 'CN0137', 'CN0138', 'CN0139', 'CN0140', 'CN0147', 'CN0155', 'CN0159', 'CN0167', 'CN0171', 'CN0172', 'CN0173', 'CN0175', 'CN0180', 'CN0184', 'CN0186', 'CN0190'];
                    $data = $this->prepareData($group->id, $data, $canonicalCodes);
                    break;
                case 'EURO':
                    $canonicalCodes = ['CN0002', 'CN0005', 'CN0013', 'CN0021', 'CN0022', 'CN0029', 'CN0032', 'CN0034', 'CN0053', 'CN0055', 'CN0056', 'CN0057', 'CN0059', 'CN0064', 'CN0066', 'CN0069', 'CN0070', 'CN0074', 'CN0076', 'CN0077', 'CN0086', 'CN0087', 'CN0092', 'CN0094', 'CN0103', 'CN0106', 'CN0110', 'CN0111', 'CN0112', 'CN0123', 'CN0124', 'CN0126', 'CN0136', 'CN0145', 'CN0146', 'CN0149', 'CN0150', 'CN0157', 'CN0160', 'CN0161', 'CN0166', 'CN0169', 'CN0170', 'CN0179', 'CN0182'];
                    $data = $this->prepareData($group->id, $data, $canonicalCodes);
                    break;
                case 'AFR':
                    $canonicalCodes = ['CN0003', 'CN0006', 'CN0020', 'CN0024', 'CN0030', 'CN0037', 'CN0039', 'CN0043', 'CN0044', 'CN0050', 'CN0051', 'CN0058', 'CN0061', 'CN0063', 'CN0065', 'CN0071', 'CN0072', 'CN0075', 'CN0081', 'CN0095', 'CN0100', 'CN0108', 'CN0109', 'CN0113', 'CN0114', 'CN0117', 'CN0119', 'CN0120', 'CN0127', 'CN0128', 'CN0129', 'CN0134', 'CN0135', 'CN0151', 'CN0156', 'CN0047', 'CN0158', 'CN0162', 'CN0163', 'CN0165', 'CN0168', 'CN0174', 'CN0176', 'CN0178', 'CN0181', 'CN0189', 'CN0191', 'CN0192'];
                    $data = $this->prepareData($group->id, $data, $canonicalCodes);
                    break;
                case 'NAM':
                    $canonicalCodes = ['CN0004', 'CN0040', 'CN0121'];
                    $data = $this->prepareData($group->id, $data, $canonicalCodes);
                    break;
                case 'SAM':
                    $canonicalCodes = ['CN0010', 'CN0028', 'CN0031', 'CN0045', 'CN0049', 'CN0060', 'CN0082', 'CN0143', 'CN0144', 'CN0183', 'CN0185'];
                    $data = $this->prepareData($group->id, $data, $canonicalCodes);
                default:
                    break;
            }
        }

        HealthGroupNationality::upsert(
            $data,
            ['health_nationality_group_id', 'canonical_nationality_code'], // unique keys
            ['is_active', 'effective_from', 'effective_to'] // columns to update
        );
    }

    private function prepareData($healthNationalityGroupId, $data, $codes): array
    {
        foreach ($codes as $canonicalCode) {
            $data[] = [
                'health_nationality_group_id' => $healthNationalityGroupId,
                'canonical_nationality_code' => $canonicalCode,
                'is_active' => true,
                'effective_from' => '2026-03-02',
                'effective_to' => '2099-12-31',
            ];
        }

        return $data;
    }
}
