<?php


namespace VATSIMUK\Auth\Remote\RemoteEloquent;


trait HasCustomInstanceCreation
{
    /**
     * Create a new model instance for a related model.
     *
     * @param  string  $class
     * @return mixed
     */
    protected function newRelatedInstance($class)
    {
        if(is_object($class)){
            return $class;
        }
        
        return parent::newRelatedInstance($class);
    }
}
