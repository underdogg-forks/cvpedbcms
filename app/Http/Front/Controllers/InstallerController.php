<?php namespace App\Http\Front\Controllers;

use Artisan;
use DB;
use Config;
use Redirect;
use File;
use Hash;
use Illuminate\Http\Request;
use CVEPDB\Controllers\AbsBaseController as Controller;
use App\Http\Front\Requests\InstallerFormRequest;
use Mockery\CountValidator\Exception;
use Modules\Users\Entities\User;
use \Illuminate\Filesystem\FileException;
use \Illuminate\Filesystem\FileNotFoundException;
use CVEPDB\Repositories\Users\UserRepositoryEloquent;
use CVEPDB\Repositories\Roles\RoleRepositoryEloquent;

class InstallerController extends Controller
{
    /**
     * @var UserRepositoryEloquent|null
     */
    private $r_user = null;

    /**
     * @var RoleRepositoryEloquent|null
     */
    private $r_role = null;

    public function __construct(
        UserRepositoryEloquent $r_user,
        RoleRepositoryEloquent $r_role
    )
    {
        $this->r_user = $r_user;
        $this->r_role = $r_role;
    }

    /**
     * Installer form
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        return view(
            'installer.index',
            [
                'footer' => [
                    'version' => Config::get('app.version'),
                    'title' => Config::get('app.title'),
                    'url' => Config::get('app.url'),
                ]
            ]
        );
    }

    /**
     * Step 1
     *
     * If we can connect to the database with form credential we run the install process
     *
     * @param InstallerFormRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(Request $request, InstallerFormRequest $formRequest)
    {
        try {

            if ($this->testDBConnection($formRequest)) {
                // Write DB config in .env.installer
                $this->generateConfig($formRequest);
                // Store admin user in session
                $request->session()->put('installer_user_admin', [
                    'first_name' => $formRequest->get('first_name'),
                    'last_name' => $formRequest->get('last_name'),
                    'email' => $formRequest->get('email'),
                    'password' => $formRequest->get('password'),
                ]);
            } else {
                return Redirect::to('installer')
                    ->withErrors('installer.error:db_connection')
                    ->withInput();
            }
        } catch (FileNotFoundException $exception) {
            die ("The file doesn't exist");
        } catch (FileException $exception) {
            die ("Impossible to write in file");
        }
        return redirect('installer/migration');
    }

    /**
     * Step 2
     *
     * Run migration based on installer env and set the production env as main env
     *
     * @return Redirect
     */
    public function runMigration()
    {
        Artisan::call('migrate', ['--force' => true]);
        //Artisan::call('module:migrate');
        return redirect('installer/initialisation');
    }

    /**
     * Step 3
     *
     * Run post migration and configuration actions
     *
     * - add admin user and roles
     *
     * @return Redirect
     */
    public function initialiseProduction(Request $request)
    {
        try {
            $this->addUserAdmin($request);

            $bytes_written = File::put(base_path('.env'), 'production' . PHP_EOL);
            if ($bytes_written === false) {
                throw new FileException;
            }

        } catch (FileException $exception) {
            File::delete(base_path('.env'));
            File::delete(base_path('.env.production'));
            die ("Impossible to write in file");
        }
        return redirect('/');
    }

    /**
     * Try the DB connection with form credentials
     *
     * @param InstallerFormRequest $request
     * @return bool
     */
    private function testDBConnection(InstallerFormRequest $formRequest)
    {
        $isConnected = false;

        try {
            Config::set('database.default', 'mysql');
            Config::set('database.connections.mysql.host', $formRequest->get('DB_HOST'));
            Config::set('database.connections.mysql.database', $formRequest->get('DB_DATABASE'));
            Config::set('database.connections.mysql.username', $formRequest->get('DB_USERNAME'));
            Config::set('database.connections.mysql.password', $formRequest->get('DB_PASSWORD'));

            DB::connection()->select(DB::raw("SELECT 1"));

            $isConnected = true;

        } catch (\Exception $e) {
            $isConnected = false;
        }
        return $isConnected;
    }

    /**
     * Complete the installer env file with DB info and create the production env file
     *
     * @param InstallerFormRequest $request
     * @return bool
     * @throws FileException
     */
    private function generateConfig(InstallerFormRequest $formRequest)
    {
        /*
         * Add DB info in installer config for migration
         */

        $contents = File::get(base_path('.env.installer'));
        $contents .= PHP_EOL;
        $contents .= 'DB_HOST=' . $formRequest->get('DB_HOST') . PHP_EOL;
        $contents .= 'DB_DATABASE=' . $formRequest->get('DB_DATABASE') . PHP_EOL;
        $contents .= 'DB_USERNAME=' . $formRequest->get('DB_USERNAME') . PHP_EOL;
        $contents .= 'DB_PASSWORD=' . $formRequest->get('DB_PASSWORD') . PHP_EOL;

        $bytes_written = File::put(base_path('.env.installer'), $contents);
        if ($bytes_written === false) {
            throw new FileException;
        }

        /*
         * Create production env file
         */

        $contents = 'APP_ENV=production'. PHP_EOL;
        $contents .= 'APP_DEBUG=true'. PHP_EOL;
        $contents .= 'APP_KEY=' . hash('md5', time().date('Y-m-d', time())) . PHP_EOL;
        $contents .= 'APP_INSTALLED=true'. PHP_EOL;
        $contents .= 'APP_URL=' . $formRequest->get('APP_URL') . PHP_EOL;
        $contents .= PHP_EOL;
        $contents .= 'CACHE_DRIVER=array' . PHP_EOL;
        $contents .= PHP_EOL;
        $contents .= 'DB_HOST=' . $formRequest->get('DB_HOST') . PHP_EOL;
        $contents .= 'DB_DATABASE=' . $formRequest->get('DB_DATABASE') . PHP_EOL;
        $contents .= 'DB_USERNAME=' . $formRequest->get('DB_USERNAME') . PHP_EOL;
        $contents .= 'DB_PASSWORD=' . $formRequest->get('DB_PASSWORD') . PHP_EOL;

        $bytes_written = File::put(base_path('.env.production'), $contents);
        if ($bytes_written === false) {
            throw new FileException;
        }

        return true;
    }

    /**
     * Record first roles [user,admin] and the admin user
     *
     * @return bool
     * @throws \Exception
     */
    private function addUserAdmin(Request $request)
    {
        $this->r_role->create([
            'name' => RoleRepositoryEloquent::USER,
            'display_name' => 'role:' . RoleRepositoryEloquent::USER . ':display_name',
            'description' => 'role:' . RoleRepositoryEloquent::USER . ':description'
        ]);

        $this->r_role->create([
            'name' => RoleRepositoryEloquent::ADMIN,
            'display_name' => 'role:' . RoleRepositoryEloquent::ADMIN . ':display_name',
            'description' => 'role:' . RoleRepositoryEloquent::ADMIN . ':description'
        ]);

        // Retrieve data from the session
        $session_installer = $value = $request->session()->get('installer_user_admin');
        // Reset session
        $request->session()->put('installer_user_admin', []);

        $user = User::create([
            'first_name' => $session_installer['first_name'],
            'last_name' => $session_installer['last_name'],
            'email' => $session_installer['email'],
            'password' => Hash::make($session_installer['password']),
        ]);

        $role = $this->r_role->role_exists(RoleRepositoryEloquent::USER);
        $user->attachRole($role);

        $role = $this->r_role->role_exists(RoleRepositoryEloquent::ADMIN);
        $user->attachRole($role);

        return true;
    }
}