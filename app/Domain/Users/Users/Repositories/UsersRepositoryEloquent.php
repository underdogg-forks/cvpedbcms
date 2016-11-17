<?php namespace cms\Domain\Users\Users\Repositories;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Container\Container as Application;
use cms\Infrastructure\Abstractions\Repositories\RepositoryEloquentAbstract;
use cms\Domain\Users\Users\Repositories\UsersRepository;
use cms\Domain\Environments\Environments\Repositories\EnvironmentsRepositoryEloquent;
use cms\Domain\Users\SocialTokens\Repositories\SocialTokenRepositoryEloquent;
use cms\Domain\Users\Users\Criterias\OnlyTrashedCriteria;
use cms\Domain\Users\Users\Criterias\WithTrashedCriteria;
use cms\Domain\Users\Users\Criterias\EmailLikeCriteria;
use cms\Domain\Users\Users\Criterias\UserNameLikeCriteria;
use cms\Domain\Users\Users\Criterias\RolesCriteria;
use cms\Domain\Users\Users\Criterias\EnvironmentsCriteria;
use cms\Domain\Users\Users\Events\UserCreatedEvent;
use cms\Domain\Users\Users\Events\UserUpdatedEvent;
use cms\Domain\Users\Users\Events\UserDeletedEvent;
use cms\Domain\Users\Users\Events\NewUserCreatedEvent;
use cms\Domain\Users\Users\Events\NewAdminCreatedEvent;
use cms\Domain\Users\Users\User;

/**
 * Class UsersRepositoryEloquent
 * @package cms\Domain\Users\Users\Repositories
 */
class UsersRepositoryEloquent extends RepositoryEloquentAbstract implements UsersRepository
{

	/**
	 * @var EnvironmentsRepositoryEloquent|null
	 */
	protected $r_environments = null;

	/**
	 * @var SocialTokenRepositoryEloquent|null
	 */
	protected $r_social_tokens = null;

	/**
	 * @var array Civilities available to fill civility field in users table.
	 */
	protected $civilities = [
		'madam'  => 'global.madam',
		'miss'   => 'global.miss',
		'mister' => 'global.mister',
	];

	/**
	 * UsersRepositoryEloquent constructor.
	 *
	 * @param Application                    $app
	 * @param EnvironmentsRepositoryEloquent $r_environments
	 * @param SocialTokenRepositoryEloquent  $r_social_tokens
	 */
	public function __construct(
		Application $app,
		EnvironmentsRepositoryEloquent $r_environments,
		SocialTokenRepositoryEloquent $r_social_tokens
	)
	{
		parent::__construct($app);

		$this->r_environments = $r_environments;
		$this->r_social_tokens = $r_social_tokens;
	}

	/**
	 * Specify Model class name
	 *
	 * @return string
	 */
	public function model()
	{
		return User::class;
	}

	/**
	 * @return \Illuminate\Support\Collection
	 */
	public function getCivilitiesList()
	{
		return collect($this->civilities)
			->map(function ($translation_key, $civility_key)
			{
				return trans($translation_key);
			});
	}

	/**
	 * Create user and fire event "UserCreatedEvent".
	 *
	 * @param array $attributes
	 *
	 * @event cms\Domain\Users\Users\Events\UserUpdatedEvent
	 * @return \cms\Domain\Users\Users\User
	 */
	public function create(array $attributes)
	{
		$user = parent::create($attributes);

		event(new UserCreatedEvent($user));

		return $user;
	}

	/**
	 * Update user and fire event "UserUpdatedEvent".
	 *
	 * @param array   $attributes
	 * @param integer $user_id
	 *
	 * @event cms\Domain\Users\Users\Events\UserUpdatedEvent
	 * @return \cms\Domain\Users\Users\User
	 */
	public function update(array $attributes, $user_id)
	{
		$user = parent::update($attributes, $user_id);

		event(new UserUpdatedEvent($user));

		return $user;
	}

	/**
	 * Delete user and fire event "UserDeletedEvent".
	 *
	 * @param array   $attributes
	 * @param integer $user_id
	 *
	 * @event cms\Domain\Users\Users\Events\UserDeletedEvent
	 * @return int
	 */
	public function delete($id)
	{
		$user = parent::delete($id);

		event(new UserDeletedEvent($id));

		return $user;
	}

	/**
	 * Get the list of all user, active and soft deleted users.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection|static[]
	 */
	public function allWithTrashed()
	{
		return User::withTrashed()->get();
	}

	/**
	 * Get only user that was soft deleted.
	 *
	 * @return \Illuminate\Database\Eloquent\Collection|static[]
	 */
	public function onlyTrashed()
	{
		return User::onlyTrashed()->get();
	}

	/**
	 * Filter users by name.
	 *
	 * @param string $name The user last name or user first name
	 *
	 * @throws \Prettus\Repository\Exceptions\RepositoryException
	 */
	public function filterUserName($name)
	{
		$this->pushCriteria(new UserNameLikeCriteria($name));
	}

