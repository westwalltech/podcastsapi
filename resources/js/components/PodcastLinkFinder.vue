<template>
    <div class="podcast-link-finder">
        <!-- Episode Selection -->
        <div class="mb-6">
            <label class="publish-field-label">Select Episode from Transistor</label>
            <div class="flex gap-2 mb-2">
                <input
                    type="text"
                    v-model="searchQuery"
                    @input="debouncedSearch"
                    placeholder="Search episodes..."
                    class="input-text flex-1"
                />
                <div class="select-wrapper">
                    <select
                        v-model="episodeStatus"
                        @change="loadEpisodes(searchQuery)"
                        class="input-text"
                    >
                        <option value="published">Published</option>
                        <option value="scheduled">Scheduled</option>
                        <option value="draft">Draft</option>
                        <option value="all">All Episodes</option>
                    </select>
                    <div class="select-chevron">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
                <div class="select-wrapper">
                    <select
                        v-model="episodeLimit"
                        @change="loadEpisodes(searchQuery)"
                        class="input-text"
                    >
                        <option :value="20">20</option>
                        <option :value="50">50</option>
                        <option :value="100">100</option>
                    </select>
                    <div class="select-chevron">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div v-if="loading" class="flex items-center py-2 text-gray-600 dark:text-dark-150">
                <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm">Loading episodes...</span>
            </div>
            <select
                v-else
                v-model="selectedEpisodeId"
                @change="onEpisodeSelected"
                class="input-text"
            >
                <option :value="null">-- Select an episode --</option>
                <option
                    v-for="episode in episodes"
                    :key="episode.id"
                    :value="episode.id"
                >
                    {{ episode.title }} ({{ formatDate(episode.published_at) }})
                </option>
            </select>
        </div>

        <!-- Selected Episode Info -->
        <div v-if="value.episode_title" class="card mb-6" :style="selectedEpisodeStyle">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-600 dark:text-dark-150 mb-1">Selected Episode</div>
                    <div class="font-medium text-gray-900 dark:text-dark-100">{{ value.episode_title }}</div>
                </div>
                <button
                    v-if="!searchingPlatforms && !platformResults"
                    @click="searchAllPlatforms"
                    type="button"
                    class="btn-primary"
                >
                    Search Platforms
                </button>
            </div>
        </div>

        <!-- Searching Status -->
        <div v-if="searchingPlatforms" class="card mb-6" :style="searchingStyle">
            <div class="flex items-center">
                <svg class="animate-spin h-4 w-4 mr-3 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm">Searching across platforms...</span>
            </div>
        </div>

        <!-- Platform Results -->
        <div v-if="platformResults" class="space-y-6">
            <div class="text-xs uppercase tracking-wide text-gray-600 dark:text-dark-150 mb-2">Platform Links</div>

            <!-- YouTube Results -->
            <div class="card p-0 overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-3 border-b" :style="youtubeHeaderStyle">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                    <span class="font-semibold">YouTube</span>
                    <span v-if="platformResults.youtube.length > 0" class="ml-auto text-xs px-2 py-1 rounded" :style="resultCountStyle">
                        {{ platformResults.youtube.length }} found
                    </span>
                </div>
                <div v-if="platformResults.youtube.length > 0" class="p-4">
                    <select
                        v-model="selectedYouTube"
                        @change="onYouTubeSelected"
                        class="input-text"
                    >
                        <option :value="null">-- Select a video --</option>
                        <option
                            v-for="(result, index) in platformResults.youtube"
                            :key="index"
                            :value="result.url"
                        >
                            {{ result.title }} ({{ result.score }}% match)
                        </option>
                    </select>
                    <div v-if="selectedYouTube" class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-dark-150">
                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <a :href="selectedYouTube" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline truncate">
                            {{ selectedYouTube }}
                        </a>
                    </div>
                </div>
                <div v-else class="p-4">
                    <div v-if="platformWarnings && platformWarnings.youtube" class="text-sm text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded p-3">
                        <div class="flex items-center justify-between gap-4">
                            <div class="flex items-start gap-2">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                <span>{{ platformWarnings.youtube }}</span>
                            </div>
                            <button
                                v-if="!searchingYouTube"
                                @click="forceYouTubeSearch"
                                type="button"
                                class="btn-primary text-xs px-2 py-1 flex-shrink-0"
                            >
                                Search Anyway
                            </button>
                            <span v-else class="text-xs">Searching...</span>
                        </div>
                    </div>
                    <div v-else class="text-gray-500 dark:text-dark-150 text-sm">
                        No matches found
                    </div>
                </div>
            </div>

            <!-- Spotify Results -->
            <div class="card p-0 overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-3 border-b" :style="spotifyHeaderStyle">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
                    </svg>
                    <span class="font-semibold">Spotify</span>
                    <span v-if="platformResults.spotify.length > 0" class="ml-auto text-xs px-2 py-1 rounded" :style="resultCountStyle">
                        {{ platformResults.spotify.length }} found
                    </span>
                </div>
                <div v-if="platformResults.spotify.length > 0" class="p-4">
                    <select
                        v-model="selectedSpotify"
                        @change="onSpotifySelected"
                        class="input-text"
                    >
                        <option :value="null">-- Select an episode --</option>
                        <option
                            v-for="(result, index) in platformResults.spotify"
                            :key="index"
                            :value="result.url"
                        >
                            {{ result.title }} ({{ result.score }}% match)
                        </option>
                    </select>
                    <div v-if="selectedSpotify" class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-dark-150">
                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <a :href="selectedSpotify" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline truncate">
                            {{ selectedSpotify }}
                        </a>
                    </div>
                </div>
                <div v-else class="p-4 text-gray-500 dark:text-dark-150 text-sm">
                    No matches found
                </div>
            </div>

            <!-- Apple Podcasts Results -->
            <div class="card p-0 overflow-hidden">
                <div class="flex items-center gap-2 px-4 py-3 border-b" :style="appleHeaderStyle">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                    </svg>
                    <span class="font-semibold">Apple Podcasts</span>
                    <span v-if="platformResults.apple_podcasts.length > 0" class="ml-auto text-xs px-2 py-1 rounded" :style="resultCountStyle">
                        {{ platformResults.apple_podcasts.length }} found
                    </span>
                </div>
                <div v-if="platformResults.apple_podcasts.length > 0" class="p-4">
                    <select
                        v-model="selectedApple"
                        @change="onAppleSelected"
                        class="input-text"
                    >
                        <option :value="null">-- Select an episode --</option>
                        <option
                            v-for="(result, index) in platformResults.apple_podcasts"
                            :key="index"
                            :value="result.url"
                        >
                            {{ result.title }} ({{ result.score }}% match)
                        </option>
                    </select>
                    <div v-if="selectedApple" class="mt-2 flex items-center gap-2 text-sm text-gray-700 dark:text-dark-150">
                        <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <a :href="selectedApple" target="_blank" rel="noopener noreferrer" class="text-blue-600 dark:text-blue-400 hover:underline truncate">
                            {{ selectedApple }}
                        </a>
                    </div>
                </div>
                <div v-else class="p-4 text-gray-500 dark:text-dark-150 text-sm">
                    No matches found
                </div>
            </div>
        </div>

        <!-- Manual Override Section -->
        <div v-if="config.allow_manual_override && value.episode_id" class="mt-6 pt-6 border-t border-gray-200 dark:border-dark-900">
            <details>
                <summary class="cursor-pointer text-sm text-gray-600 dark:text-dark-150 hover:text-gray-800 dark:hover:text-dark-100 select-none">
                    Enter links manually
                </summary>
                <div class="mt-4 space-y-6">
                    <div>
                        <label class="publish-field-label text-sm">YouTube URL</label>
                        <input
                            type="url"
                            v-model="manualYouTube"
                            @input="onManualYouTubeInput"
                            placeholder="https://www.youtube.com/watch?v=..."
                            class="input-text"
                        />
                    </div>
                    <div>
                        <label class="publish-field-label text-sm">Spotify URL</label>
                        <input
                            type="url"
                            v-model="manualSpotify"
                            @input="onManualSpotifyInput"
                            placeholder="https://open.spotify.com/episode/..."
                            class="input-text"
                        />
                    </div>
                    <div>
                        <label class="publish-field-label text-sm">Apple Podcasts URL</label>
                        <input
                            type="url"
                            v-model="manualApple"
                            @input="onManualAppleInput"
                            placeholder="https://podcasts.apple.com/..."
                            class="input-text"
                        />
                    </div>
                </div>
            </details>
        </div>

        <!-- Error Message -->
        <div v-if="error" class="card mb-6" :style="errorStyle">
            <div class="flex items-start">
                <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span class="text-sm">{{ error }}</span>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    mixins: [Fieldtype],

    computed: {
        isDarkMode() {
            return document.documentElement.classList.contains('dark');
        },
        selectedEpisodeStyle() {
            return this.isDarkMode
                ? { backgroundColor: '#1a1a1a', borderColor: '#374151' }
                : { backgroundColor: '#eff6ff', borderColor: '#bfdbfe' };
        },
        searchingStyle() {
            return this.isDarkMode
                ? { backgroundColor: '#1a1a1a', borderColor: '#374151', color: '#93c5fd' }
                : { backgroundColor: '#eff6ff', borderColor: '#bfdbfe', color: '#1e40af' };
        },
        youtubeHeaderStyle() {
            return this.isDarkMode
                ? { backgroundColor: 'rgba(127, 29, 29, 0.3)', borderColor: '#7f1d1d', color: '#fca5a5' }
                : { backgroundColor: '#fef2f2', borderColor: '#fecaca', color: '#b91c1c' };
        },
        spotifyHeaderStyle() {
            return this.isDarkMode
                ? { backgroundColor: 'rgba(20, 83, 45, 0.3)', borderColor: '#14532d', color: '#86efac' }
                : { backgroundColor: '#f0fdf4', borderColor: '#bbf7d0', color: '#15803d' };
        },
        appleHeaderStyle() {
            return this.isDarkMode
                ? { backgroundColor: 'rgba(88, 28, 135, 0.3)', borderColor: '#581c87', color: '#d8b4fe' }
                : { backgroundColor: '#faf5ff', borderColor: '#e9d5ff', color: '#7e22ce' };
        },
        errorStyle() {
            return this.isDarkMode
                ? { backgroundColor: 'rgba(127, 29, 29, 0.2)', borderColor: '#b91c1c', color: '#fca5a5' }
                : { backgroundColor: '#fee2e2', borderColor: '#f87171', color: '#b91c1c' };
        },
        resultCountStyle() {
            return this.isDarkMode
                ? { backgroundColor: 'rgba(0, 0, 0, 0.3)', color: 'inherit' }
                : { backgroundColor: 'rgba(255, 255, 255, 0.7)', color: 'inherit' };
        },
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
                this.update({
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

            this.update({
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
                    // Update only YouTube results
                    this.platformResults.youtube = response.data.results.youtube || [];
                    // Clear the YouTube warning since we forced the search
                    if (this.platformWarnings) {
                        this.platformWarnings.youtube = null;
                    }

                    // Auto-select first result if available
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
            this.update({
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

<style scoped>
.podcast-link-finder {
    max-width: 56rem;
}

.select-wrapper {
    position: relative;
    display: inline-block;
}

.select-wrapper select {
    padding-right: 2rem;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
}

.select-chevron {
    position: absolute;
    top: 50%;
    right: 0.5rem;
    transform: translateY(-50%);
    pointer-events: none;
    color: #6b7280;
}

:global(.dark) .select-chevron {
    color: #9ca3af;
}

details[open] summary {
    margin-bottom: 1rem;
}
</style>
