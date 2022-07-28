<?php

use App\Models\CarPlan;
use App\Models\InsuranceCompany;
use App\Models\InsuranceProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class ExportInsuranceCompaniesPlanDataFromExcel extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    private function exportRow($insuranceProviderModel, $row)
    {
        $insuranceCompany = InsuranceCompany::where('name', 'like', '%'.$row[1].'%')->orWhere('name', 'like', $row[1].'%')->orWhere('name', $row[1])->select('id', 'name')->first();
        if ($insuranceCompany) {
            $insuranceProviderModel->insurance_company_id = $insuranceCompany->id;
            $insuranceProviderModel->save();
        } else {
            $newInsuranceCompany = new InsuranceCompany;
            $newInsuranceCompany->name = $row[1];
            $newInsuranceCompany->is_active = 1;
            $newInsuranceCompany->created_by = 'muhammad.amjad@afia.ae';

            if ($newInsuranceCompany->save()) {
                $insuranceProviderModel->insurance_company_id = $newInsuranceCompany->id;
                $insuranceProviderModel->save();
            }
        }

        $carPlan = new CarPlan;
        $carPlan->text = $row[2];
        $carPlan->provider_id = $insuranceProviderModel->id;
        $carPlan->repair_type = $row[3];
        $carPlan->is_active = 0;
        $carPlan->insurance_type = $row[4];
        $carPlan->save();
    }

    public function up()
    {
        $loadded = [];
        $path = resource_path().'/ActiveInsurancePlans.xlsx';
        $rows = Excel::toArray($loadded, $path);
        for ($i = 1; $i < 50; $i++) {
            $row = $rows[0][$i];

            if ($row[1]) {
                $insuranceProviderModel = InsuranceProvider::where('text', 'like', '%'.$row[1].'%')->orWhere('text', 'like', $row[1].'%')->orWhere('text', $row[1])->select('text', 'id')->first();
                if ($insuranceProviderModel) {
                    $this->exportRow($insuranceProviderModel, $row);
                } else {
                    $insuranceProviderModel = new InsuranceProvider;
                    $insuranceProviderModel->text = $row[1];
                    $insuranceProviderModel->code = str_replace(' ', '', $row[1]);
                    $insuranceProviderModel->is_active = ($row[0] == 'Active') ? 1 : 0;
                    $insuranceProviderModel->save();
                    $this->exportRow($insuranceProviderModel, $row);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('excel', function (Blueprint $table) {
            //
        });
    }
}
