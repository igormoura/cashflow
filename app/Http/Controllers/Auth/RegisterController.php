<?php
/**
 * RegisterController.php
 * Copyright (c) 2019 james@firefly-iii.org
 *
 * This file is part of Firefly III (https://github.com/firefly-iii).
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Auth;

use FireflyIII\Events\RegisteredUser;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Support\Http\Controllers\CreateStuff;
use FireflyIII\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Log;

/**
 * Class RegisterController
 *
 * This controller handles the registration of new users as well as their
 * validation and creation. By default this controller uses a trait to
 * provide this functionality without requiring any additional code.
 *
 * @codeCoverageIgnore
 */
class RegisterController extends Controller
{
    use RegistersUsers, CreateStuff;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest');

        $loginProvider = config('firefly.login_provider');
        $authGuard     = config('firefly.authentication_guard');

        if ('eloquent' !== $loginProvider || 'web' !== $authGuard) {
            throw new FireflyException('Using external identity provider. Cannot continue.');
        }

    }

    /**a ideia 
     * Handle a registration request for the application.
     *
     * @param Request $request
     *
     * @return Application|Redirector|RedirectResponse
     * @throws FireflyException
     * @throws ValidationException
     */
    public function register(Request $request)
    {
        // is allowed to?

        echo "passou aqui";

        $allowRegistration = true;
        $singleUserMode    = app('fireflyconfig')->get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $userCount         = User::count();
        $guard             = config('auth.defaults.guard');
        if (true === $singleUserMode && $userCount > 0 && 'ldap' !== $guard) {
            $allowRegistration = false;
        }

        if ('ldap' === $guard) {
            $allowRegistration = false;
        }

        if (false === $allowRegistration) {
            throw new FireflyException('Registration is currently not available :(');
        }

        $this->validator($request->all())->validate();
        $user = $this->createUser($request->all());
        Log::info(sprintf('Registered new user %s', $user->email));
        event(new RegisteredUser($user, $request->ip()));

        $this->guard()->login($user);

        session()->flash('success', (string)trans('firefly.registered'));

        $this->registered($request, $user);

        return redirect($this->redirectPath());
    }

    /**
     * Show the application registration form.
     *
     * @param Request $request
     *
     * @return Factory|View
     * @throws FireflyException
     */
    public function showRegistrationForm(Request $request)
    {
        $allowRegistration = true;
        $isDemoSite        = app('fireflyconfig')->get('is_demo_site', config('firefly.configuration.is_demo_site'))->data;
        $singleUserMode    = app('fireflyconfig')->get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $userCount         = User::count();
        $pageTitle         = (string)trans('firefly.register_page_title');
        $guard             = config('auth.defaults.guard');

        if (true === $isDemoSite) {
            $allowRegistration = false;
        }

        if (true === $singleUserMode && $userCount > 0 && 'ldap' !== $guard) {
            $allowRegistration = false;
        }

        if ('ldap' === $guard) {
            $allowRegistration = false;
        }

        if (false === $allowRegistration) {
            $message = 'Registration is currently not available.';

            return prefixView('error', compact('message'));
        }

        $email = $request->old('email');

        return prefixView('auth.register', compact('isDemoSite', 'email', 'pageTitle'));
    }

}
