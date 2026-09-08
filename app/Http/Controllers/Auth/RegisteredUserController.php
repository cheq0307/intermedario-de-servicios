<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Models\Category;
use App\Models\Community;
use App\Models\User;
use App\Services\Accounts\IdentityContacts;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Throwable;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        $communities = Community::query()->where('is_active', true)->orderBy('name')->get();
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get();

        return view('auth.register', compact('communities', 'categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'phone' => is_string($request->input('phone')) ? preg_replace('/[\s().-]+/', '', $request->input('phone')) : $request->input('phone'),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'community_id' => ['required', 'integer', Rule::exists('communities', 'id')->where('is_active', true)],
            'interests' => ['nullable', 'array', 'max:10'],
            'interests.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->where('is_active', true)],
            'phone' => ['required', 'string', 'regex:/^\d{10}$/', Rule::unique('users', 'phone')],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [
            'email.unique' => 'Este correo ya está registrado. Inicia sesión o recupera tu contraseña.',
            'name.required' => 'Escribe tu nombre completo.',
            'name.max' => 'El nombre no puede superar 120 caracteres.',
            'community_id.required' => 'Selecciona tu ciudad y comunidad.',
            'community_id.exists' => 'La comunidad seleccionada no está disponible.',
            'phone.regex' => 'El teléfono debe contener exactamente 10 dígitos.',
            'phone.required' => 'Escribe tu número de teléfono.',
            'phone.unique' => 'Este teléfono ya está registrado en una cuenta.',
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
            IdentityContacts::lock();
            if (User::where('email', $validated['email'])->orWhere('phone', $validated['phone'])->exists()) {
                throw ValidationException::withMessages(['email' => 'Este correo o teléfono ya está registrado.']);
            }
            abort_if(AdminUser::where('email', $validated['email'])->orWhere('phone', $validated['phone'])->exists(), 422, 'Estos datos pertenecen a una cuenta administrativa. Crea tu cuenta de Plaza Local desde administración, después de verificar correo y teléfono.');
            $community = Community::query()->where('is_active', true)->findOrFail($validated['community_id']);
            $user = User::create(collect($validated)->except('interests')->all() + [
                'account_type' => 'client',
                'city' => $community->municipality,
            ]);
            $user->assignRole(Role::findOrCreate('client'));
            $user->categoryPreferences()->sync(collect($validated['interests'] ?? [])->mapWithKeys(
                fn (int $categoryId) => [$categoryId => ['interest_score' => 100, 'behavior_score' => 0]],
            )->all());

            return $user;
        });

        $verificationDeliveryFailed = false;
        try {
            event(new Registered($user));
        } catch (Throwable $exception) {
            report($exception);
            $verificationDeliveryFailed = true;
        }

        Auth::login($user);
        $request->session()->regenerate();

        $response = redirect()->route('dashboard');

        return $verificationDeliveryFailed
            ? $response->with('verification_delivery_failed', true)
            : $response;
    }
}
