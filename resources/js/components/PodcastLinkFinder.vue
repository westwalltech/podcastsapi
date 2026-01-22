<template>
    <div class="podcast-link-finder">
        <!-- Episode Selection -->
        <div class="mb-6">
            <label class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2 block">Select Episode from Transistor</label>
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
                <Icon name="loading" class="size-4 mr-2" />
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

        <!-- Selected Episode Info -->
        <Card v-if="value.episode_title" class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-1">Selected Episode</div>
                    <div class="font-medium text-gray-900 dark:text-gray-100">{{ value.episode_title }}</div>
                </div>
                <Button
                    v-if="!searchingPlatforms && !platformResults"
                    @click="searchAllPlatforms"
                    variant="primary"
                    text="Search Platforms"
                />
            </div>
        </Card>

        <!-- Searching Status -->
        <Card v-if="searchingPlatforms" class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
            <div class="flex items-center text-blue-700 dark:text-blue-300">
                <Icon name="loading" class="size-4 mr-3" />
                <span class="text-sm">Searching across platforms...</span>
            </div>
        </Card>

        <!-- Platform Results -->
        <div v-if="platformResults" class="space-y-4">
            <div class="text-xs uppercase tracking-wide text-gray-600 dark:text-gray-400 mb-2">Platform Links</div>

            <!-- YouTube Results -->
            <Card :inset="true" class="p-0 overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-3 border-b border-red-200 dark:border-red-800 bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-300">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                    <span class="font-semibold">YouTube</span>
                    <span v-if="platformResults.youtube.length > 0" class="ml-auto text-xs px-2 py-1 rounded bg-white/50 dark:bg-black/30">
                        {{ platformResults.youtube.length }} found
                    </span>
                </div>
                <div v-if="platformResults.youtube.length > 0" class="p-4">
                    <Select
                        v-model="selectedYouTube"
                        @update:model-value="onYouTubeSelected"
                        :options="youtubeOptions"
                        placeholder="Select a video..."
                    />
                    <div v-if="selectedYouTube" class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <Icon name="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                        <a :href="selectedYouTube" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline truncate">
                            {{ selectedYouTube }}
                        </a>
                    </div>
                </div>
                <div v-else class="p-4">
                    <Alert v-if="platformWarnings && platformWarnings.youtube" variant="warning">
                        <div class="flex items-center justify-between gap-4">
                            <span>{{ platformWarnings.youtube }}</span>
                            <Button
                                v-if="!searchingYouTube"
                                @click="forceYouTubeSearch"
                                variant="primary"
                                size="sm"
                                text="Search Anyway"
                            />
                            <span v-else class="text-xs">Searching...</span>
                        </div>
                    </Alert>
                    <div v-else class="text-gray-500 dark:text-gray-400 text-sm">
                        No matches found
                    </div>
                </div>
            </Card>

            <!-- Spotify Results -->
            <Card :inset="true" class="p-0 overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-3 border-b border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
                    </svg>
                    <span class="font-semibold">Spotify</span>
                    <span v-if="platformResults.spotify.length > 0" class="ml-auto text-xs px-2 py-1 rounded bg-white/50 dark:bg-black/30">
                        {{ platformResults.spotify.length }} found
                    </span>
                </div>
                <div v-if="platformResults.spotify.length > 0" class="p-4">
                    <Select
                        v-model="selectedSpotify"
                        @update:model-value="onSpotifySelected"
                        :options="spotifyOptions"
                        placeholder="Select an episode..."
                    />
                    <div v-if="selectedSpotify" class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <Icon name="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                        <a :href="selectedSpotify" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline truncate">
                            {{ selectedSpotify }}
                        </a>
                    </div>
                </div>
                <div v-else class="p-4 text-gray-500 dark:text-gray-400 text-sm">
                    No matches found
                </div>
            </Card>

            <!-- Apple Podcasts Results -->
            <Card :inset="true" class="p-0 overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-3 border-b border-purple-200 dark:border-purple-800 bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                    </svg>
                    <span class="font-semibold">Apple Podcasts</span>
                    <span v-if="platformResults.apple_podcasts.length > 0" class="ml-auto text-xs px-2 py-1 rounded bg-white/50 dark:bg-black/30">
                        {{ platformResults.apple_podcasts.length }} found
                    </span>
                </div>
                <div v-if="platformResults.apple_podcasts.length > 0" class="p-4">
                    <Select
                        v-model="selectedApple"
                        @update:model-value="onAppleSelected"
                        :options="appleOptions"
                        placeholder="Select an episode..."
                    />
                    <div v-if="selectedApple" class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <Icon name="check-circle" class="size-4 text-green-600 dark:text-green-400" />
                        <a :href="selectedApple" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline truncate">
                            {{ selectedApple }}
                        </a>
                    </div>
                </div>
                <div v-else class="p-4 text-gray-500 dark:text-gray-400 text-sm">
                    No matches found
                </div>
            </Card>
        </div>

        <!-- Manual Override Section -->
        <div v-if="config.allow_manual_override && value.episode_id" class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <details>
                <summary class="cursor-pointer text-sm text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 select-none">
                    Enter links manually
                </summary>
                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1 block">YouTube URL</label>
                        <Input
                            type="url"
                            v-model="manualYouTube"
                            @update:model-value="onManualYouTubeInput"
                            placeholder="https://www.youtube.com/watch?v=..."
                        />
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1 block">Spotify URL</label>
                        <Input
                            type="url"
                            v-model="manualSpotify"
                            @update:model-value="onManualSpotifyInput"
                            placeholder="https://open.spotify.com/episode/..."
                        />
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1 block">Apple Podcasts URL</label>
                        <Input
                            type="url"
                            v-model="manualApple"
                            @update:model-value="onManualAppleInput"
                            placeholder="https://podcasts.apple.com/..."
                        />
                    </div>
                </div>
            </details>
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
    Icon,
    Input,
    Select,
} from '@statamic/cms/ui';

