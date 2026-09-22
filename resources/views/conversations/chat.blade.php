<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mensajes - Plaza Local</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-brand-surface text-brand-ink antialiased">
    <div class="flex h-dvh min-h-0 flex-col pb-16">
        <x-market-nav :back-url="route('dashboard')" :show-notifications="true" />
        <main class="mx-auto flex min-h-0 w-full max-w-6xl flex-1 overflow-hidden border-x border-brand/10 bg-white" aria-label="Mensajería de Plaza Local">
            <aside class="min-h-0 w-full shrink-0 border-r border-brand/10 lg:block lg:w-80 {{ $activeConversation ? 'hidden' : '' }}">
                <livewire:chat.conversation-list :selected="$activeConversation?->public_id" />
            </aside>
            <div class="min-h-0 min-w-0 flex-1 {{ $activeConversation ? '' : 'hidden lg:block' }}">
                @if($activeConversation)
                    <livewire:chat.chat-window :conversation-id="$activeConversation->public_id" />
                @else
                    <div class="grid h-full place-items-center p-8 text-center"><div><h2 class="text-2xl font-black">Tus conversaciones, en su lugar</h2><p class="mt-3 max-w-md text-brand-copy">Selecciona un chat. Los mensajes de cada publicación y pedido se mantienen separados.</p></div></div>
                @endif
            </div>
        </main>
    </div>
    <x-bottom-nav active="messages" />
    @livewireScriptConfig
</body>
</html>
