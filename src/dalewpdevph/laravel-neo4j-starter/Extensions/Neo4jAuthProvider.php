<?php namespace dalewpdevph\LaravelNeo4jStarter\Extensions;

use dalewpdevph\LaravelNeo4jStarter\Models\User;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

class Neo4jAuthProvider implements UserProvider {


	protected $model;
	protected $hasher;

	public function __construct(HasherContract $hasher, $model)
	{
		$this->model = $model;
		$this->hasher = $hasher;
	}

	private function userArray(User $user)
	{
		$userArray = array();
		$userArray['id']        = $user->id;
		$userArray['email']     = $user->email;
		$userArray['password']  = $user->password;
		$userArray['name']      = $user->name;
		$userArray['remember_token'] = $user->remember_token;

		return $userArray;
	}

	protected function getGenericUser($user)
	{
		if ($user !== null)
		{
			return new \Illuminate\Auth\GenericUser((array) $user);
		}

		return $user;
	}

	public function retrieveByID($identifier)
	{
		$model = $this->createModel();

		$user = $model::find($identifier);

		if ($user != null)
		{
			$userArray = $this->userArray($user);

			return $this->getGenericUser((array) $userArray);
		}

		return $user;
	}

	public function retrieveByToken($identifier, $token)
	{

		$namespace = $this->createModel();
		$model = new $namespace;

		if($user = $model::findByAttribute("remember_token", $token))
		{
			$userArray = $this->userArray($user);

			return $this->getGenericUser((array) $userArray);
		}

		return $model;
	}

	/**
	 * Update the "remember me" token for the given user in storage.
	 *
	 * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
	 * @param  string  $token
	 * @return void
	 */
	public function updateRememberToken(UserContract $user, $token)
	{
		$id = $user->getAuthIdentifier();

		$model = $this->createModel();

		$user = $model::find($id);
		$user->remember_token = $token;
		$user->save();
	}

	/**
	 * Retrieve a user by the given credentials.
	 *
	 * @param  array  $credentials
	 *
	 * @return \Illuminate\Contracts\Auth\Authenticatable|null
	 */
	public function retrieveByCredentials(array $credentials)
	{
		$model = $this->createModel();

		if($user = $model::findByAttribute("email", $credentials['email']))
		{
			return $user;
		}

		return $model;
	}

	/**
	 * Validate a user against the given credentials.
	 *
	 * @param \Illuminate\Contracts\Auth\Authenticatable  $user
	 * @param  array  $credentials
	 *
	 * @return bool
	 */
	public function validateCredentials(UserContract $user, array $credentials)
	{
		$plain = $credentials['password'];
		return $this->hasher->check($plain, $user->getAuthPassword());

	}

	/**
	 * Create a new instance of the model.
	 *
	 * @return \dalewpdevph\LaravelNeo4jStarter\Models\User
	 */
	public function createModel()
	{
		$class = '\\'.ltrim($this->model, '\\');

		return new $class;
	}

}