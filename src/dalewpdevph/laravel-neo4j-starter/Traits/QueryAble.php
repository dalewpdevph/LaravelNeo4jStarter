<?php

namespace dalewpdevph\LaravelNeo4jStarter\Traits;

trait QueryAble
{
	public function convertToCypherString($array)
	{
		$array = array_keys($array);

		$attributes = array();

		if (!empty($array))
		{
			foreach ($array as $key)
			{
				$attributes[] = "$key:{{$key}}";
			}
		}

		return implode(',', $attributes);
	}

	public function getSaveModelQuery($attributes, $label)
	{
		unset($attributes['id']);

		$inputString = $this->convertToCypherString($attributes);

		$queryString = "
				MERGE (id:UniqueId{name:'$label'})
				ON CREATE SET id.count = 1
				ON MATCH SET id.count = id.count + 1
				WITH id.count AS uid
				CREATE (n:$label{id:uid, " . $inputString . "})
				RETURN n";

		return $queryString;
	}

	protected function addQueryString($string, $args = array())
	{
		$this->queryString = $string;
		$this->queryArgs = $args;
	}
}