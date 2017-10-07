<?php

namespace pdaleramirez\LaravelNeo4jStarter\Extensions;

use Illuminate\Validation\PresenceVerifierInterface;

class Neo4jPresenceVerifier implements PresenceVerifierInterface
{
  public function getCount($collection, $column, $value, $excludeId = null, $idColumn = null, array $extra = array())
  {
		// Default neo4j label for default installation
  	if ($collection == 'user')
	  {
	  	$collection = ucwords($collection);
	  }

		$exs = '';
		if(!is_null($excludeId))
		{
			$excludeId = (int) $excludeId;
	    $exs = 'AND n.id <> {xid}';
	  }

	  $statusMatch = '';
	  $statusQuery = '';

		if (isset($extra['status']))
		{
			$statusMatch = "<-[:STATUS_OF]-(status)";
			$statusQuery = "AND status.value <> '" . $extra['status'] . "'";
		}

	  $queryString = "MATCH (n:" . $collection . ")$statusMatch
				WHERE n." . $column . " = {value} " . $exs . " $statusQuery
				RETURN n";

		$result = \Neo4jQuery::getResultSet($queryString, array('value' => $value, 'xid' => $excludeId));

	  return $result->count();
  }

  public function getMultiCount($collection, $column, array $values, array $extra = array())
  {
		return array();
  }

	public function setConnection($connection)
	{
		$this->connection = $connection;
	}
}