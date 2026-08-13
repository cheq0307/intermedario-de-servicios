<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $communities = Community::query()->where('is_active', true)->orderBy('name')->get();

        return view('auth.register', compact('communities'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_type' => ['required', 'string', 'in:client,provider'],
            'name' => ['required', 'string', 'max:120'],
            'community_id' => ['required', 'integer', Rule::exists('communities', 'id')->where('is_active', true)],
            'phone' => ['nullable', 'regex:/^\d{10}$/'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'email.unique' => 'Este correo ya está registrado. Inicia sesión o recupera tu contraseña.',
            'account_type.required' => 'Selecciona si comenzarás como cliente o proveedor.',
            'account_type.in' => 'El tipo de cuenta seleccionado no es válido.',
            'name.required' => 'Escribe tu nombre completo.',
            'name.max' => 'El nombre no puede superar 120 caracteres.',
            'community_id.required' => 'Selecciona tu ciudad y comunidad.',
            'community_id.exists' => 'La comunidad seleccionada no está disponible.',
            'phone.regex' => 'El teléfono debe contener exactamente 10 dígitos.',
            'email.required' => 'Escribe tu correo electrónico.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'email.max' => 'El correo electrónico es demasiado largo.',
            'password.required' => 'Crea una contraseña.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.letters' => 'La contraseña debe incluir al menos una letra.',
            'password.numbers' => 'La contraseña debe incluir al menos un número.',
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $community = Community::query()->where('is_active', true)->findOrFail($validated['community_id']);
            $validated['city'] = $community->municipality;
            $user = User::create($validated);
            $user->assignRole(Role::findOrCreate($validated['account_type']));

            if ($validated['account_type'] === 'provider') {
                $user->vendor()->create([
                    'display_name' => $user->name,
                    'slug' => Str::slug($user->name).'-'.$user->id,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'status' => 'draft',
                ]);
            }

            return $user;
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
