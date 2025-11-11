<?php

namespace NewSong\PodcastLinkFinder\Fieldtypes;

use Statamic\Fields\Fieldtype;
use GraphQL\Type\Definition\Type;

class PodcastLinkFinder extends Fieldtype
{
    protected $icon = 'podcast';

    protected $categories = ['media'];

    /**
     * The blank/default value
     *
     * @return array
     */
    public function defaultValue()
    {
        return [
            'episode_id' => null,
            'episode_title' => null,
            'spotify_link' => null,
            'apple_podcasts_link' => null,
            'youtube_link' => null,
        ];
    }

    /**
     * Pre-process the data before it gets sent to the publish page
     *
     * @param mixed $data
     * @return array
     */
    public function preProcess($data)
    {
        return is_array($data) ? $data : $this->defaultValue();
    }

    /**
     * Process the data before it gets saved
     *
     * @param mixed $data
     * @return array
     */
    public function process($data)
    {
        return is_array($data) ? $data : $this->defaultValue();
    }

    /**
     * Augment the value for use in templates
     *
     * @param mixed $value
     * @return array
     */
    public function augment($value)
    {
        if (!is_array($value)) {
            return $this->defaultValue();
        }

        return [
            'episode_id' => $value['episode_id'] ?? null,
            'episode_title' => $value['episode_title'] ?? null,
            'spotify' => [
                'url' => $value['spotify_link'] ?? null,
                'has_link' => !empty($value['spotify_link']),
            ],
            'apple_podcasts' => [
                'url' => $value['apple_podcasts_link'] ?? null,
                'has_link' => !empty($value['apple_podcasts_link']),
            ],
            'youtube' => [
                'url' => $value['youtube_link'] ?? null,
                'has_link' => !empty($value['youtube_link']),
            ],
            'has_any_links' => !empty($value['spotify_link']) ||
                              !empty($value['apple_podcasts_link']) ||
                              !empty($value['youtube_link']),
        ];
    }

    /**
     * Define the fieldtype config blueprint
     *
     * @return array
     */
    public function configFieldItems(): array
    {
        return [
            'auto_find' => [
                'type' => 'toggle',
                'display' => 'Auto Find Links',
                'instructions' => 'Automatically search for links when an episode is selected',
                'default' => true,
            ],
            'allow_manual_override' => [
                'type' => 'toggle',
                'display' => 'Allow Manual Override',
                'instructions' => 'Allow users to manually edit the links',
                'default' => true,
            ],
        ];
    }

    /**
     * Define the GraphQL type for this fieldtype
     *
     * @return \GraphQL\Type\Definition\Type
     */
    public function toGqlType()
    {
        return \GraphQL::type('PodcastLinks');
    }
}
