<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pindahkan data dari tabel admins ke users
        $admins = DB::table('admins')->get();
        
        foreach ($admins as $admin) {
            DB::table('users')->insert([
                'name' => $admin->name,
                'email' => $admin->email,
                'password' => $admin->password,
                'role_id' => 1, // admin role
                'email_verified_at' => now(),
                'created_at' => $admin->created_at,
                'updated_at' => $admin->updated_at,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus admin users yang dipindahkan
        DB::table('users')->where('role_id', 1)->delete();
    }
};
