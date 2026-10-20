<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Partner Monthly Customer Quota Target
    |--------------------------------------------------------------------------
    |
    | The advisory monthly customer-registration target a partner is measured
    | against (PartnerQuotaService). Advisory only in v1.1: this never blocks
    | customer creation. Override per-environment with the env var below.
    |
    */

    'partner_monthly_customer_target' => (int) env('PARTNER_MONTHLY_CUSTOMER_TARGET', 100),

];
