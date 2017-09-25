<?php

namespace pdaleramirez\LaravelNeo4jStarter\Services;

class Neo4jQueryService
{

  private $neo4j;
  private $queryString = '';
	private $queries = array();
	private $transaction = null;

  public function __construct()
  {
    $this->neo4j = \App::make("neo4j");
  }

	/**
	 * Used for DELETE, STORE AND UPDATE	use default for retrieval
	 *
	 * @param       $string
	 * @param array $args
	 *
	 * @return \Everyman\Neo4j\Query\ResultSet
	 */
  public function getResultSet($string, $args = array())
  {
	  $neo4j = $this->neo4j;

	  $this->queryString = $this->queryString . $string . '<br /><br />'; // concat all queries in a page

	  $query = new \Everyman\Neo4j\Cypher\Query($neo4j, $string, $args);

	  $result = $query->getResultSet();

		return $result;
  }

	public function addQuery($string, $args = array())
	{
		$this->queries[] = new \Everyman\Neo4j\Cypher\Query($this->neo4j, $string, $args);
	}

	/**
	 * Get all queries
	 * @return array
	 */
	public function getQueries()
	{
		return $this->queries;
	}

	public function beginTransaction()
	{
		$client = $this->neo4j;

		$this->transaction = $client->beginTransaction();

		return $this->transaction;
	}

	public function addStatements(array $inputQueries = array())
	{
		$queries = $this->queries;
		return $this->transaction->addStatements(array_merge($queries, $inputQueries));
	}

	public function commit()
	{
		return $this->transaction->commit();
	}

	public function rollback()
	{
		return $this->transaction->rollback();
	}

	public function isClosed()
	{
		return $this->transaction->isClosed();
	}

	public function isError()
	{
		return $this->transaction->isError();
	}

	public function keepAlive()
	{
		return $this->transaction->keepAlive();
	}


	public function getTransaction()
	{
		return $this->transaction;
	}

  public function getClient()
  {
    return $this->neo4j;
  }

	/**
	 * Get concatenated query strings
	 * @return string
	 */
  public function getQueryString()
  {
      return $this->queryString;
  }
}