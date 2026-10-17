<?php

namespace App\Services\Demo;

use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Community;
use App\Models\Conversation;
use App\Models\JobApplication;
use App\Models\JobProposal;
use App\Models\JobRequest;
use App\Models\JobVacancy;
use App\Models\Listing;
use App\Models\Order;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReaction;
use App\Models\PostShare;
use App\Models\Review;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

final class PopulateMarketplaceActivity
{
    /** @param Collection<string, User> $users */
    public function handle(Collection $users, Community $community, Category $category): void
    {
        $posts = $this->createOffers($users, $category);
        $requests = $this->createRequests($users, $community, $category, $posts);
        $proposals = $this->createProposals($users, $requests);
        $orders = $this->createOrders($users, $requests, $proposals);
        $this->createVacancies($users, $community, $category);
        $this->createConversations($users, $posts);
        $this->createSocialActivity($users, $posts);
        $this->createReviews($users, $orders['service3']);
        $this->createSupportActivity($users);
        $this->createFollows($users);
    }

    /** @param Collection<string, User> $users
     * @return Collection<int, Post>
     */
    private function createOffers(Collection $users, Category $category): Collection
    {
        $offers = [
            ['Tacos para fiestas', 'service', 'Llevamos carrito, ingredientes y servicio completo para tu evento.', 'starting_at', 320000, null],
            ['Canasta de pan artesanal', 'product', 'Pan horneado esta mañana con entrega en la comunidad.', 'fixed', 14500, 18],
            ['Reparación de fugas', 'service', 'Diagnóstico y reparación de plomería doméstica con materiales cotizados aparte.', 'quote', null, null],
            ['Kit de plantas aromáticas', 'product', 'Cinco plantas listas para cocina y jardín en macetas reutilizables.', 'fixed', 28000, 12],
            ['Fotografía para eventos', 'service', 'Cobertura local de cumpleaños, bautizos y reuniones familiares.', 'starting_at', 180000, null],
            ['Pastel personalizado', 'product', 'Pastel para doce personas con decoración y mensaje a elegir.', 'starting_at', 42000, 8],
            ['Clases de regularización', 'service', 'Apoyo en matemáticas y español para primaria y secundaria.', 'fixed', 12000, null],
            ['Mueble restaurado', 'product', 'Buró de madera recuperada, lijado y terminado a mano.', 'fixed', 95000, 2],
            ['Traslado local programado', 'service', 'Viajes dentro de la zona para personas y compras pequeñas.', 'quote', null, null],
            ['Paquete de comida semanal', 'product', 'Cinco comidas caseras listas para recoger de lunes a viernes.', 'fixed', 55000, 10],
        ];

        return collect($offers)->map(function (array $offer, int $index) use ($users, $category): Post {
            $number = $index + 1;
            $providerNumber = ($index % 5) + 1;
            $provider = $users['provider'.$providerNumber];
            $vendor = $provider->vendor;
            $slug = $number === 1 ? 'producto-demo-visible' : 'oferta-demo-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT);
            $token = $number === 1 ? 'demo-marketplace-product' : 'demo-marketplace-offer-'.$number;
            [$name, $type, $description, $priceType, $price, $stock] = $offer;

            $listing = Listing::updateOrCreate(
                ['vendor_id' => $vendor->id, 'slug' => $slug],
                [
                    'category_id' => $category->id,
                    'type' => $type,
                    'name' => $name,
                    'description' => $description,
                    'price_type' => $priceType,
                    'price_amount' => $price,
                    'currency' => 'MXN',
                    'stock' => $stock,
                    'is_active' => true,
                ],
            );
            $post = Post::updateOrCreate(
                ['submission_token' => $token],
                [
                    'user_id' => $provider->id,
                    'vendor_id' => $vendor->id,
                    'listing_id' => $listing->id,
                    'job_request_id' => null,
                    'category_id' => $category->id,
                    'type' => $type,
                    'body' => $description,
                    'comments_enabled' => true,
                    'published_at' => now()->subHours(36 - $number),
                ],
            );
            $this->attachDemoImage($post, $number, $name);

            if ($number === 1) {
                $this->attachDemoVideo($post);
            }

            return $post;
        });
    }

