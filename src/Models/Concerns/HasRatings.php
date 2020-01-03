<?php

namespace VATSIMUK\Support\Auth\Models\Concerns;

use Illuminate\Support\Collection;
use stdClass;
use VATSIMUK\Support\Auth\Models\RemoteBuilder;

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

    public function scopeWithRatings(RemoteBuilder $query)
    {
        return $query->withColumns([
            ['atcRating' => $this->RATINGS_SCHEMA],
            ['pilotRating' => $this->RATINGS_SCHEMA]
        ]);
    }

    /**
     * Request the data for the ATC rating of the RemoteUser.
     *
     * @return stdClass
     */
    public function getATCRatingAttribute()
    {
        return (object) $this->loadMissingAttributes(['atcRating' => $this->RATINGS_SCHEMA])->attributes['atcRating'];
    }

    /**
     * Request the data for the Pilot rating(s) of the RemoteUser.
     *
     * @return Collection|null
     */
    public function getPilotRatingsAttribute()
    {
        $ratings = $this->loadMissingAttributes(['pilotRatings' => $this->RATINGS_SCHEMA])->attributes['pilotRatings'];

        return $ratings ? collect($ratings)->map(function ($rating){
            return (object) $rating;
        }) : null;
    }
}