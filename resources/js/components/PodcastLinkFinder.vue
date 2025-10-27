<template>
    <div class="podcast-link-finder">
        <!-- Episode Selection -->
        <div class="mb-6">
            <label class="publish-field-label">Select Episode from Transistor</label>
            <input
                type="text"
                v-model="searchQuery"
                @input="debouncedSearch"
                placeholder="Search episodes..."
                class="input-text mb-2"
            />
            <div v-if="loading" class="text-gray-600 text-sm py-2">
                Loading episodes...
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
        <div v-if="value.episode_title" class="selected-episode">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-xs uppercase tracking-wide text-gray-600 mb-1">Selected Episode</div>
                    <div class="font-medium">{{ value.episode_title }}</div>
                </div>
                <button
                    v-if="!searchingPlatforms && !platformResults"
                    @click="searchAllPlatforms"
                    type="button"
                    class="btn"
                >
                    Search Platforms
                </button>
            </div>
        </div>

        <!-- Searching Status -->
        <div v-if="searchingPlatforms" class="status-box searching">
            <div class="flex items-center">
                <svg class="animate-spin h-4 w-4 mr-3 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm">Searching across platforms...</span>
            </div>
        </div>

        <!-- Platform Results -->
        <div v-if="platformResults" class="space-y-4">
            <div class="text-xs uppercase tracking-wide text-gray-600 mb-2">Platform Links</div>

            <!-- YouTube Results -->
            <div class="platform-card">
                <div class="platform-header youtube">
                    <svg class="w-5 h-5 text-[#FF0000]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                    <span class="font-semibold text-gray-900">YouTube</span>
                    <span v-if="platformResults.youtube.length > 0" class="result-count">
                        {{ platformResults.youtube.length }} found
                    </span>
                </div>
                <div v-if="platformResults.youtube.length > 0" class="platform-body">
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
                    <div v-if="selectedYouTube" class="selected-link">
                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <a :href="selectedYouTube" target="_blank" rel="noopener noreferrer">
                            {{ selectedYouTube }}
                        </a>
                    </div>
                </div>
                <div v-else class="platform-body">
                    <div v-if="platformWarnings && platformWarnings.youtube" class="text-sm text-amber-700 bg-amber-50 border border-amber-200 rounded p-3">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            <span>{{ platformWarnings.youtube }}</span>
                        </div>
                    </div>
                    <div v-else class="text-gray-500 text-sm">
                        No matches found
                    </div>
                </div>
            </div>

            <!-- Spotify Results -->
            <div class="platform-card">
                <div class="platform-header spotify">
                    <svg class="w-5 h-5 text-[#1DB954]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>
                    </svg>
                    <span class="font-semibold text-gray-900">Spotify</span>
                    <span v-if="platformResults.spotify.length > 0" class="result-count">
                        {{ platformResults.spotify.length }} found
                    </span>
                </div>
                <div v-if="platformResults.spotify.length > 0" class="platform-body">
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
                    <div v-if="selectedSpotify" class="selected-link">
                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <a :href="selectedSpotify" target="_blank" rel="noopener noreferrer">
                            {{ selectedSpotify }}
                        </a>
                    </div>
                </div>
                <div v-else class="platform-body text-gray-500 text-sm">
                    No matches found
                </div>
            </div>

            <!-- Apple Podcasts Results -->
            <div class="platform-card">
                <div class="platform-header apple">
                    <svg class="w-5 h-5 text-[#9933CC]" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                    </svg>
                    <span class="font-semibold text-gray-900">Apple Podcasts</span>
                    <span v-if="platformResults.apple_podcasts.length > 0" class="result-count">
                        {{ platformResults.apple_podcasts.length }} found
                    </span>
                </div>
                <div v-if="platformResults.apple_podcasts.length > 0" class="platform-body">
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
                    <div v-if="selectedApple" class="selected-link">
                        <svg class="w-3 h-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <a :href="selectedApple" target="_blank" rel="noopener noreferrer">
                            {{ selectedApple }}
                        </a>
                    </div>
                </div>
                <div v-else class="platform-body text-gray-500 text-sm">
                    No matches found
                </div>
            </div>
        </div>

        <!-- Manual Override Section -->
        <div v-if="config.allow_manual_override && value.episode_id" class="manual-override">
            <details>
                <summary class="cursor-pointer text-sm text-gray-600 hover:text-gray-800 select-none">
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
        <div v-if="error" class="status-box error">
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

    data() {
        return {
            episodes: [],
            searchQuery: '',
            selectedEpisodeId: null,
            loading: false,
            searchingPlatforms: false,
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
                    params: { query },
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

        async searchAllPlatforms() {
            this.searchingPlatforms = true;
            this.error = null;

            try {
                const response = await this.$axios.post('/cp/podcast-link-finder/search-platforms', {
                    episode_id: this.selectedEpisodeId,
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
    @apply max-w-4xl;
}

.selected-episode {
    @apply bg-blue-50 border border-blue-200 rounded-md p-4 mb-6;
}

.status-box {
    @apply rounded-md p-4 mb-6;
}

.status-box.searching {
    @apply bg-blue-50 border border-blue-200 text-blue-800;
}

.status-box.error {
    @apply bg-red-50 border border-red-200 text-red-800;
}

.platform-card {
    @apply bg-white border border-gray-300 rounded-md overflow-hidden shadow-sm;
}

.platform-header {
    @apply flex items-center gap-2 px-4 py-3 border-b border-gray-200;
}

.platform-header.youtube {
    @apply bg-red-50 text-red-700 border-red-200;
}

.platform-header.spotify {
    @apply bg-green-50 text-green-700 border-green-200;
}

.platform-header.apple {
    @apply bg-purple-50 text-purple-700 border-purple-200;
}

.result-count {
    @apply ml-auto text-xs bg-white bg-opacity-70 px-2 py-1 rounded font-normal;
}

.platform-body {
    @apply p-4;
}

.selected-link {
    @apply mt-2 flex items-center gap-2 text-xs text-gray-600;
}

.selected-link a {
    @apply text-blue-600 hover:text-blue-800 hover:underline truncate;
}

.manual-override {
    @apply mt-8 pt-6 border-t border-gray-300;
}

.manual-override details[open] summary {
    @apply mb-4;
}

.btn {
    @apply px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 transition-colors;
}
</style>
