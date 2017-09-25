<?php

namespace pdaleramirez\LaravelNeo4jStarter\Passwords;

use pdaleramirez\LaravelNeo4jStarter\Models\TokenPassword;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

class Neo4jTokenRepository implements TokenRepositoryInterface
{
	/**
	 * The Hasher implementation.
	 *
	 * @var \Illuminate\Contracts\Hashing\Hasher
	 */
	protected $hasher;

	/**
	 * The token database table.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The hashing key.
	 *
	 * @var string
	 */
	protected $hashKey;

	/**
	 * The number of seconds a token should last.
	 *
	 * @var int
	 */
	protected $expires;

	/**
	 * Create a new token repository instance.
	 *
	 * @param  \Illuminate\Database\ConnectionInterface  $connection
	 * @param  \Illuminate\Contracts\Hashing\Hasher  $hasher
	 * @param  string  $table
	 * @param  string  $hashKey
	 * @param  int  $expires
	 * @return void
	 */
	public function __construct(HasherContract $hasher,
	                            $table, $hashKey, $expires = 60)
	{
		$this->table = $table;
		$this->hasher = $hasher;
		$this->hashKey = $hashKey;
		$this->expires = $expires * 60;
	}

	/**
	 * Create a new token record.
	 *
	 * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
	 * @return string
	 */
	public function create(CanResetPasswordContract $user)
	{
		$email = $user->getEmailForPasswordReset();

		$this->deleteExisting($user);

		// We will create a new, random token for the user so that we can e-mail them
		// a safe link to the password reset form. Then we will insert a record in
		// the database so that we can verify the token within the actual reset.
		$token = $this->createNewToken();

		TokenPassword::create($this->getPayload($email, $token));

		return $token;
	}

	/**
	 * Delete all existing reset tokens from the database.
	 *
	 * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
	 * @return int
	 */
	protected function deleteExisting(CanResetPasswordContract $user)
	{
		$token = TokenPassword::findByAttribute('email', $user->getEmailForPasswordReset());

		if ($token)
		{
			return $token->delete();
		}
	}

	/**
	 * Build the record payload for the table.
	 *
	 * @param  string  $email
	 * @param  string  $token
	 * @return array
	 */
	protected function getPayload($email, $token)
	{
		$date = Carbon::now();

		return ['email' => $email, 'token' => $this->hasher->make($token), 'created_at' => $date->toDateTimeString()];
	}

	/**
	 * Determine if a token record exists and is valid.
	 *
	 * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
	 * @param  string  $token
	 * @return bool
	 */
	public function exists(CanResetPasswordContract $user, $token)
	{
		$record = TokenPassword::findByAttribute('email', $user->getEmailForPasswordReset());

		return $record &&
			!$this->tokenExpired($record->created_at) &&
			$this->hasher->check($token, $record->token);
	}

	/**
	 * Determine if the token has expired.
	 *
	 * @param  string  $createdAt
	 * @return bool
	 */
	protected function tokenExpired($createdAt)
	{
		return Carbon::parse($createdAt)->addSeconds($this->expires)->isPast();
	}

	/**
	 * Delete a token record by user.
	 *
	 * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
	 * @return void
	 */
	public function delete(CanResetPasswordContract $user)
	{
		$this->deleteExisting($user);
	}

	/**
	 * Delete expired tokens.
	 *
	 * @return void
	 */
	public function deleteExpired()
	{
		$expiredAt = Carbon::now()->subSeconds($this->expires);

		$expiredTime = $expiredAt->toDateTimeString();

		$queryArgs = array(
			'time' => $expiredTime
		);
		$model = new TokenPassword();
		$label = $model->getLabel();
		$queryString = "
			MATCH (node:$label)
			WHERE node.created_at < {time}
			DELETE node";

		\Neo4jQuery::getResultSet($queryString, $queryArgs);
	}

	/**
	 * Create a new token for the user.
	 *
	 * @return string
	 */
	public function createNewToken()
	{
		return hash_hmac('sha256', Str::random(40), $this->hashKey);
	}

	/**
	 * Get the hasher instance.
	 *
	 * @return \Illuminate\Contracts\Hashing\Hasher
	 */
	public function getHasher()
	{
		return $this->hasher;
	}
}