<?php

namespace dalewpdevph\LaravelNeo4jStarter\Passwords;

use Illuminate\Auth\Passwords\PasswordBrokerManager as BasePasswordBrokerManager;
use Illuminate\Support\Str;

class PasswordBrokerManager extends BasePasswordBrokerManager
{
	/**
	 * Create a token repository instance based on the given configuration.
	 *
	 * @param  array  $config
	 * @return \Illuminate\Auth\Passwords\TokenRepositoryInterface
	 */
	protected function createTokenRepository(array $config)
	{
		$key = $this->app['config']['app.key'];

		if (Str::startsWith($key, 'base64:')) {
			$key = base64_decode(substr($key, 7));
		}

		return new Neo4jTokenRepository($this->app['hash'],	$config['table'],	$key,	$config['expire']);
	}
}