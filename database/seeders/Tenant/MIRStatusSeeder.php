<?php

namespace Database\Seeders;

use App\Models\Tenant\MIRLineItem;
use Illuminate\Database\Seeder;

class MIRStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all MIR line items
        $lines = MIRLineItem::all();

        foreach ($lines as $line) {
            // Set status based on issued_qty
            if ($line->issued_qty > 0) {
                if ($line->issued_qty >= $line->required_qty) {
                    $line->status = 'FULLY_PICKED';
                } else {
                    $line->status = 'PARTIALLY_PICKED';
                }
            } else {
                $line->status = 'PENDING';
            }

            $line->save();
        }

        // Update MIR header statuses
        $this->updateMIRStatuses();
    }

    /**
     * Update all MIR header statuses based on line statuses
     */
    private function updateMIRStatuses(): void
    {
        $mirs = \App\Models\Tenant\MaterialIssueRequest::with('lines')->get();

        foreach ($mirs as $mir) {
            $mir->status = $mir->deriveHeaderStatus();
            $mir->save();
        }
    }
}