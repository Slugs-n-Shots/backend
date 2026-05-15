<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('accounting_document_name', 64)->nullable()->after('access_guid');
            $table->string('accounting_document_number', 32)->nullable()->after('accounting_document_name');
            $table->string('issuer_name')->nullable()->after('accounting_document_number');
            $table->string('issuer_address')->nullable()->after('issuer_name');
            $table->string('issuer_tax_number', 32)->nullable()->after('issuer_address');
            $table->string('issuer_organizational_unit')->nullable()->after('issuer_tax_number');
            $table->string('customer_type', 16)->nullable()->after('issuer_organizational_unit');
            $table->string('customer_name')->nullable()->after('customer_type');
            $table->string('customer_address')->nullable()->after('customer_name');
            $table->string('customer_tax_number', 32)->nullable()->after('customer_address');
            $table->string('customer_email')->nullable()->after('customer_tax_number');
            $table->dateTime('performance_at')->nullable()->after('customer_email');
            $table->string('economic_event_description')->nullable()->after('performance_at');
            $table->string('accounting_currency', 3)->nullable()->after('economic_event_description');
            $table->integer('accounting_gross_total')->nullable()->after('accounting_currency');
            $table->json('accounting_items')->nullable()->after('accounting_gross_total');
            $table->string('bookkeeping_reference')->nullable()->after('accounting_items');
            $table->dateTime('bookkeeping_posted_at')->nullable()->after('bookkeeping_reference');
            $table->string('bookkeeping_verified_by')->nullable()->after('bookkeeping_posted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $usesSqlite = Schema::getConnection()->getDriverName() === 'sqlite';

        if ($usesSqlite) {
            Schema::disableForeignKeyConstraints();
        }

        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn([
                'accounting_document_name',
                'accounting_document_number',
                'issuer_name',
                'issuer_address',
                'issuer_tax_number',
                'issuer_organizational_unit',
                'customer_type',
                'customer_name',
                'customer_address',
                'customer_tax_number',
                'customer_email',
                'performance_at',
                'economic_event_description',
                'accounting_currency',
                'accounting_gross_total',
                'accounting_items',
                'bookkeeping_reference',
                'bookkeeping_posted_at',
                'bookkeeping_verified_by',
            ]);
        });

        if ($usesSqlite) {
            Schema::enableForeignKeyConstraints();
        }
    }
};
