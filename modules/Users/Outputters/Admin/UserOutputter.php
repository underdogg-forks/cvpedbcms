<?php namespace Modules\Users\Outputters\Admin;

use Auth;
use Config;
use Session;
use Request;
use Event;
use App\Http\Admin\Outputters\AdminOutputter;
use CVEPDB\Requests\IFormRequest;
use Modules\Users\Repositories\UserRepositoryEloquent;
use Modules\Users\Repositories\ApiKeyRepositoryEloquent;
use Modules\Users\Transformers\UsersAdminExcelTransformer;
use Modules\Users\Presenters\UsersAdminExcelPresenter;
use CVEPDB\Repositories\Roles\RoleRepositoryEloquent;
use \Maatwebsite\Excel\Files\NewExcelFile;
use Modules\Users\Events\Admin\UserCreatedEvent;
use Modules\Users\Events\Admin\UserUpdatedEvent;
use Modules\Users\Events\Admin\UserDeletedEvent;
use Conner\Addresses\AddressesFacade as Addresses;

class UserOutputter extends AdminOutputter
{
    /**
     * @var string Outputter header title
     */
    protected $title = 'users::admin.meta_title';

    /**
     * @var string Outputter header description
     */
    protected $description = 'users::admin.meta_description';

    /**
     * @var null UserRepositoryEloquent
     */
    private $r_user = null;

    /**
     * @var RoleRepositoryEloquent|null
     */
    private $r_role = null;

    /**
     * @var ApiKeyRepositoryEloquent|null
     */
    private $r_apikey = null;

    public function __construct(
        UserRepositoryEloquent $r_user,
        RoleRepositoryEloquent $r_role,
        ApiKeyRepositoryEloquent $r_apikey
    )
    {
        parent::__construct();

        $this->set_current_module('users');

        $this->r_user = $r_user;
        $this->r_role = $r_role;
        $this->r_apikey = $r_apikey;

        $this->addBreadcrumb('Users', 'admin/users');
    }

    /**
     * @param IFormRequest $request
     * @param bool|false $usePartial
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(IFormRequest $request, $usePartial = false)
    {
        $name = $request->has('name')
            ? $request->get('name')
            : null;

        $email = $request->has('email')
            ? $request->get('email')
            : null;

        if (!is_null($name)) {
            $this->r_user->filterUserName($name);
        }

        if (!is_null($email)) {
            $this->r_user->filterEmail($email);
        }

        $users = $this->r_user->paginate(config('app.pagination'));

        return $this->output(
            $usePartial
                ? 'users.admin.users.chunks.index_tables'
                : 'users.admin.users.index',
            [
                'users' => $users,
                'nb_users' => $this->r_user->allCount(),
                'filters' => [
                    'name' => $name,
                    'email' => $email,
                ]
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Exclude user role because always added to every new user
        $roles = $this->r_role->findWhereNotIn('name', ['user']);

        return $this->output(
            'users.admin.users.create',
            [
                'roles' => $roles
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function store(IFormRequest $request)
    {

        dd( $request->all() );

        $user = $this->r_user->create_user(
            $request->get('first_name'),
            $request->get('last_name'),
            $request->get('email')
        );

        $this->r_apikey->generate_api_key($user);

        $roles = $request->only('user_role_id');

        if (count($roles['user_role_id']) > 0) {
            $user->roles()->attach($roles['user_role_id']);
        }

        event(new UserCreatedEvent($user));

        return $this->redirectTo('admin/users')
            ->with('message-success', 'users::admin.create.message.success');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $user = $this->r_user->find($id);

        return $this->output(
            'users.admin.users.show',
            [
                'user' => $user
            ]
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function edit($id)
    {
        $user = $this->r_user->find($id);

//        dd( $user->addresses() );
//        dd( $user->primaryAddress() );
//        dd( $user->billingAddress() );
//        dd( $user->shippingAddress() );

        // On exclue le role user qui est ajoute par defaut
        $roles = $this->r_role->findWhereNotIn('name', ['user']);

        return $this->output(
            'users.admin.users.edit',
            [
                'user' => $user,
                'roles' => $roles
            ]
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     * @return Response
     */
    public function update($id, IFormRequest $request)
    {

        dd( $request->all() );


        if (Addresses::getValidator()->fails()) {

            die('fails');

        }
        else {
            echo 'youpi';
        }


        $address = Addresses::createAddress($request->all());




        exit;



        $user = $this->r_user->update([
            'first_name' => $request->get('first_name'),
            'last_name' => $request->get('last_name'),
            'email' => $request->get('email')
        ], $id);

        $roles = $request->only('user_role_id');

        $user->roles()->detach();
        // Always attach client role
        $role = $this->r_role->role_exists(RoleRepositoryEloquent::USER);
        $user->attachRole($role);

        if (count($roles['user_role_id']) > 0) {
            $user->roles()->attach($roles['user_role_id']);
        }

        event(new UserUpdatedEvent($user));

        return $this->redirectTo('admin/users')
            ->with('message-success', 'users::admin.edit.message.success');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $this->r_user->findAndDelete($id);

        event(new UserDeletedEvent($id));

        return $this->redirectTo('admin/users')
            ->with('message-success', 'users::admin.delete.message.success');
    }

