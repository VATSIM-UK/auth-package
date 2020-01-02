<?php


namespace VATSIMUK\Support\Auth\Models\Concerns;


trait HasCustomInstanceCreation
{
    /**
     * Create a new model instance for a related model.
     *
     * @param  object|string  $class
     * @return mixed
     */
    protected function newRelatedInstance($class)
    {
        if(is_object($class)){
            if(method_exists($class, 'setRelationshipBuilder')){
                $class->setRelationshipBuilder(true);
            }
            return $class;
        }

        return tap(resolve($class), function ($instance) {
            if (! $instance->getConnectionName()) {
                $instance->setConnection($this->connection);
            }
            if(method_exists($instance, 'setRelationshipBuilder')){
                $instance->setRelationshipBuilder(true);
            }
        });
    }
}
