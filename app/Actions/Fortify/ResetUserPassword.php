<?php

namespace App\Actions\Fortify;

use App\Models\AccountIdentityLink;
use App\Models\AdminUser;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    /**
     * Validate and reset the user's forgotten password.
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $adminId = AccountIdentityLink::where('user_id', $user->id)->value('admin_user_id');
        if ($adminId && Hash::check($input['password'], AdminUser::findOrFail($adminId)->password)) {
            throw ValidationException::withMessages(['password' => 'Usa una contraseña distinta de la administrativa.']);
        }

        $user->forceFill([
            'password' => Hash::make($input['password']),
        ])->save();
    }
}
