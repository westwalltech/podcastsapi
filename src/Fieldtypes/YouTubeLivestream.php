<?php

namespace NewSong\PodcastLinkFinder\Fieldtypes;

use Statamic\Fields\Fieldtype;
use Statamic\Facades\GraphQL;
use Carbon\Carbon;

class YouTubeLivestream extends Fieldtype
{
    protected $icon = 'video';

    protected $categories = ['media'];

    /**
     * The blank/default value
     *
     * @return null
     */
    public function defaultValue()
    {
        return null;
    }

    /**
     * Pre-process the data before it gets sent to the publish page
     *
     * @param mixed $data
     * @return string|null
     */
    public function preProcess($data)
    {
        return $data;
    }

    /**
     * Process the data before it gets saved
     *
     * @param mixed $data
     * @return string|null
     */
    public function process($data)
    {
        return $data ?: null;
    }

    /**
     * Augment the value for use in templates
     *
     * @param mixed $value
     * @return array
     */
    public function augment($value)
    {
        return [
            'url' => $value,
            'has_link' => !empty($value),
        ];
    }

    /**
     * Preload data to be passed to the Vue component
     *
     * @return array
     */
    public function preload()
    {
        $entryId = null;
        $airDate = null;
        $showButton = false;

        try {
            $entry = $this->field->parent();

            // Check if we have a valid entry with an ID
            if ($entry && method_exists($entry, 'id') && $entry->id()) {
                $entryId = $entry->id();

                // Get the date field from config
                $dateField = $this->config('date_field') ?? config('youtube-livestream.date_field', 'air_date');

                // Get air_date using value() method
                if (method_exists($entry, 'value')) {
                    $airDate = $entry->value($dateField);
                }

                // Determine if we should show the fetch button
                if ($airDate && config('youtube-livestream.enabled', true)) {
                    $timezone = config('youtube-livestream.schedule.timezone', 'America/Chicago');
                    try {
                        // Handle Carbon objects or strings
                        if ($airDate instanceof Carbon) {
                            $date = $airDate->copy()->setTimezone($timezone);
                        } else {
                            $date = Carbon::parse($airDate, $timezone);
                        }
                        $isFutureOrToday = $date->isToday() || $date->isFuture();

                        // Check if sunday_only is enabled
                        $sundayOnly = config('youtube-livestream.matching.sunday_only', false);
                        if ($sundayOnly) {
                            $showButton = $date->isSunday() && $isFutureOrToday;
                        } else {
                            $showButton = $isFutureOrToday;
                        }
                    } catch (\Exception $e) {
                        // Invalid date, don't show button
                    }
                }
            }
        } catch (\Exception $e) {
            // Entry not ready or other error - return defaults
        }

        return [
            'entryId' => $entryId,
            'airDate' => $airDate instanceof Carbon ? $airDate->toDateString() : $airDate,
            'showButton' => $showButton,
            'enabled' => config('youtube-livestream.enabled', true),
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
            'date_field' => [
                'type' => 'text',
                'display' => 'Date Field',
                'instructions' => 'The field handle containing the air date to match against livestreams',
                'default' => 'air_date',
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
        return GraphQL::type('PlatformLink');
    }
}
