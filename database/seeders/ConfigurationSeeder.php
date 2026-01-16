<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuration;

class ConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        Configuration::firstOrCreate(['key' => 'DAILY_TRANSFER_LIMIT_TRY'], ['value' => '50000', 'description' => 'Daily transfer limit in TRY']);
        Configuration::firstOrCreate(['key' => 'DAILY_WITHDRAW_LIMIT_EUR'], ['value' => '50000', 'description' => 'Daily withdraw limit in EUR']);
        Configuration::firstOrCreate(['key' => 'DAILY_WITHDRAW_LIMIT_USD'], ['value' => '50000', 'description' => 'Daily withdraw limit in USD']);

        // Fraud Check Configurations
        Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_VELOCITY_WINDOW_MINUTES'], ['value' => '60', 'description' => 'Fraud check velocity window in minutes']);
        Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_VELOCITY_LIMIT'], ['value' => '4', 'description' => 'Fraud check velocity transfer limit']);
        Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_NIGHT_START_HOUR'], ['value' => '2', 'description' => 'Fraud check night start hour']);
        Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_NIGHT_END_HOUR'], ['value' => '6', 'description' => 'Fraud check night end hour']);
        Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_NIGHT_AMOUNT_LIMIT'], ['value' => '5000', 'description' => 'Fraud check night amount limit']);
        Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_NEW_ACCOUNT_DAYS'], ['value' => '7', 'description' => 'Fraud check new account days']);
        Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_NEW_ACCOUNT_AMOUNT_LIMIT'], ['value' => '10000', 'description' => 'Fraud check new account amount limit']);
        Configuration::firstOrCreate(['key' => 'FRAUD_CHECK_IP_WINDOW_MINUTES'], ['value' => '1440', 'description' => 'Fraud check IP window in minutes']);

        // Fee Configurations
        Configuration::firstOrCreate(['key' => 'FEE_THRESHOLD_LOW'], ['value' => '1000', 'description' => 'Fee threshold low amount']);
        Configuration::firstOrCreate(['key' => 'FEE_THRESHOLD_MEDIUM'], ['value' => '10000', 'description' => 'Fee threshold medium amount']);
        Configuration::firstOrCreate(['key' => 'FEE_LOW_FIXED'], ['value' => '2.0', 'description' => 'Fee low fixed amount']);
        Configuration::firstOrCreate(['key' => 'FEE_MEDIUM_RATE'], ['value' => '0.005', 'description' => 'Fee medium rate']);
        Configuration::firstOrCreate(['key' => 'FEE_HIGH_BASE_FEE'], ['value' => '2.0', 'description' => 'Fee high base fee']);
        Configuration::firstOrCreate(['key' => 'FEE_HIGH_RATE'], ['value' => '0.003', 'description' => 'Fee high rate']);
    }
}