export default {
    mixins: [Fieldtype],

    components: {
        Alert,
        Button,
        Card,
        Icon,
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
    },

    mounted() {
        this.loadEpisodes();

        if (this.value && this.value.episode_id) {
            this.selectedEpisodeId = this.value.episode_id;
            this.selectedYouTube = this.value.youtube_link || null;
            this.selectedSpotify = this.value.spotify_link || null;
            this.selectedApple = this.value.apple_podcasts_link || null;
            this.manualYouTube = this.value.youtube_link || '';
            this.manualSpotify = this.value.spotify_link || '';
            this.manualApple = this.value.apple_podcasts_link || '';
        }
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
                this.selectedYouTube = null;
                this.selectedSpotify = null;
                this.selectedApple = null;
                this.manualYouTube = '';
                this.manualSpotify = '';
                this.manualApple = '';
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

            if (this.config.auto_find !== false) {
                await this.searchAllPlatforms();
            }
        },

        async searchAllPlatforms(forceYouTube = false) {
            this.searchingPlatforms = true;
            this.error = null;

            try {
                const response = await this.$axios.post('/cp/podcast-link-finder/search-platforms', {
                    episode_id: this.selectedEpisodeId,
                    force_youtube: forceYouTube,
                });

                if (response.data.success) {
                    this.platformResults = response.data.results;
                    this.platformWarnings = response.data.warnings || null;

                    if (this.config.auto_find !== false) {
                        if (this.platformResults.youtube.length > 0) {
                            this.selectedYouTube = this.platformResults.youtube[0].url;
                            this.onYouTubeSelected();
                        }
                        if (this.platformResults.spotify.length > 0) {
                            this.selectedSpotify = this.platformResults.spotify[0].url;
                            this.onSpotifySelected();
                        }
                        if (this.platformResults.apple_podcasts.length > 0) {
                            this.selectedApple = this.platformResults.apple_podcasts[0].url;
                            this.onAppleSelected();
                        }
                    }
                }
            } catch (err) {
                this.error = 'Failed to search platforms. You can enter links manually.';
                console.error(err);
            } finally {
                this.searchingPlatforms = false;
            }
        },

        async forceYouTubeSearch() {
            this.searchingYouTube = true;

            try {
                const response = await this.$axios.post('/cp/podcast-link-finder/search-platforms', {
                    episode_id: this.selectedEpisodeId,
                    force_youtube: true,
                });

                if (response.data.success) {
                    this.platformResults.youtube = response.data.results.youtube || [];
                    if (this.platformWarnings) {
                        this.platformWarnings.youtube = null;
                    }

                    if (this.platformResults.youtube.length > 0) {
                        this.selectedYouTube = this.platformResults.youtube[0].url;
                        this.onYouTubeSelected();
                    }
                }
            } catch (err) {
                console.error('YouTube search failed:', err);
            } finally {
                this.searchingYouTube = false;
            }
        },

        onYouTubeSelected() {
            this.manualYouTube = this.selectedYouTube || '';
            this.updateLinks();
        },

        onSpotifySelected() {
            this.manualSpotify = this.selectedSpotify || '';
            this.updateLinks();
        },

        onAppleSelected() {
            this.manualApple = this.selectedApple || '';
            this.updateLinks();
        },

        onManualYouTubeInput() {
            this.selectedYouTube = this.manualYouTube;
            this.updateLinks();
        },

        onManualSpotifyInput() {
            this.selectedSpotify = this.manualSpotify;
            this.updateLinks();
        },

        onManualAppleInput() {
            this.selectedApple = this.manualApple;
            this.updateLinks();
        },

        updateLinks() {
            this.$emit('update:value', {
                ...this.value,
                spotify_link: this.selectedSpotify || null,
                apple_podcasts_link: this.selectedApple || null,
                youtube_link: this.selectedYouTube || null,
            });
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString();
        },
    },
};
</script>
