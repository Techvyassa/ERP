<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class ASNDocument extends Model
{
    protected $connection = 'tenant';
    protected $table = 'asn_documents';
    public $timestamps = false;

    protected $fillable = [
        'asn_id',
        'document_type',
        'document_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'file_size' => 'integer',
    ];

    /**
     * Get the ASN
     */
    public function asn()
    {
        return $this->belongsTo(ASN::class, 'asn_id');
    }

    /**
     * Get the uploader
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get file size in human readable format
     */
    public function getFileSizeHuman(): string
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 2) . ' ' . $units[$unit];
    }

    /**
     * Check if document is an image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Check if document is a PDF
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }
}
