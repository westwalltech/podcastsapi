<?php

namespace NewSong\PodcastLinkFinder\GraphQL;

use GraphQL\Type\Definition\Type;
use Rebing\GraphQL\Support\Type as GraphQLType;

class PlatformLinkType extends GraphQLType
{
    protected $attributes = [
        'name' => 'PlatformLink',
        'description' => 'A link to a podcast episode on a specific platform',
    ];

    public function fields(): array
    {
        return [
            'url' => [
                'type' => Type::string(),
                'description' => 'The URL to the episode on this platform',
            ],
            'has_link' => [
                'type' => Type::nonNull(Type::boolean()),
                'description' => 'Whether a link exists for this platform',
            ],
        ];
    }
}
