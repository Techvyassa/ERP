<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PutawayTaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'task_number' => $this->task_number,
            'grn_line_id' => $this->grn_line_id,
            'material_id' => $this->material_id,
            'batch_number' => $this->batch_number,
            'quantity' => $this->quantity,
            'uom_id' => $this->uom_id,
            'source_bin_id' => $this->source_bin_id,
            'destination_bin_id' => $this->destination_bin_id,
            'strategy' => $this->strategy,
            'status' => $this->status,
            'bin_scan_confirmed' => $this->bin_scan_confirmed,
            'item_scan_confirmed' => $this->item_scan_confirmed,
            'completed_at' => $this->completed_at,
            'assigned_to' => $this->assigned_to,
            'completed_by' => $this->completed_by,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships with null-safe handling
            'grn_line_item' => $this->whenLoaded('grnLineItem', function () {
                return [
                    'id' => $this->grnLineItem?->id,
                    'grn_id' => $this->grnLineItem?->grn_id,
                    'grn_number' => $this->grnLineItem?->grn?->grn_number,
                    'line_number' => $this->grnLineItem?->line_number,
                ];
            }),
            'material' => $this->whenLoaded('material', function () {
                return [
                    'id' => $this->material?->id,
                    'material_code' => $this->material?->material_code,
                    'material_name' => $this->material?->material_name,
                ];
            }),
            'uom' => $this->whenLoaded('uom', function () {
                return [
                    'id' => $this->uom?->id,
                    'uom_code' => $this->uom?->uom_code,
                    'uom_name' => $this->uom?->uom_name,
                ];
            }),
            'source_bin' => $this->whenLoaded('sourceBin', function () {
                return [
                    'id' => $this->sourceBin?->id,
                    'bin_code' => $this->sourceBin?->bin_code,
                    'bin_name' => $this->sourceBin?->bin_name,
                ];
            }),
            'destination_bin' => $this->whenLoaded('destinationBin', function () {
                return [
                    'id' => $this->destinationBin?->id,
                    'bin_code' => $this->destinationBin?->bin_code,
                    'bin_name' => $this->destinationBin?->bin_name,
                ];
            }),
            'assigned_operator' => $this->whenLoaded('assignedOperator', function () {
                return $this->assignedOperator ? [
                    'id' => $this->assignedOperator->id,
                    'employee_code' => $this->assignedOperator->employee_code,
                    'first_name' => $this->assignedOperator->first_name,
                    'last_name' => $this->assignedOperator->last_name,
                    'full_name' => $this->assignedOperator->full_name,
                ] : null;
            }),
            'completed_by_operator' => $this->whenLoaded('completedByOperator', function () {
                return $this->completedByOperator ? [
                    'id' => $this->completedByOperator->id,
                    'employee_code' => $this->completedByOperator->employee_code,
                    'first_name' => $this->completedByOperator->first_name,
                    'last_name' => $this->completedByOperator->last_name,
                    'full_name' => $this->completedByOperator->full_name,
                ] : null;
            }),
        ];
    }
}
