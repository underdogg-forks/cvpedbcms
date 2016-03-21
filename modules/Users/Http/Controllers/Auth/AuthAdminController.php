<?php

namespace Modules\Users\Http\Controllers\Auth;

use App\User;
use Validator;
use CVEPDB\Controllers\AbsController as Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Theme;
use Modules\Users\Outputters\AuthAdminOutputter;

class AuthAdminController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = 'admin';

    /**
     * @var AuthAdminOutputter|null
     */
    private $outputter = null;

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct(AuthAdminOutputter $outputter)
    {
        parent::__construct();
        $this->middleware('guest', ['except' => ['logout', 'getLogout']]);
        $this->outputter = $outputter;
        $this->view_prefix = Theme::getCurrent() . '::';
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|confirmed|min:6',
        ]);
    }

    public function getLogin()
    {
        return $this->outputter->output('users.admin.login');
    }
}