<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Cashier\Cashier;

final class ConfigureStripePortalCommand extends Command
{
    protected $signature   = 'stripe:configure-portal';
    protected $description = 'Disable subscription cancellation in the Stripe Customer Portal (reproducible across environments)';

    public function handle(): int
    {
        $stripe  = Cashier::stripe();
        $configs = $stripe->billingPortal->configurations->all(['is_default' => true, 'limit' => 1]);

        if ($configs->data !== []) {
            $configId = $configs->data[0]->id;

            if (! is_string($configId)) {
                $this->error('Unexpected non-string portal configuration ID returned by Stripe.');

                return self::FAILURE;
            }

            $stripe->billingPortal->configurations->update($configId, [
                'features' => [
                    'subscription_cancel' => ['enabled' => false],
                ],
            ]);
            $this->info("Updated portal configuration {$configId}: subscription_cancel disabled.");

            return self::SUCCESS;
        }

        $config = $stripe->billingPortal->configurations->create([
            'features'           => [
                'invoice_history'       => ['enabled' => true],
                'payment_method_update' => ['enabled' => true],
                'subscription_cancel'   => ['enabled' => false],
            ],
            'default_return_url' => route('teams.billing.show'),
        ]);
        $this->info("Created portal configuration {$config->id}: subscription_cancel disabled.");

        return self::SUCCESS;
    }
}
