<template>
    <div class="podcast-link-finder">
        <!-- Episode Selection -->
        <div class="mb-6">
            <label class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2 block">
                Select Episode from Transistor
            </label>
            <div class="flex gap-2 mb-2">
                <div class="flex-1 min-w-0">
                    <Input
                        v-model="searchQuery"
                        @update:model-value="debouncedSearch"
                        placeholder="Search episodes..."
                    />
                </div>
                <div class="shrink-0">
                    <Select
                        v-model="episodeStatus"
                        @update:model-value="loadEpisodes(searchQuery)"
                        :options="statusOptions"
                    />
                </div>
                <div class="shrink-0">
                    <Select
                        v-model="episodeLimit"
                        @update:model-value="loadEpisodes(searchQuery)"
                        :options="limitOptions"
                    />
                </div>
            </div>
            <div v-if="loading" class="flex items-center py-2 text-gray-600 dark:text-gray-400">
                <svg class="animate-spin size-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm">Loading episodes...</span>
            </div>
            <Select
                v-else
                v-model="selectedEpisodeId"
                @update:model-value="onEpisodeSelected"
                :options="episodeOptions"
                placeholder="Select an episode..."
            />
        </div>

        <!-- Selected Episode Summary -->
        <div v-if="value.episode_title" class="mb-6">
            <Card class="p-4 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="text-xs uppercase tracking-wide text-blue-600 dark:text-blue-400 mb-1">
                            Selected Episode
                        </div>
                        <div class="font-medium text-gray-900 dark:text-gray-100 truncate">
                            {{ value.episode_title }}
                        </div>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        <!-- Completion Status -->
                        <div class="flex items-center gap-1.5 text-sm">
                            <span
                                class="size-2.5 rounded-full"
                                :class="value.youtube_link ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
                            ></span>
                            <span
                                class="size-2.5 rounded-full"
                                :class="value.spotify_link ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
                            ></span>
                            <span
                                class="size-2.5 rounded-full"
                                :class="value.apple_podcasts_link ? 'bg-green-500' : 'bg-gray-300 dark:bg-gray-600'"
                            ></span>
                            <span class="text-gray-500 dark:text-gray-400 ml-1">
                                {{ linkedCount }}/3
                            </span>
                        </div>
                        <Button
                            v-if="hasUnlinkedPlatforms"
                            @click="searchMissingPlatforms"
                            :disabled="searchingPlatforms"
                            variant="primary"
                            size="sm"
                        >
                            <svg v-if="searchingPlatforms" class="animate-spin size-4 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ searchingPlatforms ? 'Searching...' : 'Search Missing' }}
                        </Button>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Platform Cards (Always Visible) -->
        <div v-if="value.episode_id" class="space-y-3">
            <!-- YouTube Card -->
            <Card :inset="true" class="p-0 overflow-hidden">
                <div
                    class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none"
                    :class="youtubeHeaderClass"
                    @click="expandedPlatform = expandedPlatform === 'youtube' ? null : 'youtube'"
                >
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                    <span class="font-semibold">YouTube</span>

                    <!-- Status Badge -->
                    <span
                        class="ml-auto text-xs px-2 py-0.5 rounded-full font-medium"
                        :class="getStatusBadgeClass('youtube')"
                    >
                        {{ getStatusText('youtube') }}
                    </span>

                    <!-- Expand Icon -->
                    <svg
                        class="w-4 h-4 transition-transform shrink-0"
                        :class="{ 'rotate-180': expandedPlatform === 'youtube' }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>

                <!-- YouTube Content -->
                <div v-if="expandedPlatform === 'youtube'" class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <!-- Current Link Display -->
                    <div v-if="value.youtube_link" class="flex items-center gap-2 mb-3 p-2 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">
                        <svg class="size-4 text-green-600 dark:text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <a
                            :href="value.youtube_link"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline truncate flex-1"
                        >
                            {{ value.youtube_link }}
                        </a>
                        <Button
                            @click.stop="clearLink('youtube')"
                            variant="ghost"
                            size="sm"
                            class="shrink-0 text-gray-400 hover:text-red-500"
                        >
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </Button>
                    </div>

                    <!-- Warning for YouTube restrictions -->
                    <Alert v-if="platformWarnings?.youtube && !value.youtube_link" variant="warning" class="mb-3">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-sm">{{ platformWarnings.youtube }}</span>
                            <Button
                                v-if="!searchingYouTube"
                                @click="searchPlatform('youtube', true)"
                                variant="primary"
                                size="sm"
                                text="Search Anyway"
                            />
                        </div>
                    </Alert>

                    <!-- Search Results -->
                    <div v-if="platformResults?.youtube?.length > 0 && !value.youtube_link" class="mb-3">
                        <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">
                            {{ platformResults.youtube.length }} matches found
                        </label>
                        <Select
                            v-model="selectedYouTube"
                            @update:model-value="onPlatformSelected('youtube', $event)"
                            :options="youtubeOptions"
                            placeholder="Select a video..."
                        />
                    </div>

                    <!-- Search / Manual Entry -->
                    <div class="flex gap-2">
                        <div class="flex-1">
                            <Input
                                v-model="manualYouTube"
                                @update:model-value="onManualInput('youtube', $event)"
                                placeholder="Enter YouTube URL manually..."
                                type="url"
                            />
                        </div>
                        <Button
                            @click="searchPlatform('youtube')"
                            :disabled="searchingYouTube"
                            variant="secondary"
                            class="shrink-0"
                        >
                            <svg v-if="searchingYouTube" class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Spotify Card -->
            <Card :inset="true" class="p-0 overflow-hidden">
                <div
                    class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none"
                    :class="spotifyHeaderClass"
                    @click="expandedPlatform = expandedPlatform === 'spotify' ? null : 'spotify'"
                >
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
                    </svg>
                    <span class="font-semibold">Spotify</span>

                    <span
                        class="ml-auto text-xs px-2 py-0.5 rounded-full font-medium"
                        :class="getStatusBadgeClass('spotify')"
                    >
                        {{ getStatusText('spotify') }}
                    </span>

                    <svg
                        class="w-4 h-4 transition-transform shrink-0"
                        :class="{ 'rotate-180': expandedPlatform === 'spotify' }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>

                <!-- Spotify Content -->
                <div v-if="expandedPlatform === 'spotify'" class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <div v-if="value.spotify_link" class="flex items-center gap-2 mb-3 p-2 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">
                        <svg class="size-4 text-green-600 dark:text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <a
                            :href="value.spotify_link"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline truncate flex-1"
                        >
                            {{ value.spotify_link }}
                        </a>
                        <Button
                            @click.stop="clearLink('spotify')"
                            variant="ghost"
                            size="sm"
                            class="shrink-0 text-gray-400 hover:text-red-500"
                        >
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </Button>
                    </div>

                    <div v-if="platformResults?.spotify?.length > 0 && !value.spotify_link" class="mb-3">
                        <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">
                            {{ platformResults.spotify.length }} matches found
                        </label>
                        <Select
                            v-model="selectedSpotify"
                            @update:model-value="onPlatformSelected('spotify', $event)"
                            :options="spotifyOptions"
                            placeholder="Select an episode..."
                        />
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1">
                            <Input
                                v-model="manualSpotify"
                                @update:model-value="onManualInput('spotify', $event)"
                                placeholder="Enter Spotify URL manually..."
                                type="url"
                            />
                        </div>
                        <Button
                            @click="searchPlatform('spotify')"
                            :disabled="searchingSpotify"
                            variant="secondary"
                            class="shrink-0"
                        >
                            <svg v-if="searchingSpotify" class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </Button>
                    </div>
                </div>
            </Card>

            <!-- Apple Podcasts Card -->
            <Card :inset="true" class="p-0 overflow-hidden">
                <div
                    class="flex items-center gap-3 px-4 py-3 cursor-pointer select-none"
                    :class="appleHeaderClass"
                    @click="expandedPlatform = expandedPlatform === 'apple' ? null : 'apple'"
                >
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                    </svg>
                    <span class="font-semibold">Apple Podcasts</span>

                    <span
                        class="ml-auto text-xs px-2 py-0.5 rounded-full font-medium"
                        :class="getStatusBadgeClass('apple')"
                    >
                        {{ getStatusText('apple') }}
                    </span>

                    <svg
                        class="w-4 h-4 transition-transform shrink-0"
                        :class="{ 'rotate-180': expandedPlatform === 'apple' }"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </div>

                <!-- Apple Content -->
                <div v-if="expandedPlatform === 'apple'" class="p-4 border-t border-gray-200 dark:border-gray-700">
                    <div v-if="value.apple_podcasts_link" class="flex items-center gap-2 mb-3 p-2 bg-green-50 dark:bg-green-900/20 rounded border border-green-200 dark:border-green-800">
                        <svg class="size-4 text-green-600 dark:text-green-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <a
                            :href="value.apple_podcasts_link"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-sm text-blue-600 dark:text-blue-400 hover:underline truncate flex-1"
                        >
                            {{ value.apple_podcasts_link }}
                        </a>
                        <Button
                            @click.stop="clearLink('apple')"
                            variant="ghost"
                            size="sm"
                            class="shrink-0 text-gray-400 hover:text-red-500"
                        >
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </Button>
                    </div>

                    <div v-if="platformResults?.apple_podcasts?.length > 0 && !value.apple_podcasts_link" class="mb-3">
                        <label class="text-xs text-gray-500 dark:text-gray-400 mb-1 block">
                            {{ platformResults.apple_podcasts.length }} matches found
                        </label>
                        <Select
                            v-model="selectedApple"
                            @update:model-value="onPlatformSelected('apple', $event)"
                            :options="appleOptions"
                            placeholder="Select an episode..."
                        />
                    </div>

                    <div class="flex gap-2">
                        <div class="flex-1">
                            <Input
                                v-model="manualApple"
                                @update:model-value="onManualInput('apple', $event)"
                                placeholder="Enter Apple Podcasts URL manually..."
                                type="url"
                            />
                        </div>
                        <Button
                            @click="searchPlatform('apple')"
                            :disabled="searchingApple"
                            variant="secondary"
                            class="shrink-0"
                        >
                            <svg v-if="searchingApple" class="animate-spin size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <svg v-else class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </Button>
                    </div>
                </div>
            </Card>
        </div>

        <!-- Error Message -->
        <Alert v-if="error" variant="danger" class="mt-4">
            {{ error }}
        </Alert>
    </div>
