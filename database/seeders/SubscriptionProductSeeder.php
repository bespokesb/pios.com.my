<?php

namespace Database\Seeders;

use App\Models\SubscriptionProduct;
use Illuminate\Database\Seeder;

class SubscriptionProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. BizGrowth Engine (BGE) - Default Mandatory Product
        SubscriptionProduct::updateOrCreate(
            ['code' => 'bge'],
            [
                'name' => 'BizGrowth Engine (BGE)',
                'description' => 'Sistem perisian BizGrowth Engine (BGE) teras untuk pengurusan produk harian, kawalan inventori, pesanan, pangkalan data pelanggan, dan laporan jualan.',
                'price' => 30.00,
                'period_days' => 30,
                'target_role' => 'all', // Wajib untuk Partner & Business Owner
                'is_mandatory' => true,
                'is_active' => true,
                'sort_order' => 1,
                'payment_instructions' => "Sila buat bayaran RM30.00 melalui QR DuitNow atau Pindahan Bank:\nBank: Maybank\nNo. Akaun: 5621-0678-9012\nNama Akaun: Pak Itam Online Supermarket Sdn Bhd\n\nSelepas membuat bayaran, sila muat naik resit/slip pembayaran untuk pengesahan pantas.",
            ]
        );

        // 2. Partner Fee - Exclusively for Partners (Disabled by default per requirement)
        SubscriptionProduct::updateOrCreate(
            ['code' => 'partner_fee'],
            [
                'name' => 'Partner Fee',
                'description' => 'Yuran kelayakan rakan kongsi eksklusif PIOS Partner bagi pengurusan rangkaian Business Owner & pengagihan komisen.',
                'price' => 50.00,
                'period_days' => 30,
                'target_role' => 'partner', // Hanya effect partner sahaja
                'is_mandatory' => false,
                'is_active' => false, // Buat tapi disablekan dulu
                'sort_order' => 2,
                'payment_instructions' => 'Bayaran yuran rakan kongsi terus kepada PIOS Owner melalui QR DuitNow atau Pindahan Bank.',
            ]
        );
    }
}
