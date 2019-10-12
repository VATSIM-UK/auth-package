<?php

namespace VATSIMUK\Auth\Remote\Models\Concerns;

trait HasRatings
{
    public function getATCRatingAttribute()
    {   
        return isset($this->attributes['atcRating']) ? $this->attributes['atcRating'] : $this->attributes['atcRating'] = static::find($this->id, ['atcRating' => [
                'code',
                'type',
                'name_small',
                'name_long',
                'name_grp',
                'vatsim'
            ]])->attributes['atcRating'];
    }

    public function getPilotRatingsAttribute()
    {
        if(isset($this->attributes['pilotRatings'])){
            return $this->attributes['pilotRatings'];
        }

        $ratings = static::find($this->id, ['pilotRatings' => [
            'code',
            'type',
            'name_small',
            'name_long',
            'name_grp',
            'vatsim'
        ]])->attributes['pilotRatings'];

        return $ratings ? collect($ratings) : null;
    }
}