    public function destroy_multiple(IFormRequest $request)
    {
        $users = $request->only('users_multi_destroy');

        foreach ($users['users_multi_destroy'] as $user_id) {
            $this->r_user->findAndDelete($user_id);
            event(new UserDeletedEvent($user_id));
        }

        return $this->redirectTo('admin/users')
            ->with('message-success', 'users::admin.delete_multiple.message.success');
    }

    /**
     * @param $id
     * @return mixed
     */
    public function impersonate($id)
    {
        Session::set('impersonate_member', $id);
        return redirect('/');
    }

    /**
     * @return mixed
     */
    public function endimpersonate()
    {
        Session::forget('impersonate_member');
        return redirect('admin');
    }

    public function export(NewExcelFile $excel)
    {
        $this->r_user->setPresenter(new UsersAdminExcelPresenter());
        $users = $this->r_user->all();
        $nb_users = $this->r_user->allCount();

        return $excel->setTitle(trans(''))
            ->setCreator(Auth::user()->full_name)
            ->setCompany(env('APP_SITE_NAME'))
            ->setDescription(trans('users::admin.export.users_list.title'))
            ->sheet(
                trans('users::admin.export.users_list.sheet_title') . date('Y-m-d H\hi'),
                function ($sheet) use ($users, $nb_users) {

                    $sheet->prependRow([
                        '#',
                        trans('global.last_name'),
                        trans('global.first_name'),
                        trans('global.email'),
                        trans('global.role_s')
                    ]);

                    // Append row after row 2
                    $sheet->rows($users['data']);

                    // Append row after row 2
                    $sheet->appendRow($nb_users + 2, ['Total users : ' . $nb_users]);

                    /*
                     * Style
                     */

                    // Set black background
                    $sheet->row(1, function ($row) {
                        // Set font
                        $row->setFont(array(
                            'size' => '14',
                            'bold' => true
                        ));
                        $row->setAlignment('center');
                        $row->setValignment('middle');
                    });

                    // Freeze first row
                    $sheet->freezeFirstRow();

                    $sheet->cells('A2:D' . ($nb_users + 2), function ($cells) {

                        // Set font
                        $cells->setFont([
                            'size' => '12',
                            'bold' => false
                        ]);
                        $cells->setAlignment('center');
                        $cells->setValignment('middle');

                    });

                    // Set black background
                    $sheet->row($nb_users + 2, function ($row) {
                        // Set font
                        $row->setFont([
                            'size' => '12',
                            'bold' => true
                        ]);
                        $row->setAlignment('center');
                        $row->setValignment('middle');
                    });

            })->export('xls');
    }
}