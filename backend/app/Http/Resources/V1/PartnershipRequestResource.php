<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PartnershipRequestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'business_name' => $this->business_name,
            'business_type' => $this->business_type,
            'purpose' => $this->purpose,
            'status' => $this->status,
            'notes' => $this->notes,
            'approved_at' => $this->approved_at ? $this->approved_at->toDateTimeString() : null,
            'rejected_at' => $this->rejected_at ? $this->rejected_at->toDateTimeString() : null,
            'approved_by' => $this->approvedBy ? [
                'id' => $this->approvedBy->id,
                'name' => $this->approvedBy->name,
                'email' => $this->approvedBy->email,
            ] : null,
            'rejected_by' => $this->rejectedBy ? [
                'id' => $this->rejectedBy->id,
                'name' => $this->rejectedBy->name,
                'email' => $this->rejectedBy->email,
            ] : null,
            'converted_to_user_id' => $this->converted_to_user_id,
            'converted_user' => $this->convertedUser ? [
                'id' => $this->convertedUser->id,
                'name' => $this->convertedUser->name,
                'email' => $this->convertedUser->email,
            ] : null,
            'created_at' => $this->created_at ? $this->created_at->toDateTimeString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toDateTimeString() : null,
        ];
    }
}
