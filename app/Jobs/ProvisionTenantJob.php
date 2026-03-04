<?php

namespace App\Jobs;

use App\Contracts\TenantProvisioningService;
use App\Models\Control\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProvisionTenantJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $orgId,
        public ?array $userData = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(TenantProvisioningService $provisioningService): void
    {
        Log::info("ProvisionTenantJob started for org_id: {$this->orgId}");
        
        try {
            // Execute tenant provisioning with user data
            $result = $provisioningService->provisionTenant($this->orgId, $this->userData);
            
            if ($result->success) {
                Log::info("ProvisionTenantJob completed successfully for org_id: {$this->orgId}", [
                    'tenant_db_name' => $result->tenantDbName,
                    'steps' => $result->steps
                ]);
            } else {
                Log::error("ProvisionTenantJob failed for org_id: {$this->orgId}", [
                    'error' => $result->errorMessage,
                    'steps' => $result->steps
                ]);
                
                // Send admin notification (already handled in service)
                // Mark job as failed
                $this->fail(new \Exception($result->errorMessage));
            }
            
        } catch (\Exception $e) {
            Log::error("ProvisionTenantJob exception for org_id: {$this->orgId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("ProvisionTenantJob permanently failed for org_id: {$this->orgId}", [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
        
        // Send admin notification about permanent failure
        $this->sendFailureNotification($exception->getMessage());
    }

    /**
     * Send failure notification to administrators
     */
    private function sendFailureNotification(string $errorMessage): void
    {
        try {
            // TODO: Implement actual admin notification
            Log::critical("ADMIN ALERT: Tenant provisioning permanently failed", [
                'org_id' => $this->orgId,
                'error' => $errorMessage,
                'attempts' => $this->attempts()
            ]);
            
            // Uncomment when email is configured:
            // Mail::to(config('app.admin_email'))->send(
            //     new ProvisioningPermanentlyFailedEmail($this->orgId, $errorMessage)
            // );
            
        } catch (\Exception $e) {
            Log::error("Failed to send failure notification: {$e->getMessage()}");
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return int
     */
    public function backoff(): int
    {
        // Exponential backoff: 30s, 60s, 120s
        return 30 * $this->attempts();
    }
}
