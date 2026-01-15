<template>
    <div class="youtube-livestream-fieldtype">
        <!-- No air_date or disabled -->
        <div v-if="!meta.enabled" class="text-sm text-gray-500 dark:text-dark-150">
            YouTube Livestream fetch is disabled
        </div>

        <!-- Show fetch button when no URL and button should show -->
        <div v-else-if="!value && meta.showButton" class="card" :style="cardStyle">
            <div class="flex items-center justify-between gap-4">
                <div class="flex-1">
                    <div class="text-sm font-medium" :class="isDarkMode ? 'text-blue-300' : 'text-blue-800'">
                        Fetch YouTube Livestream
                    </div>
                    <div class="text-xs mt-0.5" :class="isDarkMode ? 'text-blue-400' : 'text-blue-600'">
                        Auto-fetch the scheduled livestream for this message's air date
                    </div>
                </div>
                <button
                    v-if="!fetching"
                    @click="fetchLivestream"
                    type="button"
                    class="btn-primary text-xs px-3 py-1.5 flex-shrink-0"
                >
                    Fetch Livestream
                </button>
                <span v-else class="flex items-center text-xs" :class="isDarkMode ? 'text-blue-300' : 'text-blue-600'">
                    <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Fetching...
                </span>
            </div>

            <!-- Error message -->
            <div v-if="error" class="mt-3 text-sm p-3 rounded" :style="errorStyle">
                <div class="flex items-start gap-2">
                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ error }}</span>
                </div>
            </div>
        </div>

        <!-- Show URL when we have one -->
        <div v-else-if="value" class="card" :style="successCardStyle">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 flex-shrink-0" :class="isDarkMode ? 'text-green-400' : 'text-green-600'" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium" :class="isDarkMode ? 'text-green-300' : 'text-green-800'">
                        YouTube Livestream
                    </div>
                    <a :href="value" target="_blank" rel="noopener noreferrer" class="text-sm text-blue-600 dark:text-blue-400 hover:underline truncate block">
                        {{ value }}
                    </a>
                </div>
                <button
                    @click="clearUrl"
                    type="button"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1"
                    title="Clear URL"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- No air_date set or not eligible -->
        <div v-else class="text-sm text-gray-500 dark:text-dark-150">
            <span v-if="!meta.airDate">Set an air date to enable livestream fetch</span>
            <span v-else>Air date is in the past</span>
        </div>
    </div>
</template>

<script>
import { Fieldtype } from '@statamic/cms';

export default {
    mixins: [Fieldtype],

    data() {
        return {
            fetching: false,
            error: null,
        };
    },

    computed: {
        isDarkMode() {
            return document.documentElement.classList.contains('dark');
        },
        cardStyle() {
            return this.isDarkMode
                ? { backgroundColor: 'rgba(30, 58, 138, 0.2)', borderColor: '#1e3a8a' }
                : { backgroundColor: '#eff6ff', borderColor: '#bfdbfe' };
        },
        successCardStyle() {
            return this.isDarkMode
                ? { backgroundColor: 'rgba(20, 83, 45, 0.2)', borderColor: '#14532d' }
                : { backgroundColor: '#f0fdf4', borderColor: '#bbf7d0' };
        },
        errorStyle() {
            return this.isDarkMode
                ? { backgroundColor: 'rgba(127, 29, 29, 0.3)', color: '#fca5a5' }
                : { backgroundColor: '#fee2e2', color: '#b91c1c' };
        },
    },

    methods: {
        async fetchLivestream() {
            if (!this.meta.entryId) {
                this.error = 'Please save the entry first before fetching the livestream.';
                return;
            }

            this.fetching = true;
            this.error = null;

            try {
                const response = await this.$axios.post(`/cp/podcast-link-finder/youtube-livestream/${this.meta.entryId}`);

                if (response.data.success) {
                    this.update(response.data.url);
                } else {
                    this.error = response.data.message || 'Failed to fetch livestream';
                }
            } catch (err) {
                console.error('Livestream fetch error:', err);
                if (err.response && err.response.data && err.response.data.message) {
                    this.error = err.response.data.message;
                } else {
                    this.error = 'Failed to fetch livestream. Please try again.';
                }
            } finally {
                this.fetching = false;
            }
        },

        clearUrl() {
            this.update(null);
            this.error = null;
        },
    },
};
</script>

<style scoped>
.youtube-livestream-fieldtype {
    width: 100%;
}

.card {
    padding: 1rem;
    border-radius: 0.375rem;
    border: 1px solid;
}
</style>
