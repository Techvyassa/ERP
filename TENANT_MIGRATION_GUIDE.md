# Tenant Migration Guide

## Command Usage

### Basic Migration
```bash
php artisan tenant:migrate {org_slug}
```

### Fresh Migration (Drop all tables and re-run)
```bash
php artisan tenant:migrate {org_slug} --fresh
```

### Migration with Seeding
```bash
php artisan tenant:migrate {org_slug} --seed
```

### Fresh Migration with Seeding
```bash
php artisan tenant:migrate {org_slug} --fresh --seed
```

## Available Organizations

| Org ID | Org Slug | Organization Name | Database |
|--------|----------|-------------------|----------|
| 5 | demo-manufacturing | Demo Manufacturing Pvt Ltd | erp_demo-manufacturing |
| 6 | tech-vyassa | Tech Vyassa Pvt Ltd | erp_tech-vyassa |
| 9 | nextgen-plastics | NextGen Plastics India Pvt Ltd | erp_nextgen-plastics |
| 10 | skyline-engineering-001 | Skyline Engineering Solutions Pvt Ltd | erp_skyline-engineering-001 |
| 11 | skyline-engineering-002 | Skyline Engineering second Solutions Pvt Ltd | erp_skyline-engineering-002 |
| 12 | sky-engineering | Sky Engineering Solutions Pvt Ltd | erp_sky-engineering |
| 13 | vishu | vishu's Organization | erp_vishu |
| 14 | amit-organization | Amit organization | erp_amit-organization |

## Examples

### Run migrations for amit-organization
```bash
php artisan tenant:migrate amit-organization
```

### Fresh migration for demo-manufacturing
```bash
php artisan tenant:migrate demo-manufacturing --fresh
```

### Migration with seeding for tech-vyassa
```bash
php artisan tenant:migrate tech-vyassa --seed
```

## Migration Files Location

All tenant migrations are located in:
```
database/migrations/tenant/
```

## Migration Order

Migrations run in this order (based on filename):

1. `2024_01_01_000001_create_department_master_table.php`
2. `2024_01_01_000002_create_role_master_table.php`
3. `2024_01_01_000003_create_role_permissions_table.php`
4. `2024_01_01_000004_update_users_table.php`
5. `2024_01_01_000005_create_zone_master_table.php`
6. `2024_01_01_000006_create_approval_matrix_master_table.php`
7. `2024_01_01_000007_create_uom_master_table.php`
8. `2024_01_01_000008_create_hsn_codes_table.php`
9. `2024_01_01_000009_create_gst_taxes_table.php`
10. `2024_01_01_000010_create_currency_master_table.php`
11. `2024_01_01_000011_create_warehouse_master_table.php`
12. `2024_01_01_000012_create_material_master_table.php`
13. `2024_01_01_000013_create_product_master_table.php`
14. `2024_01_01_000014_create_bin_locations_table.php`
15. `2024_01_01_000015_create_vendor_master_table.php`
16. `2024_01_01_000016_create_vendor_contacts_table.php`
17. `2024_01_01_000017_create_vendor_material_map_table.php`
18. `2024_01_01_000018_create_bom_header_table.php`
19. `2024_01_01_000019_create_bom_detail_table.php`

## Checking Migration Status

### Check which migrations have run
```bash
php artisan tinker
>>> DB::connection('tenant')->table('migrations')->get();
```

### Check if a table exists
```bash
php artisan tinker
>>> Schema::connection('tenant')->hasTable('material_master');
```

## Troubleshooting

### Error: "Organization not found"
- Check the org_slug is correct
- Use `php artisan tinker` to list organizations:
  ```php
  App\Models\Control\Organization::pluck('org_slug');
  ```

### Error: "Duplicate foreign key constraint"
- The migration has already been run
- Use `--fresh` to drop and recreate all tables
- Or manually drop the constraint before re-running

### Error: "Table already exists"
- The table was created in a previous migration
- Use `--fresh` to start clean
- Or skip the migration if data exists

### Error: "Unknown database"
- The tenant database doesn't exist
- Run tenant provisioning first:
  ```bash
  php artisan tenant:provision {org_id}
  ```

## Rolling Back Migrations

### Rollback last batch
```bash
# Not directly supported by tenant:migrate
# Use tinker to rollback manually:
php artisan tinker
>>> DB::connection('tenant')->table('migrations')->latest()->delete();
```

### Drop all tables (Fresh migration)
```bash
php artisan tenant:migrate {org_slug} --fresh
```

## Seeding After Migration

### Default seeders run with --seed flag:
1. DefaultRoleSeeder - Creates default roles (ADMIN, USER, etc.)
2. DefaultRolePermissionSeeder - Sets up default permissions

### Manual seeding:
```bash
php artisan db:seed --database=tenant --class=Database\\Seeders\\Tenant\\DefaultRoleSeeder
```

## Best Practices

1. **Always backup before --fresh**
   ```bash
   mysqldump -u user -p erp_amit-organization > backup.sql
   ```

2. **Test on development first**
   - Run migrations on dev/staging before production
   - Verify data integrity after migration

3. **Check migration status**
   - Verify all migrations completed successfully
   - Check for any errors in logs

4. **Seed default data**
   - Always seed roles and permissions after fresh migration
   - Seed master data (UOM, GST rates, etc.)

5. **Document custom changes**
   - Keep track of any manual database changes
   - Create migrations for schema changes

## Common Tasks

### Add new tenant and run migrations
```bash
# 1. Register organization via API or admin panel
# 2. Run migrations
php artisan tenant:migrate {new-org-slug}

# 3. Seed default data
php artisan tenant:migrate {new-org-slug} --seed
```

### Update existing tenant schema
```bash
# 1. Create new migration file
php artisan make:migration add_column_to_table --path=database/migrations/tenant

# 2. Run migration
php artisan tenant:migrate {org_slug}
```

### Reset tenant database
```bash
# WARNING: This will delete all data!
php artisan tenant:migrate {org_slug} --fresh --seed
```

## Migration File Template

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->id();
            // Add columns here
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

## Support

For issues or questions:
- Check `TENANT_MIGRATIONS_SUMMARY.md` for table details
- Review migration files in `database/migrations/tenant/`
- Check Laravel logs: `storage/logs/laravel.log`
- Use `php artisan tinker` to inspect database state
