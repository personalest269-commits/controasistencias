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
        Schema::create('invoicedetails', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('quantity');
            $table->string('product');
            $table->text('description');
            $table->double('subtotal');
            $table->integer('invoice_id');
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
        Schema::dropIfExists('invoicedetails');
    }
};
