<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'asesor', 'manager_sertifikasi']);
            $table->timestamps();
            $table->unique(['user_id', 'role']);
        });

        DB::table('users')->select('id', 'role')->whereNotNull('role')->orderBy('id')->chunkById(200, function ($users) {
            $now  = now();
            $rows = $users->map(fn ($u) => [
                'user_id'    => $u->id,
                'role'       => $u->role,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            DB::table('user_roles')->insertOrIgnore($rows);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'asesor', 'manager_sertifikasi'])->default('admin')->after('email');
        });

        // Best-effort: prioritas admin > manager_sertifikasi > asesor jika user punya beberapa role.
        DB::statement("
            UPDATE users u
            SET role = COALESCE(
                (SELECT role FROM user_roles WHERE user_id = u.id AND role = 'admin' LIMIT 1),
                (SELECT role FROM user_roles WHERE user_id = u.id AND role = 'manager_sertifikasi' LIMIT 1),
                (SELECT role FROM user_roles WHERE user_id = u.id AND role = 'asesor' LIMIT 1),
                'admin'
            )
        ");

        Schema::dropIfExists('user_roles');
    }
};
