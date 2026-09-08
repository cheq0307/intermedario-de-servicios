<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('phone', '')->update(['phone' => null]);

        $duplicatePhone = DB::table('users')
            ->select('phone')
            ->whereNotNull('phone')
            ->groupBy('phone')
            ->havingRaw('COUNT(*) > 1')
            ->value('phone');

        if ($duplicatePhone !== null) {
            throw new RuntimeException("No se puede activar la unicidad de teléfonos: el número {$duplicatePhone} está repetido. Corrige esas cuentas desde administración y vuelve a ejecutar la migración.");
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->unique('phone', 'users_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_phone_unique');
        });
    }
};