</template>

<script>
import { Fieldtype } from '@statamic/cms';
import {
    Alert,
    Button,
    Card,
    Input,
    Select,
} from '@statamic/cms/ui';

export default {
    mixins: [Fieldtype],

    components: {
        Alert,
        Button,
        Card,
        Input,
        Select,
    },

    data() {
        return {
            episodes: [],
            searchQuery: '',
            episodeStatus: 'published',
            episodeLimit: 20,
            selectedEpisodeId: null,
            loading: false,
            searchingPlatforms: false,
            searchingYouTube: false,
            searchingSpotify: false,
            searchingApple: false,
            error: null,
            searchTimeout: null,
            platformResults: null,
            platformWarnings: null,
            selectedYouTube: null,
            selectedSpotify: null,
            selectedApple: null,
            manualYouTube: '',
            manualSpotify: '',
            manualApple: '',
            expandedPlatform: null,
            initializing: true,
        };
    },

    computed: {
        statusOptions() {
            return [
                { value: 'published', label: 'Published' },
                { value: 'scheduled', label: 'Scheduled' },
                { value: 'draft', label: 'Draft' },
                { value: 'all', label: 'All Episodes' },
            ];
        },

        limitOptions() {
            return [
                { value: 20, label: '20' },
                { value: 50, label: '50' },
                { value: 100, label: '100' },
            ];
        },

        episodeOptions() {
            return this.episodes.map(episode => ({
                value: episode.id,
                label: `${episode.title} (${this.formatDate(episode.published_at)})`,
            }));
        },

        youtubeOptions() {
            return (this.platformResults?.youtube || []).map(result => ({
                value: result.url,
                label: `${result.title} (${result.score}% match)`,
            }));
        },

        spotifyOptions() {
            return (this.platformResults?.spotify || []).map(result => ({
                value: result.url,
                label: `${result.title} (${result.score}% match)`,
            }));
        },

        appleOptions() {
            return (this.platformResults?.apple_podcasts || []).map(result => ({
                value: result.url,
                label: `${result.title} (${result.score}% match)`,
            }));
        },

        linkedCount() {
            let count = 0;
            if (this.value.youtube_link) count++;
            if (this.value.spotify_link) count++;
            if (this.value.apple_podcasts_link) count++;
            return count;
        },

        hasUnlinkedPlatforms() {
            return !this.value.youtube_link || !this.value.spotify_link || !this.value.apple_podcasts_link;
        },

        youtubeHeaderClass() {
            if (this.value.youtube_link) {
                return 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300';
            }
            return 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300';
        },

        spotifyHeaderClass() {
            if (this.value.spotify_link) {
                return 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300';
            }
            return 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300';
        },

        appleHeaderClass() {
            if (this.value.apple_podcasts_link) {
                return 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300';
            }
            return 'bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300';
        },
    },

    mounted() {
        this.loadEpisodes();

        if (this.value && this.value.episode_id) {
            this.selectedEpisodeId = this.value.episode_id;
            this.manualYouTube = this.value.youtube_link || '';
            this.manualSpotify = this.value.spotify_link || '';
            this.manualApple = this.value.apple_podcasts_link || '';
        }

        this.$nextTick(() => {
            this.initializing = false;
        });
    },

    methods: {
        async loadEpisodes(query = '') {
            this.loading = true;
            this.error = null;

            try {
                const response = await this.$axios.get('/cp/podcast-link-finder/search-episodes', {
                    params: {
                        query,
                        status: this.episodeStatus,
                        limit: this.episodeLimit,
                    },
                });
                this.episodes = response.data.episodes || [];
            } catch (err) {
                this.error = 'Failed to load episodes. Please check your API credentials.';
                console.error(err);
            } finally {
                this.loading = false;
            }
        },

        debouncedSearch() {
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.loadEpisodes(this.searchQuery);
            }, 500);
        },

        async onEpisodeSelected() {
            if (!this.selectedEpisodeId) {
                this.$emit('update:value', {
                    episode_id: null,
                    episode_title: null,
                    spotify_link: null,
                    apple_podcasts_link: null,
                    youtube_link: null,
                });
                this.platformResults = null;
                this.platformWarnings = null;
                this.selectedYouTube = null;
                this.selectedSpotify = null;
                this.selectedApple = null;
                this.manualYouTube = '';
                this.manualSpotify = '';
                this.manualApple = '';
                this.expandedPlatform = null;
                return;
            }

            const episode = this.episodes.find(e => e.id === this.selectedEpisodeId);

            this.$emit('update:value', {
                episode_id: episode.id,
                episode_title: episode.title,
                spotify_link: null,
                apple_podcasts_link: null,
                youtube_link: null,
            });

            this.platformResults = null;
            this.platformWarnings = null;
            this.selectedYouTube = null;
            this.selectedSpotify = null;
            this.selectedApple = null;
            this.manualYouTube = '';
            this.manualSpotify = '';
            this.manualApple = '';

            // Auto-search all platforms on episode selection
            if (this.config.auto_find !== false) {
                await this.searchAllPlatforms();
            }
        },

        async searchAllPlatforms(forceYouTube = false) {
            this.searchingPlatforms = true;
            this.searchingYouTube = true;
            this.searchingSpotify = true;
            this.searchingApple = true;
            this.error = null;

            try {
                const response = await this.$axios.post('/cp/podcast-link-finder/search-platforms', {
                    episode_id: this.selectedEpisodeId,
                    force_youtube: forceYouTube,
                });

                if (response.data.success) {
                    this.platformResults = response.data.results;
                    this.platformWarnings = response.data.warnings || null;

                    // Auto-select best matches
                    if (this.config.auto_find !== false) {
                        if (this.platformResults.youtube?.length > 0) {
                            const url = this.platformResults.youtube[0].url;
                            this.selectedYouTube = url;
                            this.manualYouTube = url;
                            this.updateLink('youtube', url);
                        }
                        if (this.platformResults.spotify?.length > 0) {
                            const url = this.platformResults.spotify[0].url;
                            this.selectedSpotify = url;
                            this.manualSpotify = url;
                            this.updateLink('spotify', url);
                        }
                        if (this.platformResults.apple_podcasts?.length > 0) {
                            const url = this.platformResults.apple_podcasts[0].url;
                            this.selectedApple = url;
                            this.manualApple = url;
                            this.updateLink('apple', url);
                        }
                    }
                }
            } catch (err) {
                this.error = 'Failed to search platforms. You can enter links manually.';
                console.error(err);
            } finally {
                this.searchingPlatforms = false;
                this.searchingYouTube = false;
                this.searchingSpotify = false;
                this.searchingApple = false;
            }
        },

        async searchMissingPlatforms() {
            this.searchingPlatforms = true;
            this.error = null;

            const missing = [];
            if (!this.value.youtube_link) missing.push('youtube');
            if (!this.value.spotify_link) missing.push('spotify');
            if (!this.value.apple_podcasts_link) missing.push('apple');

            // For now, search all and only apply missing ones
            try {
                const response = await this.$axios.post('/cp/podcast-link-finder/search-platforms', {
                    episode_id: this.selectedEpisodeId || this.value.episode_id,
                    force_youtube: false,
                });

                if (response.data.success) {
                    this.platformResults = response.data.results;
                    this.platformWarnings = response.data.warnings || null;

                    // Auto-select only for missing platforms
                    if (missing.includes('youtube') && this.platformResults.youtube?.length > 0) {
                        const url = this.platformResults.youtube[0].url;
                        this.selectedYouTube = url;
                        this.manualYouTube = url;
                        this.updateLink('youtube', url);
                    }
                    if (missing.includes('spotify') && this.platformResults.spotify?.length > 0) {
                        const url = this.platformResults.spotify[0].url;
                        this.selectedSpotify = url;
                        this.manualSpotify = url;
                        this.updateLink('spotify', url);
                    }
                    if (missing.includes('apple') && this.platformResults.apple_podcasts?.length > 0) {
                        const url = this.platformResults.apple_podcasts[0].url;
                        this.selectedApple = url;
                        this.manualApple = url;
                        this.updateLink('apple', url);
                    }
                }
            } catch (err) {
                this.error = 'Failed to search platforms. You can enter links manually.';
                console.error(err);
            } finally {
                this.searchingPlatforms = false;
            }
        },

        async searchPlatform(platform, force = false) {
            const searchingKey = platform === 'apple' ? 'searchingApple' : `searching${platform.charAt(0).toUpperCase() + platform.slice(1)}`;
            this[searchingKey] = true;

            try {
                const response = await this.$axios.post('/cp/podcast-link-finder/search-platforms', {
                    episode_id: this.selectedEpisodeId || this.value.episode_id,
                    force_youtube: platform === 'youtube' && force,
                });

                if (response.data.success) {
                    if (!this.platformResults) {
                        this.platformResults = { youtube: [], spotify: [], apple_podcasts: [] };
                    }

                    const resultKey = platform === 'apple' ? 'apple_podcasts' : platform;
                    this.platformResults[resultKey] = response.data.results[resultKey] || [];

                    if (response.data.warnings) {
                        this.platformWarnings = {
                            ...this.platformWarnings,
                            ...response.data.warnings,
                        };
                        if (platform === 'youtube' && force) {
                            this.platformWarnings.youtube = null;
                        }
                    }
                }
            } catch (err) {
                console.error(`Failed to search ${platform}:`, err);
            } finally {
                this[searchingKey] = false;
            }
        },

        onPlatformSelected(platform, url) {
            if (platform === 'youtube') {
                this.manualYouTube = url || '';
            } else if (platform === 'spotify') {
                this.manualSpotify = url || '';
            } else if (platform === 'apple') {
                this.manualApple = url || '';
            }
            this.updateLink(platform, url);
        },

        onManualInput(platform, url) {
            if (platform === 'youtube') {
                this.selectedYouTube = url;
            } else if (platform === 'spotify') {
                this.selectedSpotify = url;
            } else if (platform === 'apple') {
                this.selectedApple = url;
            }
            this.updateLink(platform, url);
        },

        updateLink(platform, url) {
            const linkKey = platform === 'youtube' ? 'youtube_link'
                : platform === 'spotify' ? 'spotify_link'
                : 'apple_podcasts_link';

            this.$emit('update:value', {
                ...this.value,
                [linkKey]: url || null,
            });
        },

        clearLink(platform) {
            if (platform === 'youtube') {
                this.selectedYouTube = null;
                this.manualYouTube = '';
            } else if (platform === 'spotify') {
                this.selectedSpotify = null;
                this.manualSpotify = '';
            } else if (platform === 'apple') {
                this.selectedApple = null;
                this.manualApple = '';
            }
            this.updateLink(platform, null);
        },

        getStatusText(platform) {
            const searching = platform === 'youtube' ? this.searchingYouTube
                : platform === 'spotify' ? this.searchingSpotify
                : this.searchingApple;

            if (searching) return 'Searching...';

            const link = platform === 'youtube' ? this.value.youtube_link
                : platform === 'spotify' ? this.value.spotify_link
                : this.value.apple_podcasts_link;

            if (link) return 'Linked';

            const resultKey = platform === 'apple' ? 'apple_podcasts' : platform;
            const hasResults = this.platformResults?.[resultKey]?.length > 0;

            if (hasResults) return 'Match found';
            if (this.platformResults && !hasResults) return 'No match';

            return 'Not linked';
        },

        getStatusBadgeClass(platform) {
            const searching = platform === 'youtube' ? this.searchingYouTube
                : platform === 'spotify' ? this.searchingSpotify
                : this.searchingApple;

            if (searching) {
                return 'bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300';
            }

            const link = platform === 'youtube' ? this.value.youtube_link
                : platform === 'spotify' ? this.value.spotify_link
                : this.value.apple_podcasts_link;

            if (link) {
                return 'bg-green-100 dark:bg-green-900/50 text-green-700 dark:text-green-300';
            }

            const resultKey = platform === 'apple' ? 'apple_podcasts' : platform;
            const hasResults = this.platformResults?.[resultKey]?.length > 0;

            if (hasResults) {
                return 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300';
            }

            if (this.platformResults && !hasResults) {
                return 'bg-orange-100 dark:bg-orange-900/50 text-orange-700 dark:text-orange-300';
            }

            return 'bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400';
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString();
        },
    },
};
</script>