    /** @param Collection<string, User> $users
     * @param  Collection<int, Post>  $posts
     * @return Collection<int, JobRequest>
     */
    private function createRequests(Collection $users, Community $community, Category $category, Collection $posts): Collection
    {
        $requests = [
            ['Reparar una fuga en cocina', 'Busco a alguien que pueda revisar una fuga debajo del fregadero esta semana.', 50000, 120000, 'soon'],
            ['Comida para reunión familiar', 'Necesito comida para veinte personas el sábado, con opciones sin picante.', 180000, 350000, 'urgent'],
            ['Arreglar puerta de madera', 'La puerta roza el piso y necesita ajuste de bisagras y acabado.', 70000, 160000, 'normal'],
            ['Clases de matemáticas', 'Busco apoyo dos tardes por semana para estudiante de secundaria.', 8000, 15000, 'soon'],
            ['Traslado de compras grandes', 'Necesito recoger materiales en el municipio y llevarlos a casa.', 30000, 90000, 'normal'],
        ];

        return collect($requests)->map(function (array $data, int $index) use ($users, $community, $category, $posts): JobRequest {
            $number = $index + 1;
            [$title, $description, $minimum, $maximum, $urgency] = $data;
            $request = JobRequest::updateOrCreate(
                ['public_id' => $this->uuid('1', $number)],
                [
                    'client_id' => $users['buyer'.$number]->id,
                    'category_id' => $category->id,
                    'title' => $title,
                    'description' => $description,
                    'budget_min_amount' => $minimum,
                    'budget_max_amount' => $maximum,
                    'currency' => 'MXN',
                    'urgency' => $urgency,
                    'status' => $number === 5 ? 'in_conversation' : 'assigned',
                    'location_label' => 'Comunidad Demostración',
                    'published_at' => now()->subDays(6 - $number),
                    'expires_at' => now()->addDays(14),
                ],
            );
            $request->communities()->syncWithoutDetaching([$community->id]);
            $post = Post::updateOrCreate(
                ['submission_token' => 'demo-marketplace-request-'.$number],
                [
                    'user_id' => $users['buyer'.$number]->id,
                    'vendor_id' => null,
                    'listing_id' => null,
                    'job_request_id' => $request->id,
                    'category_id' => $category->id,
                    'type' => 'job_request',
                    'body' => $description,
                    'comments_enabled' => true,
                    'published_at' => $request->published_at,
                ],
            );
            $this->attachDemoImage($post, 10 + $number, $title);
            $posts->push($post);

            return $request;
        });
    }

    /** @param Collection<string, User> $users
     * @param  Collection<int, JobRequest>  $requests
     * @return Collection<string, JobProposal>
     */
    private function createProposals(Collection $users, Collection $requests): Collection
    {
        $proposals = collect();
        foreach ($requests as $index => $request) {
            foreach ([1, 2] as $position) {
                $providerNumber = (($index + $position - 1) % 5) + 1;
                $proposal = JobProposal::updateOrCreate(
                    ['job_request_id' => $request->id, 'provider_id' => $users['provider'.$providerNumber]->id],
                    [
                        'public_id' => $this->uuid('2', ($index * 2) + $position),
                        'amount' => $request->budget_min_amount + ($position * 10000),
                        'currency' => 'MXN',
                        'message' => $position === 1
                            ? 'Puedo realizar el trabajo en la fecha indicada. Incluyo revisión inicial y seguimiento.'
                            : 'Tengo disponibilidad y experiencia en trabajos similares dentro de la comunidad.',
                        'estimated_days' => $position + 1,
                        'status' => $index < 4 ? ($position === 1 ? 'accepted' : 'rejected') : 'pending',
                        'responded_at' => $index < 4 ? now()->subDays(2) : null,
                    ],
                );
                $proposals->put('request'.($index + 1).'.'.$position, $proposal);
            }
        }

        return $proposals;
    }

