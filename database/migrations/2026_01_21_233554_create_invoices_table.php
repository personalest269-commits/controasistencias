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
        Schema::create('invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->string('from_company_name');
            $table->text('from_company_address');
            $table->string('from_company_phone');
            $table->string('from_company_email');
            $table->string('to_company_name');
            $table->text('to_company_address');
            $table->string('to_company_phone');
            $table->string('to_company_email');
            $table->string('invoice_number');
            $table->date('payment_due');
            $table->double('tax');
            $table->double('shipping');
            $table->double('total');
            $table->string('payment_status');
            $table->string('invoice_type');
            $table->date('renewal_date');
            $table->timestamps();
            $table->softDeletes();
            $table->char('estado', 1)->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
