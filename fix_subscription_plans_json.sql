-- Fix double-encoded JSON in subscription_plans table
-- The modules_included field is currently stored as: "\"[\\\"PR\\\",\\\"PO\\\"]\""
-- It should be stored as: ["PR","PO"]

UPDATE subscription_plans 
SET modules_included = '["PR","PO","GRN","QC","INVOICE","PAYMENT","INVENTORY","REPORTS","USERS","SETTINGS"]'
WHERE plan_code = 'TRIAL';

UPDATE subscription_plans 
SET modules_included = '["PR","PO","GRN","QC","INVOICE","PAYMENT","INVENTORY","REPORTS","USERS","SETTINGS"]'
WHERE plan_code = 'BASIC';

UPDATE subscription_plans 
SET modules_included = '["PR","PO","GRN","QC","INVOICE","PAYMENT","INVENTORY","WAREHOUSE","REPORTS","SETTINGS"]'
WHERE plan_code = 'PROFESSIONAL';

UPDATE subscription_plans 
SET modules_included = '["PR","PO","GRN","QC","INVOICE","PAYMENT","INVENTORY","WAREHOUSE","REPORTS","SETTINGS"]'
WHERE plan_code = 'ENTERPRISE';

-- Verify the fix
SELECT plan_code, plan_name, modules_included FROM subscription_plans;
