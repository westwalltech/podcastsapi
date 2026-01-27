<template>
    <div class="youtube-livestream-fieldtype">
        <!-- Disabled -->
        <div v-if="!meta.enabled" class="text-sm text-gray-500 dark:text-gray-400">
            YouTube Livestream fetch is disabled
        </div>

        <!-- Show fetch button when no URL and button should show -->
        <Card v-else-if="!value && meta.showButton" class="p-4 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
            <div class="flex items-center justify-between gap-4">
                <div class="flex-1">
                    <div class="text-sm font-medium text-blue-800 dark:text-blue-300">
                        Fetch YouTube Livestream
                    </div>
                    <div class="text-xs mt-0.5 text-blue-600 dark:text-blue-400">
                        Auto-fetch the scheduled livestream for this message's air date
                    </div>
                </div>
                <Button
                    v-if="!fetching"
                    @click="fetchLivestream"
                    variant="primary"
                    size="sm"
                    text="Fetch Livestream"
                />
                <span v-else class="flex items-center text-xs text-blue-600 dark:text-blue-300">
                    <Icon name="loading" class="size-4 mr-2" />
                    Fetching...
                </span>
            </div>

            <!-- Error message -->
            <Alert v-if="error" variant="danger" class="mt-3">
                {{ error }}
            </Alert>
        </Card>

        <!-- Show URL when we have one -->
        <Card v-else-if="value" class="p-4 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800">
            <div class="flex items-center gap-3">
                <Icon name="checkmark" class="size-5 text-green-600 dark:text-green-400 flex-shrink-0" />
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-green-800 dark:text-green-300">
                        YouTube Livestream
                    </div>
                    <a :href="value" target="_blank" rel="noopener noreferrer" class="text-sm text-blue-600 dark:text-blue-400 hover:underline truncate block">
                        {{ value }}
                    </a>
                </div>
                <Button
                    @click="clearUrl"
                    variant="ghost"
                    size="sm"
                    icon="x"
                    title="Clear URL"
                />
            </div>
        </Card>

        <!-- No air_date set or not eligible -->
        <div v-else class="text-sm text-gray-500 dark:text-gray-400">
            <span v-if="!meta.airDate">Set an air date to enable livestream fetch</span>
            <span v-else>Air date is in the past</span>
        </div>
    </div>
</template>

<script>
import { Fieldtype } from '@statamic/cms';
import {
    Alert,
    Button,
    Card,
    Icon,
} from '@statamic/cms/ui';

export default {
    mixins: [Fieldtype],

    components: {
        Alert,
        Button,
        Card,
        Icon,
    },

    data() {
        return {
            fetching: false,
            error: null,
        };
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
                    this.$emit('update:value', response.data.url);
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
            this.$emit('update:value', null);
            this.error = null;
        },
    },
};
</script>