    /** @param Collection<string, User> $users
     * @param  Collection<int, JobRequest>  $requests
     * @param  Collection<string, JobProposal>  $proposals
     * @return Collection<string, Order>
     */
    private function createOrders(Collection $users, Collection $requests, Collection $proposals): Collection
    {
        $orders = collect();
        $states = ['awaiting_payment', 'in_progress', 'completed', 'delivered'];
        foreach (range(1, 4) as $number) {
            $request = $requests[$number - 1];
            $proposal = $proposals['request'.$number.'.1'];
            $provider = $proposal->provider;
            $status = $states[$number - 1];
            $order = Order::updateOrCreate(
                ['public_id' => $this->uuid('5', $number)],
                [
                    'buyer_id' => $request->client_id,
                    'vendor_id' => $provider->vendor->id,
                    'job_request_id' => $request->id,
                    'job_proposal_id' => $proposal->id,
                    'status' => $status,
                    'fulfillment_type' => 'service',
                    'subtotal_amount' => $proposal->amount,
                    'commission_amount' => 0,
                    'total_amount' => $proposal->amount,
                    'currency' => 'MXN',
                    'buyer_notes' => $request->description,
                    'accepted_at' => now()->subDays(5),
                    'started_at' => in_array($status, ['in_progress', 'completed', 'delivered'], true) ? now()->subDays(4) : null,
                    'delivered_at' => in_array($status, ['completed', 'delivered'], true) ? now()->subDays(2) : null,
                    'completed_at' => $status === 'completed' ? now()->subDay() : null,
                    'due_at' => now()->addDays(2),
                ],
            );
            $order->items()->updateOrCreate(
                ['listing_id' => null, 'name_snapshot' => $request->title],
                ['quantity' => 1, 'unit_price_amount' => $proposal->amount, 'line_total_amount' => $proposal->amount],
            );
            $orders->put('service'.$number, $order);
        }

        return $orders;
    }

    /** @param Collection<string, User> $users */
    private function createVacancies(Collection $users, Community $community, Category $category): void
    {
        $titles = ['Auxiliar de cocina', 'Ayudante de reparto', 'Atención de mostrador', 'Apoyo para eventos', 'Asistente de taller'];
        foreach ($titles as $index => $title) {
            $number = $index + 1;
            $vacancy = JobVacancy::updateOrCreate(
                ['public_id' => $number === 1 ? 'd0000000-0000-4000-8000-000000000002' : $this->uuid('6', $number)],
                [
                    'employer_id' => $users['employer'.$number]->id,
                    'community_id' => $community->id,
                    'category_id' => $category->id,
                    'title' => $title.' DEMO',
                    'description' => 'Vacante ficticia visible para probar búsqueda, postulación y seguimiento.',
                    'requirements' => 'Disponibilidad, trato respetuoso y referencias dentro de la comunidad.',
                    'salary_min_amount' => 180000 + ($number * 10000),
                    'salary_max_amount' => 240000 + ($number * 10000),
                    'pay_period' => 'weekly',
                    'work_mode' => 'onsite',
                    'contract_type' => 'temporary',
                    'schedule' => 'Lunes a viernes, horario acordado con el negocio.',
                    'vacancies_count' => 1,
                    'publication_fee_amount' => 0,
                    'currency' => 'MXN',
                    'status' => 'published',
                    'paid_at' => null,
                    'published_at' => now()->subDays($number),
                    'expires_at' => now()->addDays(30),
                ],
            );
            foreach ([1, 2] as $position) {
                $applicantNumber = (($index + $position - 1) % 5) + 1;
                JobApplication::updateOrCreate(
                    ['job_vacancy_id' => $vacancy->id, 'applicant_id' => $users['applicant'.$applicantNumber]->id],
                    [
                        'cover_letter' => 'Me interesa la vacante y puedo presentarme para conversar sobre horarios y actividades.',
                        'status' => $position === 1 && $number % 2 === 0 ? 'reviewing' : 'submitted',
                    ],
                );
            }
        }
    }

    /** @param Collection<string, User> $users
     * @param  Collection<int, Post>  $posts
     */
    private function createConversations(Collection $users, Collection $posts): void
    {
        foreach (range(1, 5) as $number) {
            $buyer = $users['buyer'.$number];
            $provider = $users['provider'.$number];
            $post = $posts[$number - 1];
            $negotiation = $this->upsertConversation([
                'public_id' => $this->uuid('7', $number),
                'post_id' => $post->id,
                'initiated_by_user_id' => $buyer->id,
                'context_key' => 'demo-negotiation-'.$number,
                'type' => 'negotiation',
                'state' => 'active',
                'expires_at' => now()->addDays(7),
                'last_message_at' => now()->subMinutes($number),
            ]);
            $negotiation->participants()->syncWithoutDetaching([$buyer->id, $provider->id]);
            $this->createConversationMessages($negotiation, $buyer, $provider, [
                'Hola, vi tu publicación. ¿Todavía está disponible?',
                'Sí, todavía está disponible. ¿Para qué fecha lo necesitas?',
                'Esta semana me funciona. Te confirmo por aquí los detalles.',
            ], 70 + $number);

            $ids = collect([$users['applicant'.$number]->id, $users['employer'.$number]->id])->sort()->values();
            $direct = $this->upsertConversation([
                'public_id' => $this->uuid('8', $number),
                'direct_key' => $ids->join(':'),
                'type' => 'direct',
                'state' => 'active',
                'last_message_at' => now()->subMinutes(10 + $number),
            ]);
            $direct->participants()->syncWithoutDetaching($ids->all());
            $this->createConversationMessages($direct, $users['applicant'.$number], $users['employer'.$number], [
                'Hola, me interesa conocer más sobre la vacante.',
                'Gracias por escribir. Estamos revisando postulaciones esta semana.',
                'Perfecto, quedo pendiente y tengo disponibilidad para entrevista.',
            ], 80 + $number);
        }
    }

