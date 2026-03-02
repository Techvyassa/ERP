<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create trigger for INSERT operations
        DB::connection('control')->unprepared('
            CREATE TRIGGER sync_active_subscriptions_insert
            AFTER INSERT ON org_subscriptions
            FOR EACH ROW
            BEGIN
                IF NEW.subscription_status IN ("ACTIVE", "TRIAL") THEN
                    INSERT INTO active_subscriptions (
                        org_id, subscription_id, plan_id, plan_code,
                        subscription_status, period_end_date, modules_allowed,
                        max_users, tenant_db_name, is_in_trial, refreshed_at
                    )
                    SELECT 
                        NEW.org_id, 
                        NEW.subscription_id, 
                        NEW.plan_id, 
                        sp.plan_code,
                        NEW.subscription_status, 
                        NEW.current_period_end, 
                        sp.modules_included,
                        sp.max_users, 
                        o.tenant_db_name, 
                        (NEW.subscription_status = "TRIAL"), 
                        NOW()
                    FROM subscription_plans sp
                    JOIN organizations o ON o.org_id = NEW.org_id
                    WHERE sp.plan_id = NEW.plan_id
                    ON DUPLICATE KEY UPDATE
                        subscription_id = NEW.subscription_id,
                        plan_id = NEW.plan_id,
                        plan_code = sp.plan_code,
                        subscription_status = NEW.subscription_status,
                        period_end_date = NEW.current_period_end,
                        modules_allowed = sp.modules_included,
                        max_users = sp.max_users,
                        is_in_trial = (NEW.subscription_status = "TRIAL"),
                        refreshed_at = NOW();
                END IF;
            END
        ');

        // Create trigger for UPDATE operations
        DB::connection('control')->unprepared('
            CREATE TRIGGER sync_active_subscriptions_update
            AFTER UPDATE ON org_subscriptions
            FOR EACH ROW
            BEGIN
                IF NEW.subscription_status IN ("ACTIVE", "TRIAL") THEN
                    -- Upsert active subscription
                    INSERT INTO active_subscriptions (
                        org_id, subscription_id, plan_id, plan_code,
                        subscription_status, period_end_date, modules_allowed,
                        max_users, tenant_db_name, is_in_trial, refreshed_at
                    )
                    SELECT 
                        NEW.org_id, 
                        NEW.subscription_id, 
                        NEW.plan_id, 
                        sp.plan_code,
                        NEW.subscription_status, 
                        NEW.current_period_end, 
                        sp.modules_included,
                        sp.max_users, 
                        o.tenant_db_name, 
                        (NEW.subscription_status = "TRIAL"), 
                        NOW()
                    FROM subscription_plans sp
                    JOIN organizations o ON o.org_id = NEW.org_id
                    WHERE sp.plan_id = NEW.plan_id
                    ON DUPLICATE KEY UPDATE
                        subscription_id = NEW.subscription_id,
                        plan_id = NEW.plan_id,
                        plan_code = sp.plan_code,
                        subscription_status = NEW.subscription_status,
                        period_end_date = NEW.current_period_end,
                        modules_allowed = sp.modules_included,
                        max_users = sp.max_users,
                        is_in_trial = (NEW.subscription_status = "TRIAL"),
                        refreshed_at = NOW();
                ELSEIF NEW.subscription_status IN ("EXPIRED", "CANCELLED") AND 
                       EXISTS (SELECT 1 FROM active_subscriptions WHERE org_id = NEW.org_id AND subscription_id = NEW.subscription_id) THEN
                    -- Delete if this was the active subscription
                    DELETE FROM active_subscriptions WHERE org_id = NEW.org_id AND subscription_id = NEW.subscription_id;
                END IF;
            END
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::connection('control')->unprepared('DROP TRIGGER IF EXISTS sync_active_subscriptions_insert');
        DB::connection('control')->unprepared('DROP TRIGGER IF EXISTS sync_active_subscriptions_update');
    }
};
