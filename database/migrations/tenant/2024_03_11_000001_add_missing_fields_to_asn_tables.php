<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add missing fields to ASN tables based on implementation plan review
     */
    public function up(): void
    {
        Schema::connection('tenant')->table('asn_headers', function (Blueprint $table) {
            // Add warehouse_id after vendor_id (CRITICAL)
            $table->unsignedBigInteger('warehouse_id')
                ->after('vendor_id')
                ->nullable()
                ->comment('FK → warehouse_master');
            
            // Add actual_arrival after eta (IMPORTANT)
            $table->dateTime('actual_arrival')
                ->after('eta')
                ->nullable()
                ->comment('Actual arrival timestamp at warehouse');
            
            // Add driver details after vehicle_number (NICE TO HAVE)
            $table->string('driver_name', 100)
                ->after('container_id')
                ->nullable()
                ->comment('Driver name for contact');
            
            $table->string('driver_phone', 20)
                ->after('driver_name')
                ->nullable()
                ->comment('Driver contact number');
            
            // Add customer_reference after ship_to_address (NICE TO HAVE)
            $table->string('customer_reference', 100)
                ->after('ship_to_address')
                ->nullable()
                ->comment('Internal project/department code');
            
            // Add updated_by after created_by (AUDIT)
            $table->unsignedBigInteger('updated_by')
                ->after('created_by')
                ->nullable()
                ->comment('FK → users');
        });
        
        // Update status enum to include full workflow
        DB::connection('tenant')->statement("
            ALTER TABLE asn_headers 
            MODIFY COLUMN status ENUM('DRAFT', 'SENT', 'IN_TRANSIT', 'ARRIVED', 'RECEIVED', 'CANCELLED') 
            DEFAULT 'DRAFT'
            COMMENT 'Current shipment status'
        ");
        
        Schema::connection('tenant')->table('asn_headers', function (Blueprint $table) {
            // Add foreign keys
            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouse_master')
                ->onDelete('restrict');
            
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Add indexes
            $table->index('warehouse_id');
            $table->index('actual_arrival');
        });
        
        Schema::connection('tenant')->table('asn_line_items', function (Blueprint $table) {
            // Add sscc after pallet_id (IMPORTANT)
            $table->string('sscc', 50)
                ->after('pallet_id')
                ->nullable()
                ->comment('Serial Shipping Container Code (GS1)');
            
            // Add lot_number after batch_number (IMPORTANT)
            $table->string('lot_number', 50)
                ->after('batch_number')
                ->nullable()
                ->comment('Lot number (separate from batch)');
            
            // Add material_description after material_id (NICE TO HAVE)
            $table->string('material_description', 300)
                ->after('material_id')
                ->nullable()
                ->comment('Vendor description of material');
            
            // Add weight_uom after net_weight (NICE TO HAVE)
            $table->string('weight_uom', 10)
                ->after('net_weight')
                ->default('KG')
                ->comment('Unit of measure for weights');
            
            // Add dimensions after weight_uom (NICE TO HAVE)
            $table->decimal('length', 10, 2)
                ->after('weight_uom')
                ->nullable()
                ->comment('Package length');
            
            $table->decimal('width', 10, 2)
                ->after('length')
                ->nullable()
                ->comment('Package width');
            
            $table->decimal('height', 10, 2)
                ->after('width')
                ->nullable()
                ->comment('Package height');
            
            $table->string('dimension_uom', 10)
                ->after('height')
                ->default('CM')
                ->comment('Unit of measure for dimensions');
            
            // Add line_status after dimension_uom (CRITICAL)
            $table->enum('line_status', ['PENDING', 'RECEIVED', 'PARTIAL', 'CANCELLED'])
                ->after('dimension_uom')
                ->default('PENDING')
                ->comment('Individual line item status');
            
            // Add received_qty after line_status (CRITICAL)
            $table->decimal('received_qty', 15, 3)
                ->after('line_status')
                ->default(0)
                ->comment('Quantity actually received');
            
            // Add indexes
            $table->index('line_status');
            $table->index('sscc');
            $table->index('lot_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('asn_headers', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropForeign(['updated_by']);
            $table->dropIndex(['warehouse_id']);
            $table->dropIndex(['actual_arrival']);
            $table->dropColumn([
                'warehouse_id',
                'actual_arrival',
                'driver_name',
                'driver_phone',
                'customer_reference',
                'updated_by'
            ]);
        });
        
        // Revert status enum
        DB::connection('tenant')->statement("
            ALTER TABLE asn_headers 
            MODIFY COLUMN status ENUM('SENT', 'ACKNOWLEDGED', 'ARRIVED', 'CANCELLED') 
            DEFAULT 'SENT'
        ");
        
        Schema::connection('tenant')->table('asn_line_items', function (Blueprint $table) {
            $table->dropIndex(['line_status']);
            $table->dropIndex(['sscc']);
            $table->dropIndex(['lot_number']);
            $table->dropColumn([
                'sscc',
                'lot_number',
                'material_description',
                'weight_uom',
                'length',
                'width',
                'height',
                'dimension_uom',
                'line_status',
                'received_qty'
            ]);
        });
    }
};
