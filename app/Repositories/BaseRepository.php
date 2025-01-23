<?php

namespace App\Repositories;

use Illuminate\Container\Container as Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;

abstract class BaseRepository
{
    use ForwardsCalls;

    private $model;

    /**
     * Specify Model class name
     *
     * @return string
     */
    abstract public function model();

    /**
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $method = 'fetch'.ucfirst($method);

        return (new static)->forwardScopeCall($method, $parameters);
    }

    /**
     * @param  Application  $app
     */
    public function __construct()
    {
        $this->app = new Application;
        $this->makeModel();
    }

    /**
     * @return Model
     *
     * @throws RepositoryException
     */
    public function makeModel()
    {
        $model = $this->app->make($this->model());

        if (! $model instanceof Model) {
            throw new \Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        $this->model = $model;

        return $this->model;
    }

    /**
     * @return mixed
     */
    public function forwardScopeCall($method, $parameters)
    {
        // return $this->forwardCallTo($this, $method, $parameters);
        return $this->$method(...$parameters);
    }

    /**
     * forward call to connected model
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (strpos($method, 'fetch') !== false) {
            $method = substr($method, strlen('fetch'));
            $method = lcfirst($method);
        }

        return $this->forwardCallTo($this->model, $method, $parameters);
    }
}
