<?php namespace dalewpdevph\LaravelNeo4jStarter\Facades;

use Illuminate\Support\Facades\Facade;

class Neo4jQueryFacade extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'neo4jQuery'; }

}