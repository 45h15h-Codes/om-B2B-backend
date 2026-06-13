<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('diamonds', function (Blueprint $table) {
            $table->string('customer_website_report_no')->nullable()->after('show_on_OM');
        });

        // Resolve existing records
        $diamonds = DB::table('diamonds')->get();
        $seen = [];
        foreach ($diamonds as $d) {
            $specs = json_decode($d->specifications, true) ?? [];
            $reportNo = isset($specs['report_no']) ? trim($specs['report_no']) : '';
            if ($d->show_on_OM && !empty($reportNo)) {
                if (in_array($reportNo, $seen)) {
                    // Duplicate found on Customer Website. Turn off visibility for this duplicate.
                    DB::table('diamonds')->where('id', $d->id)->update([
                        'show_on_OM' => false,
                        'customer_website_report_no' => null
                    ]);
                } else {
                    $seen[] = $reportNo;
                    DB::table('diamonds')->where('id', $d->id)->update([
                        'customer_website_report_no' => $reportNo
                    ]);
                }
            }
        }

        Schema::table('diamonds', function (Blueprint $table) {
            $table->unique('customer_website_report_no', 'unique_customer_report_no');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('diamonds', function (Blueprint $table) {
            $table->dropUnique('unique_customer_report_no');
            $table->dropColumn('customer_website_report_no');
        });
    }
};
