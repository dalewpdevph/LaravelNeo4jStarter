<?php

namespace pdaleramirez\LaravelNeo4jStarter\Extensions;

use Carbon\Carbon;
use SessionHandlerInterface;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Session\ExistenceAwareInterface;
use Illuminate\Contracts\Container\Container;

class Neo4jSessionHandler implements SessionHandlerInterface, ExistenceAwareInterface
{
	/*
	 * The number of minutes the session should be valid.
	 *
	 * @var int
	 */
	protected $minutes;

	/**
	 * The container instance.
	 *
	 * @var \Illuminate\Contracts\Container\Container
	 */
	protected $container;

	/**
	 * The existence state of the session.
	 *
	 * @var bool
	 */
	protected $exists;

	public function __construct($minutes, Container $container = null)
	{
		$this->minutes = $minutes;
		$this->container = $container;
	}


	/**
	 * {@inheritdoc}
	 */
	public function open($savePath, $sessionName)
	{
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function close()
	{
		return true;
	}

	public function read($sessionId)
	{
		$queryArgs = array(
			'id' => $sessionId
		);
		$queryString = "
				MATCH (node:Session {id: {id}})
				RETURN node";

		$result = \Neo4jQuery::getResultSet($queryString, $queryArgs);

		if ($result->count())
		{
			$session = $result[0]['node'];

			if (isset($session->last_activity)) {
				if ($session->last_activity < Carbon::now()->subMinutes($this->minutes)->getTimestamp()) {
					$this->exists = true;

					return;
				}
			}

			if (isset($session->payload)) {
				$this->exists = true;

				return base64_decode($session->payload);
			}
		}
	}

	public function write($sessionId, $data)
	{
		$payload = $this->getDefaultPayload($data);

		if (!$this->exists)
		{
			$this->read($sessionId);
		}

		if ($this->exists)
		{
			$payload['id'] = $sessionId;

			$queryArgs = array(
				'id'      => $sessionId,
				'session' => $payload
			);

			$queryString = "
			MATCH (node:Session {id: {id}})
			SET node = {session}
			RETURN node";

			$result =  \Neo4jQuery::getResultSet($queryString, $queryArgs);

			if ($result->count())
			{
				$node = $result[0]['node'];
			}
		}
		else
		{
			$payload['id'] = $sessionId;

			$queryArgs = array(
				'session' => $payload
			);

			$queryString = "
				CREATE (node:Session {session})
				RETURN node";

			\Neo4jQuery::getResultSet($queryString, $queryArgs);
		}

		$this->exists = true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function destroy($sessionId)
	{
		$queryArgs = array(
			'id' => $sessionId
		);

		$queryString = "
		MATCH (node:Session)
		WHERE node.id = {id}
		DELETE node";

		$result =  \Neo4jQuery::getResultSet($queryString, $queryArgs);

		if ($result->count())
		{
			$node = $result[0]['node'];
		}
	}

	public function gc($lifetime)
	{
		$queryArgs = array(
			'time' => time() - $lifetime
		);
		$queryString = "
		MATCH (node:Session)
		WHERE node.last_activity <= {time}
		DELETE node";

		\Neo4jQuery::getResultSet($queryString, $queryArgs);
	}

	/**
	 * Get the default payload for the session.
	 *
	 * @param  string  $data
	 * @return array
	 */
	protected function getDefaultPayload($data)
	{
		$payload = ['payload' => base64_encode($data), 'last_activity' => time()];

		if (! $container = $this->container) {
			return $payload;
		}

		if ($container->bound(Guard::class)) {
			$payload['user_id'] = $container->make(Guard::class)->id();
		}
		else
		{
			$payload['user_id'] = null;
		}

		if ($container->bound('request')) {
			$payload['ip_address'] = $container->make('request')->ip();

			$payload['user_agent'] = substr(
				(string) $container->make('request')->header('User-Agent'), 0, 500
			);
		}

		return $payload;
	}

	/**
	 * Set the existence state for the session.
	 *
	 * @param  bool  $value
	 * @return $this
	 */
	public function setExists($value)
	{
		$this->exists = $value;

		return $this;
	}
}