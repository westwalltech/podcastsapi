<?php

namespace NewSong\PodcastLinkFinder\GraphQL;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PodcastLinksType extends GraphQLType
{
    protected $attributes = [
        'name' => 'PodcastLinks',
        'description' => 'Links to a podcast episode across multiple platforms'
    ];

    public function fields(): array
    {
        return [
            'episode_id' => [
                'type' => Type::string(),
                'description' => 'The Transistor episode ID',
            ],
            'episode_title' => [
                'type' => Type::string(),
                'description' => 'The episode title',
            ],
            'spotify' => [
                'type' => \GraphQL::type('PlatformLink'),
                'description' => 'Spotify platform link',
            ],
            'apple_podcasts' => [
                'type' => \GraphQL::type('PlatformLink'),
                'description' => 'Apple Podcasts platform link',
            ],
            'youtube' => [
                'type' => \GraphQL::type('PlatformLink'),
                'description' => 'YouTube platform link',
            ],
            'has_any_links' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether any platform links exist',
            ],
        ];
    }
}
