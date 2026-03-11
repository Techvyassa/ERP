<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Table: asn_documents
     * Module: Inward > Advance Shipping Notice > Documents
     * Depends on: asn_headers, users
     */
    public function up(): void
    {
        Schema::connection('tenant')->create('asn_documents', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('asn_id')->comment('FK → asn_headers');
            
            $table->enum('document_type', ['PACKING_LIST', 'INVOICE', 'CERTIFICATE', 'OTHER'])
                ->comment('Type of document attached');
            
            $table->string('document_name', 255)->comment('Original filename');
            $table->string('file_path', 500)->comment('Storage path');
            $table->integer('file_size')->nullable()->comment('File size in bytes');
            $table->string('mime_type', 100)->nullable()->comment('File MIME type');
            
            $table->unsignedBigInteger('uploaded_by')->nullable()->comment('FK → users');
            $table->timestamp('uploaded_at')->useCurrent();
            
            // Foreign Keys
            $table->foreign('asn_id')
                ->references('id')
                ->on('asn_headers')
                ->onDelete('cascade');
            
            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
            
            // Indexes
            $table->index('asn_id');
            $table->index('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('asn_documents');
    }
};
