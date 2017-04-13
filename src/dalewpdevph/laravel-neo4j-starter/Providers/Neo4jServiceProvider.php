<?php namespace dalewpdevph\LaravelNeo4jStarter\Providers;

use dalewpdevph\LaravelNeo4jStarter\Passwords\PasswordBrokerManager;
use Illuminate\Support\ServiceProvider;
use Everyman\Neo4j\Client;

class Neo4jServiceProvider extends ServiceProvider {

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		\Auth::provider('neo4jauth',function($app, array $config)
		{
			$model = $config['model'];
			return new \dalewpdevph\LaravelNeo4jStarter\Extensions\Neo4jAuthProvider($app['hash'], $model);
		});

		\Session::extend('neo4jsession', function($app) {

			$lifetime = $this->app['config']['session.lifetime'];

			return new \dalewpdevph\LaravelNeo4jStarter\Extensions\Neo4jSessionHandler($lifetime, $app);
		});

		$this->app['validator']->setPresenceVerifier($this->app['neo4jPresence']);
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		// Register 'neo4j' instance container to our 'neo4j' object
		$this->app->bind("neo4j", function($app)
		{
			// connection credentials loaded from config
			$host     = env('NEO4J_HOST', 'localhost');
			$port     = env('NEO4J_PORT', '7474');
			$username = env('NEO4J_USERNAME', 'neo4j');
			$password = env('NEO4J_PASSWORD', 'neo4j');

			// create new neo4j node
			$neo4j = new Client($host,$port);
			$neo4j->getTransport()->setAuth($username, $password);

			// return pusher
			return $neo4j;
		});

		$this->app->bind("neo4jQuery", function() {
			return new \dalewpdevph\LaravelNeo4jStarter\Services\Neo4jQueryService();
		});

		// Extend the unique validation
		$this->app->bind("neo4jPresence", function() {
			return new \dalewpdevph\LaravelNeo4jStarter\Extensions\Neo4jPresenceVerifier;
		});

		$this->app->singleton('auth.password.neo4j', function ($app) {
			return new PasswordBrokerManager($app);
		});
	}
}