	/**
	 * Filter users by emails.
	 *
	 * @param string $email The user email
	 *
	 * @throws \Prettus\Repository\Exceptions\RepositoryException
	 */
	public function filterEmail($email)
	{
		$this->pushCriteria(new EmailLikeCriteria($email));
	}

	/**
	 * Filter users by roles.
	 *
	 * @param array $roles the list of roles IDs
	 *
	 * @throws \Prettus\Repository\Exceptions\RepositoryException
	 */
	public function filterRoles($roles = [])
	{
		$roles = array_filter($roles);

		if (count($roles))
		{
			$this->pushCriteria(new RolesCriteria($roles));
		}
	}

	/**
	 * Display all users with trashed users.
	 *
	 * @throws \Prettus\Repository\Exceptions\RepositoryException
	 */
	public function filterShowWithTrashed()
	{
		$this->pushCriteria(new WithTrashedCriteria());
	}

	/**
	 * Display only trashed user.
	 *
	 * @throws \Prettus\Repository\Exceptions\RepositoryException
	 */
	public function filterShowOnlyTrashed()
	{
		$this->pushCriteria(new OnlyTrashedCriteria());
	}

	/**
	 * Filter users by environments.
	 *
	 * @param array $envs the list of environment IDs
	 *
	 * @throws \Prettus\Repository\Exceptions\RepositoryException
	 */
	public function filterEnvironments($envs = [])
	{
		$envs = array_filter($envs);

		if (count($envs))
		{
			$this->pushCriteria(new EnvironmentsCriteria($envs));
		}
	}

	/**
	 * Create a new user with role RoleRepository::USER.
	 *
	 * @param string $first_name
	 * @param string $last_name
	 * @param string $email
	 *
	 * @return mixed
	 */
	public function create_user(
		$civility,
		$first_name,
		$last_name,
		$email,
		$birth_date = '0000-00-00'
	)
	{
		$user = $this->create([
			'civility'   => $civility,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'email'      => $email,
			'birth_date' => $birth_date,
		]);

		event(new NewUserCreatedEvent($user));

		return $user;
	}

	/**
	 * Create a new user with role RoleRepository::ADMIN.
	 *
	 * @param string $first_name
	 * @param string $last_name
	 * @param string $email
	 *
	 * @return mixed
	 */
	public function create_admin(
		$civility,
		$first_name,
		$last_name,
		$email,
		$birth_date = '0000-00-00'
	)
	{
		$user = $this->create_user(
			$civility,
			$first_name,
			$last_name,
			$email,
			$birth_date
		);

		event(new NewAdminCreatedEvent($user));

		return $user;
	}

	/**
	 * Find user by ID and soft delete him.
	 *
	 * @param integer $user_id The user ID
	 *
	 * @throws \Exception
	 */
	public function findAndDelete($user_id)
	{
		if ($user_id == Auth::user()->id)
		{
			throw new \Exception(
				trans('domain/users.findanddelete.you_can_not_delete_your_account')
			);
		}

		$user = $this->find($user_id);

		/*
		 * delete user
		 */

		$this->delete($user_id);
	}

	/**
	 * @param \cms\Domain\Users\Users\User $user
	 * @param array                        $environments_reference
	 *
	 * @return mixed
	 */
	public function set_user_environments(User $user, array $environments_reference = [])
	{
		if (count($environments_reference) > 0)
		{
			$environments_rows = $this->r_environments
				->findWhereIn('reference', $environments_reference);

			$user->environments()->detach();

			$environments_rows
				->each(function ($env) use (&$user)
				{
					$user->environments()->attach($env->id);
				});
		}

		return $user;
	}

	/**
	 * Change user password and fire event "UserUpdatedEvent".
	 *
	 * @param integer $user_id The user ID
	 * @param string  $old_password
	 * @param string  $new_password
	 * @param bool    $force
	 *
	 * @event cms\Domain\Users\Users\Events\UserUpdatedEvent
	 * @return bool
	 */
	public function set_user_password($user_id, $old_password, $new_password, $force = false)
	{
		$user = $this->find($user_id);

		if (Hash::check($old_password, $user->password) || $force)
		{
			$data = [
				'password' => Hash::make($new_password)
			];

			$user->fill($data)->save();

			event(new UserUpdatedEvent($user));

			return true;
		}

		return false;
	}

	/**
	 * Allow to send the reset password link by mail via PasswordBroker.
	 *
	 * @param integer $user_id The user ID
	 *
	 * @return mixed
	 */
	public function send_reset_password_link($user_id)
	{
		$broker = null;

		$user = $this->find($user_id);

		return Password::broker($broker)->sendResetLink(
			[
				'email' => $user->email
			],
			function (Message $message)
			{
				$message->subject(trans('passwords.mail_reset_password_title'));
			}
		);
	}

}
