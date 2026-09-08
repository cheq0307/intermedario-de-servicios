<?php

namespace App\Services\Accounts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class PruneUnverifiedAccounts
{
    /**
     * References that prove the account has meaningful marketplace or support history.
     * Registration-only records such as sessions, notifications, roles and preferences
     * intentionally do not block cleanup.
     *
     * @var array<string, list<string>>
     */
    private const BLOCKING_REFERENCES = [
        'vendors' => ['user_id'],
        'orders' => ['buyer_id'],
        'disputes' => ['opened_by', 'resolved_by'],
        'reviews' => ['author_id', 'subject_user_id'],
        'job_proposals' => ['provider_id'],
        'job_requests' => ['client_id'],
        'posts' => ['user_id', 'removed_by_user_id'],
        'conversation_participants' => ['user_id'],
        'messages' => ['sender_id'],
        'favorites' => ['user_id'],
        'audit_logs' => ['user_id'],
        'dispute_messages' => ['user_id'],
        'post_reactions' => ['user_id'],
        'post_comments' => ['user_id'],
        'post_shares' => ['user_id'],
        'support_tickets' => ['user_id', 'assigned_admin_id'],
        'support_messages' => ['sender_id'],
        'job_vacancies' => ['employer_id'],
        'job_applications' => ['applicant_id'],
        'user_follows' => ['follower_id', 'followed_id'],
        'vendor_verification_documents' => ['uploaded_by_user_id', 'reviewed_by_user_id'],
        'post_promotions' => ['user_id', 'reviewed_by_user_id'],
        'publication_drafts' => ['user_id'],
        'account_identity_links' => ['user_id'],
        'admin_invitations' => ['user_id'],
    ];

    public function prune(int $days = 7, bool $dryRun = false): int
    {
        $affected = 0;

        $this->staleQuery($days)->chunkById(100, function ($users) use (&$affected, $dryRun, $days): void {
            foreach ($users as $user) {
                if ($this->hasMeaningfulHistory($user)) {
                    continue;
                }

                $affected += $dryRun ? 1 : (int) $this->deleteAccount($user, $days);
            }
        });

        return $affected;
    }

    private function staleQuery(int $days): Builder
    {
        return User::query()
            ->whereNull('migrated_to_admin_at')
            ->whereNull('email_verified_at')
            ->where('created_at', '<=', now()->subDays(max(1, $days)))
            ->whereDoesntHave('roles', fn (Builder $roles) => $roles->whereIn('name', ['admin', 'superadmin']));
    }

    private function hasMeaningfulHistory(User $user): bool
    {
        foreach (self::BLOCKING_REFERENCES as $table => $columns) {
            foreach ($columns as $column) {
                if (DB::table($table)->where($column, $user->id)->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function deleteAccount(User $user, int $days): bool
    {
        return DB::transaction(function () use ($user, $days): bool {
            IdentityContacts::lock();
            $user = $this->staleQuery($days)->whereKey($user->id)->lockForUpdate()->first();
            if (! $user || $this->hasMeaningfulHistory($user)) {
                return false;
            }
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->notifications()->delete();
            $user->delete();

            return true;
        });
    }
}