    private function upsertConversation(array $attributes): Conversation
    {
        $conversation = Conversation::query()->firstOrNew(['public_id' => $attributes['public_id']]);
        $conversation->forceFill($attributes)->save();

        return $conversation;
    }

    private function createConversationMessages(Conversation $conversation, User $first, User $second, array $bodies, int $sequence): void
    {
        foreach ($bodies as $index => $body) {
            $sender = $index % 2 === 0 ? $first : $second;
            $conversation->messages()->updateOrCreate(
                ['client_message_id' => $this->uuid('9', ($sequence * 10) + $index)],
                ['sender_id' => $sender->id, 'type' => 'text', 'body' => $body],
            );
        }
    }

    /** @param Collection<string, User> $users
     * @param  Collection<int, Post>  $posts
     */
    private function createSocialActivity(Collection $users, Collection $posts): void
    {
        $people = $users->values();
        foreach ($posts as $postIndex => $post) {
            foreach ([1, 2, 3] as $position) {
                $actor = $people[($postIndex + $position) % $people->count()];
                PostReaction::updateOrCreate(
                    ['post_id' => $post->id, 'user_id' => $actor->id],
                    ['type' => 'like'],
                );
            }
            foreach ([1, 2] as $position) {
                $actor = $people[($postIndex + $position + 4) % $people->count()];
                PostComment::updateOrCreate(
                    ['post_id' => $post->id, 'user_id' => $actor->id, 'body' => $position === 1 ? '¿Me compartes más información por mensaje?' : 'Se ve muy bien, gracias por publicarlo.'],
                );
            }
            PostShare::updateOrCreate(
                ['post_id' => $post->id, 'user_id' => $people[($postIndex + 8) % $people->count()]->id, 'channel' => 'clipboard'],
            );
        }
    }

    /** @param Collection<string, User> $users */
    private function createReviews(Collection $users, Order $order): void
    {
        $provider = $order->vendor->user;
        Review::updateOrCreate(
            ['order_id' => $order->id, 'author_id' => $order->buyer_id],
            ['subject_user_id' => $provider->id, 'rating' => 5, 'comment' => 'Cumplió el acuerdo y mantuvo buena comunicación.', 'is_visible' => true],
        );
        Review::updateOrCreate(
            ['order_id' => $order->id, 'author_id' => $provider->id],
            ['subject_user_id' => $order->buyer_id, 'rating' => 5, 'comment' => 'La solicitud fue clara y la entrega se coordinó sin problemas.', 'is_visible' => true],
        );
    }

    /** @param Collection<string, User> $users */
    private function createSupportActivity(Collection $users): void
    {
        $admin = AdminUser::where('email', 'administrador1.demo@example.test')->firstOrFail();
        foreach ([1, 2] as $number) {
            $user = $users['buyer'.$number];
            $ticket = SupportTicket::updateOrCreate(
                ['user_id' => $user->id, 'subject' => 'Consulta DEMO '.$number.' sobre el uso de Plaza Local'],
                [
                    'admin_user_id' => $admin->id,
                    'category' => $number === 1 ? 'account' : 'general',
                    'status' => $number === 1 ? 'waiting_user' : 'in_progress',
                    'last_message_at' => now()->subMinutes($number),
                ],
            );
            SupportMessage::updateOrCreate(
                ['support_ticket_id' => $ticket->id, 'sender_id' => $user->id, 'body' => 'Necesito orientación para completar este flujo de prueba.'],
                ['is_staff' => false],
            );
            SupportMessage::updateOrCreate(
                ['support_ticket_id' => $ticket->id, 'admin_user_id' => $admin->id, 'body' => 'Claro, revisamos tu caso y te compartimos los siguientes pasos.'],
                ['sender_id' => null, 'is_staff' => true],
            );
        }
    }

