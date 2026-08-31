<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->foreignId('category_id')->nullable()->after('job_request_id')->constrained()->nullOnDelete();
            $table->index(['category_id', 'type', 'published_at']);
        });

        DB::table('posts')
            ->select(['id', 'listing_id', 'job_request_id'])
            ->orderBy('id')
            ->chunkById(500, function ($posts): void {
                $listingCategories = DB::table('listings')
                    ->whereIn('id', $posts->pluck('listing_id')->filter()->all())
                    ->pluck('category_id', 'id');
                $requestCategories = DB::table('job_requests')
                    ->whereIn('id', $posts->pluck('job_request_id')->filter()->all())
                    ->pluck('category_id', 'id');

                foreach ($posts as $post) {
                    $categoryId = $post->listing_id
                        ? $listingCategories->get($post->listing_id)
                        : $requestCategories->get($post->job_request_id);

                    if ($categoryId) {
                        DB::table('posts')->where('id', $post->id)->update(['category_id' => $categoryId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex(['category_id', 'type', 'published_at']);
            $table->dropConstrainedForeignId('category_id');
        });
    }
};
