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
        Schema::table('subcategories', function (Blueprint $table) {
            $table->foreignId('parent_subcategory_id')
                ->nullable()
                ->after('category_id')
                ->constrained('subcategories')
                ->nullOnDelete();
            $table->unsignedTinyInteger('depth')->default(1)->after('parent_subcategory_id');
            $table->index(['category_id', 'parent_subcategory_id', 'sort_order'], 'subcategories_tree_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropIndex('subcategories_tree_idx');
            $table->dropConstrainedForeignId('parent_subcategory_id');
            $table->dropColumn('depth');
        });
    }
};