    /** @param Collection<string, User> $users */
    private function createFollows(Collection $users): void
    {
        foreach (range(1, 5) as $number) {
            $users['buyer'.$number]->following()->syncWithoutDetaching([
                $users['provider'.$number]->id,
                $users['provider'.(($number % 5) + 1)]->id,
            ]);
        }
    }

    private function attachDemoImage(Post $post, int $number, string $title): void
    {
        $path = 'demo/publicacion-'.str_pad((string) $number, 2, '0', STR_PAD_LEFT).'.svg';
        Storage::disk('public')->put($path, $this->demoImage($number, $title));
        $post->media()->updateOrCreate(
            ['position' => 0],
            ['type' => 'image', 'disk' => 'public', 'path' => $path, 'alt_text' => $title],
        );
    }

    private function attachDemoVideo(Post $post): void
    {
        $path = 'demo/plaza-local-demo.mp4';
        Storage::disk('public')->put($path, base64_decode(self::DEMO_VIDEO_BASE64, true));
        $post->media()->updateOrCreate(
            ['position' => 1],
            ['type' => 'video', 'disk' => 'public', 'path' => $path, 'alt_text' => null],
        );
    }

    private function demoImage(int $number, string $title): string
    {
        $safeTitle = htmlspecialchars($title, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $accent = $number % 2 === 0 ? '#E8734D' : '#FFB36B';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800" viewBox="0 0 1200 800" role="img" aria-labelledby="title description">
<title id="title">{$safeTitle}</title><desc id="description">Contenido ficticio para probar Plaza Local</desc>
<rect width="1200" height="800" fill="#F7F3EA"/><rect x="55" y="55" width="1090" height="690" rx="52" fill="#123B4A"/>
<circle cx="210" cy="205" r="92" fill="{$accent}"/><text x="210" y="225" fill="#123B4A" font-family="Arial,sans-serif" font-size="58" text-anchor="middle" font-weight="700">{$number}</text>
<text x="600" y="370" fill="#FFFFFF" font-family="Arial,sans-serif" font-size="64" text-anchor="middle" font-weight="700">PLAZA LOCAL</text>
<text x="600" y="465" fill="#FFFFFF" font-family="Arial,sans-serif" font-size="38" text-anchor="middle">{$safeTitle}</text>
<text x="600" y="565" fill="#DCE9E5" font-family="Arial,sans-serif" font-size="28" text-anchor="middle">PUBLICACIÓN DEMO · SIN PAGOS REALES</text>
</svg>
SVG;
    }

    private function uuid(string $group, int $number): string
    {
        return 'd'.$group.'000000-0000-4000-8000-'.str_pad((string) $number, 12, '0', STR_PAD_LEFT);
    }

    private const DEMO_VIDEO_BASE64 = 'AAAAIGZ0eXBpc29tAAACAGlzb21pc28yYXZjMW1wNDEAAAPEbW9vdgAAAGxtdmhkAAAAAAAAAAAAAAAAAAAD6AAAA+gAAQAAAQAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAgAAAu50cmFrAAAAXHRraGQAAAADAAAAAAAAAAAAAAABAAAAAAAAA+gAAAAAAAAAAAAAAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAABAAAAAAAAAAAAAAAAAABAAAAAAUAAAAC0AAAAAAAkZWR0cwAAABxlbHN0AAAAAAAAAAEAAAPoAAAIAAABAAAAAAJmbWRpYQAAACBtZGhkAAAAAAAAAAAAAAAAAAAwAAAAMABVxAAAAAAALWhkbHIAAAAAAAAAAHZpZGUAAAAAAAAAAAAAAABWaWRlb0hhbmRsZXIAAAACEW1pbmYAAAAUdm1oZAAAAAEAAAAAAAAAAAAAACRkaW5mAAAAHGRyZWYAAAAAAAAAAQAAAAx1cmwgAAAAAQAAAdFzdGJsAAAAwXN0c2QAAAAAAAAAAQAAALFhdmMxAAAAAAAAAAEAAAAAAAAAAAAAAAAAAAAAAUAAtABIAAAASAAAAAAAAAABFUxhdmM2Mi4yOC4xMDAgbGlieDI2NAAAAAAAAAAAAAAAGP//AAAAN2F2Y0MBZAAM/+EAGmdkAAys2UFBn58BEAAAAwAQAAADAYDxQplgAQAGaOvjyyLA/fj4AAAAABBwYXNwAAAAAQAAAAEAAAAUYnRydAAAAAAAABzwAAAAAAAAABhzdHRzAAAAAAAAAAEAAAAMAAAEAAAAABRzdHNzAAAAAAAAAAEAAAABAAAAaGN0dHMAAAAAAAAACwAAAAEAAAgAAAAAAQAAFAAAAAABAAAIAAAAAAEAAAAAAAAAAQAABAAAAAABAAAUAAAAAAEAAAgAAAAAAQAAAAAAAAABAAAEAAAAAAEAABAAAAAAAgAABAAAAAAcc3RzYwAAAAAAAAABAAAAAQAAAAwAAAABAAAARHN0c3oAAAAAAAAAAAAAAAwAAAL1AAAAEQAAAA0AAAANAAAADQAAABYAAAAPAAAADQAAAA0AAAAWAAAADwAAAA0AAAAUc3RjbwAAAAAAAAABAAAD9AAAAGJ1ZHRhAAAAWm1ldGEAAAAAAAAAIWhkbHIAAAAAAAAAAG1kaXJhcHBsAAAAAAAAAAAAAAAALWlsc3QAAAAlqXRvbwAAAB1kYXRhAAAAAQAAAABMYXZmNjIuMTIuMTAwAAAACGZyZWUAAAOmbWRhdAAAAq4GBf//qtxF6b3m2Ui3lizYINkj7u94MjY0IC0gY29yZSAxNjUgcjMyMjMgMDQ4MGNiMCAtIEguMjY0L01QRUctNCBBVkMgY29kZWMgLSBDb3B5bGVmdCAyMDAzLTIwMjUgLSBodHRwOi8vd3d3LnZpZGVvbGFuLm9yZy94MjY0Lmh0bWwgLSBvcHRpb25zOiBjYWJhYz0xIHJlZj0zIGRlYmxvY2s9MTowOjAgYW5hbHlzZT0weDM6MHgxMTMgbWU9aGV4IHN1Ym1lPTcgcHN5PTEgcHN5X3JkPTEuMDA6MC4wMiBtaXhlZF9yZWY9MSBtZV9yYW5nZT0xNiBjaHJvbWFfbWU9MSB0cmVsbGlzPTEgOHg4ZGN0PTEgY3FtPTAgZGVhZHpvbmU9MjEsMTEgZmFzdF9wc2tpcD0xIGNocm9tYV9xcF9vZmZzZXQ9LTIgdGhyZWFkcz02IGxvb2thaGVhZF90aHJlYWRzPTEgc2xpY2VkX3RocmVhZHM9MCBucj0wIGRlY2ltYXRlPTEgaW50ZXJsYWNlZD0wIGJsdXJheV9jb21wYXQ9MCBjb25zdHJhaW5lZF9pbnRyYT0wIGJmcmFtZXM9MyBiX3B5cmFtaWQ9MiBiX2FkYXB0PTEgYl9iaWFzPTAgZGlyZWN0PTEgd2VpZ2h0Yj0xIG9wZW5fZ29wPTAgd2VpZ2h0cD0yIGtleWludD0yNTAga2V5aW50X21pbj0xMiBzY2VuZWN1dD00MCBpbnRyYV9yZWZyZXNoPTAgcmNfbG9va2FoZWFkPTQwIHJjPWNyZiBtYnRyZWU9MSBjcmY9MjMuMCBxY29tcD0wLjYwIHFwbWluPTAgcXBtYXg9NjkgcXBzdGVwPTQgaXBfcmF0aW89MS40MCBhcT0xOjEuMDAAgAAAAD9liIQAEP/+5sD5llf4G36r2uOJ7SLlJeKTDK3g+nEAy5DQ8ufAAJ6lOvcXEQixXC2AiwAAB0Q2ICtlMlm8aXEAAAANQZokbEEP/qpVAACFgAAAAAlBnkJ4hv8AAXcAAAAJAZ5hdEM/AAHHAAAACQGeY2pDPwABxwAAABJBmmhJqEFomUwIf//+qZYAAgcAAAALQZ6GRREsN/8AAXcAAAAJAZ6ldEM/AAHHAAAACQGep2pDPwABxwAAABJBmqtJqEFsmUwIZ//+nhAAD5gAAAALQZ7JRRUsN/8AAXcAAAAJAZ7qakM/AAHH';
}
