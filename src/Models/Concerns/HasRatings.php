<?php

namespace VATSIMUK\Support\Auth\Models\Concerns;

use Illuminate\Support\Collection;

trait HasRatings
{
    /**
     * Defines fields used for the atcRating field sub-selection.
     *
     * @uses VATSIM-UK/auth/graphql/schema.graphql - type Rating
     * @var array
     */
    private $RATINGS_SCHEMA = [
        'id',
        'type',
        'code',
        'code_long',
        'name',
        'name_long',
        'vatsim_id'
    ];

    /**
     * Request the data for the ATC rating of the RemoteUser.
     *
     * @return mixed
     */
    public function getATCRatingAttribute()
    {   
        return isset($this->attributes['atcRating']) ?
            $this->attributes['atcRating'] :
            $this->attributes['atcRating'] = static::find($this->id, ['atcRating' => $this->RATINGS_SCHEMA])
                ->attributes['atcRating'];
    }

    /**
     * Request the data for the Pilot rating(s) of the RemoteUser.
     *
     * @return Collection |null
     */
    public function getPilotRatingsAttribute()
    {
        if(isset($this->attributes['pilotRatings'])){
            return $this->attributes['pilotRatings'];
        }

        $ratings = static::find($this->id, ['pilotRatings' => $this->RATINGS_SCHEMA])->attributes['pilotRatings'];

        return $ratings ? collect($ratings) : null;
    }
}