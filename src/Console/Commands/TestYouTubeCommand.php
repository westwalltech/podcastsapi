<?php

namespace NewSong\PodcastLinkFinder\Console\Commands;

use GuzzleHttp\Exception\RequestException;
use Illuminate\Console\Command;
use NewSong\PodcastLinkFinder\Services\YouTubeService;

class TestYouTubeCommand extends Command
{
    protected $signature = 'podcast:test-youtube {--title=test : Search title to test}';
    protected $description = 'Test YouTube API connection and display detailed error information';

    protected YouTubeService $youtube;

    public function __construct(YouTubeService $youtube)
    {
        parent::__construct();
        $this->youtube = $youtube;
    }

    public function handle()
    {
        $this->info('Testing YouTube API Connection...');
        $this->newLine();

        // Display configuration
        $this->info('Configuration:');
        $this->line('  API Key: '.substr(config('podcast-link-finder.youtube.api_key'), 0, 20).'...');
        $this->line('  Channel ID: '.config('podcast-link-finder.youtube.channel_id'));
        $this->newLine();

        // Test 1: Simple connection test
        $this->info('Test 1: Simple API Connection');
        $connectionTest = $this->youtube->testConnection();

        if ($connectionTest['success']) {
            $this->line('  <fg=green>✓</> Connection successful');
        } else {
            $this->line('  <fg=red>✗</> '.$connectionTest['message']);
        }
        $this->newLine();

        // Test 2: Search with detailed error capture
        $this->info('Test 2: Search Request with Detailed Error');
        $title = $this->option('title');
        $this->line('  Searching for: '.$title);

        try {
            $client = new \GuzzleHttp\Client([
                'base_uri' => 'https://www.googleapis.com/youtube/v3/',
            ]);

            $params = [
                'part' => 'snippet',
                'channelId' => config('podcast-link-finder.youtube.channel_id'),
                'q' => $title,
                'type' => 'video',
                'maxResults' => 5,
                'order' => 'date',
                'key' => config('podcast-link-finder.youtube.api_key'),
            ];

            $this->line('  Request URL: https://www.googleapis.com/youtube/v3/search?'.http_build_query($params));
            $this->newLine();

            $response = $client->get('search', [
                'query' => $params,
            ]);

            $data = json_decode($response->getBody()->getContents(), true);
            $videos = $data['items'] ?? [];

            $this->line('  <fg=green>✓</> Search successful');
            $this->line('  Found '.count($videos).' videos');

            if (! empty($videos)) {
                $this->newLine();
                $this->info('  Top Results:');
                foreach (array_slice($videos, 0, 3) as $video) {
                    $this->line('    - '.$video['snippet']['title']);
                }
            }

        } catch (RequestException $e) {
            $this->line('  <fg=red>✗</> Request failed');
            $this->newLine();

            // Get the full error response
            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $statusCode = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                $this->error('HTTP Status Code: '.$statusCode);
                $this->newLine();

                // Try to parse as JSON
                $errorData = json_decode($body, true);
                if ($errorData && isset($errorData['error'])) {
                    $this->error('YouTube API Error Response:');
                    $this->line('  Code: '.($errorData['error']['code'] ?? 'N/A'));
                    $this->line('  Message: '.($errorData['error']['message'] ?? 'N/A'));

                    if (isset($errorData['error']['errors'])) {
                        $this->newLine();
                        $this->line('  Detailed Errors:');
                        foreach ($errorData['error']['errors'] as $error) {
                            $this->line('    Domain: '.($error['domain'] ?? 'N/A'));
                            $this->line('    Reason: '.($error['reason'] ?? 'N/A'));
                            $this->line('    Message: '.($error['message'] ?? 'N/A'));
                            if (isset($error['extendedHelp'])) {
                                $this->line('    Help: '.$error['extendedHelp']);
                            }
                        }
                    }

                    $this->newLine();
                    $this->line('Full JSON Response:');
                    $this->line(json_encode($errorData, JSON_PRETTY_PRINT));
                } else {
                    $this->error('Raw Response Body:');
                    $this->line($body);
                }
            } else {
                $this->error('No response received: '.$e->getMessage());
            }

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('Unexpected error: '.$e->getMessage());

            return Command::FAILURE;
        }

        $this->newLine();
        $this->info('All tests completed successfully!');

        return Command::SUCCESS;
    }
}
