<?php

namespace dalewpdevph\LaravelNeo4jStarter\Models;

use Everyman\Neo4j\Node;
use dalewpdevph\LaravelNeo4jStarter\Traits\QueryAble;

abstract class BaseModel
{
	use QueryAble;

	public $attributes    = array();
	protected $primaryKey = 'id';
	protected $label      = null;

	/**
	 * @var Node
	 */
	private $node   = null;
	private $exists = false;
	private $queryString;
	private $queryArgs;
	private $paramKey;
	private $paramValue;


	protected $values = array();

	public function __construct()
	{
		$this->initLabel();
	}

	public function getAuthIdentifierName()
	{
		return $this->primaryKey;
	}

	private function initLabel()
	{
		$reflect =  new \ReflectionClass(get_called_class());

    if ($this->getLabel() == null)
    {
        // Default class name
        $this->label = $reflect->getShortName();
    }
    else
    {
        // Get defined child label
        $this->label = $this->getLabel();
    }
	}

	public function defineLabel($label = null)
	{
		if($label != null)
		{
			$this->label = $label;
		}

		return $this->label;
	}

	public function getLabel()
	{
		return $this->label;
	}

	public function getKeyName()
	{
		return $this->primaryKey;
	}

	function getKey()
	{
		return $this->getAttribute($this->getKeyName());
	}

	public function getSaveQuery()
	{
		if(!empty($this->attributes))
		{
			$attributes = $this->getAttributes();
		}
		else
		{
			throw new \Exception('Define attributes to save');
		}

		$label = $this->label;

		$queryString = $this->getSaveModelQuery($attributes, $label);

		return $queryString;
	}

	public function setExists($bol)
	{
		$this->exists = $bol;
	}

	public function getUpdateQuery()
	{
		$label = $this->label;

		if(!empty($this->attributes) && $this->exists == true)
		{
			$attributes = $this->getAttributes();
			$updateString = 'SET ';
			$updateStringArray = array();
			foreach($attributes as $attributeKey => $attributeValue)
			{
				if($attributeKey != 'id')
				{
					$updateStringArray[] = 'n.' . $attributeKey . " = {" . $attributeKey . "}";
				}
			}

			$updateString .= implode(", ", $updateStringArray);

			$queryString = "
						MATCH (n:$label { id:{id} })
						$updateString
						RETURN n";

			return $queryString;
		}
		else
		{
			throw new \Exception('Define attributes to save');
		}
	}

	public function getAttributes()
	{
		if(!empty($this->attributes))
		{
			return $this->attributes;
		}
		else
		{
			throw new \Exception('Define attributes to save');
		}
	}

	public function save($transaction = false)
	{
		$save = new $this;

		$attributes = $this->getAttributes();

		/**
		 * This ensures that any model passed with ID attribute updates the node instead of creating new one.
		 */
		if (!empty($attributes['id']))
		{
			$this->exists = true;
		}

		if($this->exists)
		{
			$queryString = $this->getUpdateQuery();
		}
		else
		{
			$queryString = $this->getSaveQuery();
		}



		if ($transaction)
		{
			\Neo4jQuery::addQuery($queryString, $attributes);

			return true;
		}

		$save->addQueryString($queryString, $attributes);

		return $save->query();
	}

	public function getProperties()
	{
		$node = $this->node;

		$properties = null;

		if ($node != null)
		{
			$properties = $node->getProperties();

			if (!empty($properties))
			{
				foreach ($properties as $propk => $prop)
				{
					$this->setAttribute($propk, $prop);
				}

				return $properties;
			}

			return $properties;
		}
		else
		{
			throw new \Exception("Call the query method first.");
		}
	}

	/**
	 * @return Node
	 */
	public function getNode()
	{
		return $this->node;
	}

	public static function find($id)
	{
		$id = (int) $id;

		return static::findByAttribute('id', $id);
	}

	public static function findByAttribute($key, $value, $label = null)
	{
		$find = new static;

		if($label == null )
		{
			$label = $find->getLabel();
		}

		$queryString = $find->findByAttributeQuery($key, $label);

		$find->addQueryString($queryString, array('findValue' => $value));

		return $find->query();
	}

	protected function findByAttributeQuery($key, $label)
	{
		$queryString = "MATCH (n:$label)
						WHERE n.$key = {findValue}
						RETURN n";

		return $queryString;
	}

	public function getDeleteQuery($id)
	{
		return $this->getDeleteByAttributeQuery('id', $id);
	}

	public function getDeleteByAttributeQuery($key, $value)
	{
		$label = $this->label;
		$queryString = "MATCH (n:$label)
						WHERE n.$key = {" . $key . "}
						OPTIONAL MATCH (n)-[r]-()
						DELETE n, r";
		$this->paramKey = $key;
		$this->paramValue = $value;
		return $queryString;
	}

	public function getQueryParams()
	{
		return array($this->paramKey => $this->paramValue);
	}

	public function delete($transaction = false)
	{
		if(!$this->exists) return null;

		$id = $this->id;

		return $this->deleteByAttribute('id', $id, $transaction);
	}

	public function deleteByAttribute($key, $value, $transaction = false)
	{
		$label = $this->label;

		if(!static::findByAttribute($key, $value, $label))
		{
			throw new \Exception('Node to delete not found');
		}

		$queryString = $this->getDeleteByAttributeQuery($key, $value);
		if($transaction)
		{
			\Neo4jQuery::addQuery($queryString, $this->getQueryParams());

			return true;
		}

		$this->addQueryString($queryString, $this->getQueryParams());

		$this->query(true);

		if(static::findByAttribute($key, $value, $label))
		{
			// node not deleted
			return null;
		}
		else
		{
			return true;
		}
	}

	protected function query($returnResult = false)
	{
		$response = null;

		$queryString = $this->queryString;
		$queryArgs   = $this->queryArgs;

		$result = \Neo4jQuery::getResultSet($queryString, $queryArgs);

		if($returnResult)
		{
			return $result;
		}

		if ($result->count())
		{
			$this->exists = true;

			$node = $result[0]['n'];

			$this->node = $node;

			$this->getProperties();

			$response = $this;
		}

		return $response;
	}

	public static function create($attributes = array())
	{
		$model = new static;

		$model->setAttributes($attributes);

		return $model->save();
	}

	public function setAttribute($key, $value)
	{
		$this->attributes[$key] = $value;
	}

	function getAttribute($key)
	{
		$attributes = null;

		if (isset($this->attributes[$key]))
		{
			$attributes = $this->attributes[$key];
		}

		return $attributes;
	}

	public function setAttributes($attributes)
	{
		if(!empty($attributes))
		{
			foreach($attributes as $key => $value)
			{
				$this->setAttribute($key, $value);
			}
		}
	}

	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	public function __set($key, $value)
	{
		$this->setAttribute($key, $value);
	}

	public function forceFill($attributes)
	{
		$this->setAttributes($attributes);

		return $this;
	}
}
