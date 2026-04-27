<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\CustomerModel;
use App\Models\CustomerTokenModel;

/**
 * MarkInactiveCustomers
 *
 * Marks customers as 'inactive' if they haven't made any API request
 * for 90+ days (tracked via last_active_at).
 *
 * IMPORTANT: This does NOT prevent login. Inactive customers can still
 * authenticate — any login re-activates them automatically.
 *
 * Schedule with a cron job (once daily is sufficient):
 *   0 2 * * * /usr/bin/php /path/to/spark customers:mark-inactive >> /dev/null 2>&1
 *
 * Run manually:
 *   php spark customers:mark-inactive
 */
class MarkInactiveCustomers extends BaseCommand
{
    protected $group       = 'Customers';
    protected $name        = 'customers:mark-inactive';
    protected $description = 'Marks customers inactive after 90 days of no activity. Does not affect login.';

    public function run(array $params): void
    {
        CLI::write('[customers:mark-inactive] Starting...', 'yellow');

        $customers = new CustomerModel();
        $tokens    = new CustomerTokenModel();

        // Mark inactive
        $marked = $customers->markInactiveAfter90Days();
        CLI::write("[customers:mark-inactive] Marked {$marked} customer(s) as inactive.", 'green');

        // Purge expired tokens while we're here
        $purged = $tokens->purgeExpired();
        CLI::write("[customers:mark-inactive] Purged {$purged} expired token(s).", 'green');

        CLI::write('[customers:mark-inactive] Done.', 'yellow');
    }
}
