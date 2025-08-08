<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->float('height')->nullable()->after('product_tax_amount');
            $table->float('width')->nullable()->after('height');
            $table->integer('piece_qty')->nullable()->after('width');
        });
    }

    public function down()
    {
        Schema::table('sale_details', function (Blueprint $table) {
            $table->dropColumn(['height', 'width', 'piece_qty']);
        });
    }
};
