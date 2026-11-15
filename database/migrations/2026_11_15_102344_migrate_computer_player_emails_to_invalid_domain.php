<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->where('is_ai_colaborer', true)
            ->orderBy('id')
            ->each(function (object $user): void {
                $updates = ['notify_email' => false];

                if (is_string($user->email) && str_contains($user->email, '@') && ! str_ends_with(strtolower($user->email), '.invalid')) {
                    $localPart = strstr($user->email, '@', true);
                    if ($localPart !== false && $localPart !== '') {
                        $candidateEmail = "{$localPart}@nagoyaharvest.invalid";
                        $exists = DB::table('users')
                            ->where('email', $candidateEmail)
                            ->where('id', '!=', $user->id)
                            ->exists();

                        if (! $exists) {
                            $updates['email'] = $candidateEmail;
                        }
                    }
                }

                DB::table('users')
                    ->where('id', $user->id)
                    ->update($updates);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible data hygiene migration: do not restore potentially real domains.
    }
};
