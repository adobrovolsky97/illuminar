<template>
    <div class="min-h-screen bg-white text-gray-900 dark:bg-gray-900 dark:text-gray-100">
        <header class="sticky top-0 z-20 border-b border-gray-200 bg-white/90 backdrop-blur dark:border-gray-700 dark:bg-gray-900/90">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3">
                <div class="flex items-baseline gap-3">
                    <a :href="basePath" class="text-xl font-semibold">Illuminar</a>
                    <span v-if="appName" class="text-sm text-gray-500 dark:text-gray-400">{{ appName }}</span>
                </div>
                <div class="flex items-center gap-2 text-sm">
                    <span v-if="error" class="text-red-500" :title="error">connection error</span>
                    <button type="button" class="btn" @click="toggleLive"
                            :title="live ? 'Pause auto refresh' : 'Resume auto refresh'">
                        <span class="inline-block h-2 w-2 rounded-full"
                              :class="live ? 'bg-green-500 animate-pulse' : 'bg-gray-400'"></span>
                        {{ live ? 'Live' : 'Paused' }}
                    </button>
                    <button type="button" class="btn" @click="toggleTheme" title="Toggle theme">
                        {{ dark ? 'Light' : 'Dark' }}
                    </button>
                    <button type="button" class="btn hover:border-red-500 hover:text-red-500" @click="clearData">
                        Clear
                    </button>
                </div>
            </div>
        </header>

        <main class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-6 text-xs lg:flex-row">
            <aside class="w-full shrink-0 space-y-4 lg:sticky lg:top-20 lg:w-64 lg:self-start">
                <section class="card p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-semibold">Types</h2>
                        <button type="button" class="text-gray-500 hover:underline" @click="toggleAllTypes">
                            {{ filters.types.length === allTypes.length ? 'none' : 'all' }}
                        </button>
                    </div>
                    <ul class="space-y-2">
                        <li v-for="(name, type) in dataTypes" :key="type">
                            <label class="flex cursor-pointer items-center justify-between gap-2">
                                <span class="flex items-center gap-2">
                                    <input type="checkbox" class="h-4 w-4" :value="type" v-model="filters.types">
                                    <span class="text-sm">{{ name }}</span>
                                    <span v-if="tracking.includes(type)" class="text-green-500" title="Tracked for every request (config)">●</span>
                                </span>
                                <span class="text-gray-500">{{ typeCounts[type] || 0 }}</span>
                            </label>
                        </li>
                    </ul>
                </section>

                <section v-if="filters.types.includes('query')" class="card p-4">
                    <h2 class="mb-3 text-base font-semibold">Queries</h2>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" class="h-4 w-4" v-model="filters.group_duplicated_queries">
                                Group duplicated queries
                            </label>
                        </li>
                        <li>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" class="h-4 w-4" v-model="filters.is_duplicate">
                                Only duplicated (N+1)
                            </label>
                        </li>
                        <li>
                            <label class="flex cursor-pointer items-center gap-2">
                                <input type="checkbox" class="h-4 w-4" v-model="filters.is_slow">
                                Only slow
                            </label>
                        </li>
                    </ul>
                </section>

                <button type="button" class="btn w-full justify-center py-3" @click="resetFilters">Reset filters</button>
            </aside>

            <section class="min-w-0 flex-1">
                <div class="relative">
                    <input type="search"
                           ref="searchInput"
                           :value="filters.search"
                           @input="onSearchInput"
                           class="card w-full px-4 py-3 text-sm outline-none focus:border-blue-500"
                           placeholder="Search (press / to focus): SQL, dump content, class, url, caller...">
                </div>

                <div v-if="filters.context_id"
                     class="mt-3 flex items-center justify-between rounded-lg border border-blue-500 px-4 py-2 text-sm text-blue-600 dark:text-blue-400">
                    <span>Showing only: <b>{{ filters.context || filters.context_id }}</b></span>
                    <button type="button" class="hover:underline" @click="setContext(null)">show all</button>
                </div>

                <div class="mt-4 space-y-4">
                    <item-card v-for="item in items"
                               :key="item.uuid"
                               :item="item"
                               @filter-context="setContext"></item-card>
                </div>

                <div v-if="!items.length && !loading" class="card mt-4 p-10 text-center text-sm text-gray-500">
                    <p class="mb-2 text-base">No entries yet.</p>
                    <p>Use <code class="code">illuminar($value)</code>, <code class="code">illuminar()->trackQueries()</code>
                        or enable watchers in <code class="code">config/illuminar.php</code> (<code class="code">ILLUMINAR_TRACK_QUERIES=true</code>).</p>
                </div>

                <button v-if="hasMorePages"
                        type="button"
                        @click="loadNextPage"
                        class="btn mt-4 w-full justify-center py-3 font-semibold">
                    Load older entries
                </button>
            </section>
        </main>
    </div>
</template>

<script>
import ItemCard from './ItemCard.vue';
import {basePath, clearEntries, fetchEntries} from '../api';

const DATA_TYPES = {
    request: 'Requests',
    dump: 'Dumps',
    query: 'Queries',
    model: 'Models',
    exception: 'Exceptions',
    http_request: 'Http Requests',
    job: 'Jobs',
    event: 'Events',
    cache: 'Cache',
    mail: 'Mails',
    log: 'Logs',
};

const BOOLEAN_FILTERS = ['group_duplicated_queries', 'is_duplicate', 'is_slow'];
const REFRESH_INTERVAL = 2000;

function defaultFilters() {
    return {
        types: Object.keys(DATA_TYPES),
        search: '',
        context_id: null,
        context: null,
        group_duplicated_queries: false,
        is_duplicate: false,
        is_slow: false,
    };
}

function readStorage(key) {
    try {
        return localStorage.getItem(key);
    } catch (e) {
        return null;
    }
}

function writeStorage(key, value) {
    try {
        localStorage.setItem(key, value);
    } catch (e) {
        // storage is not available
    }
}

export default {
    name: 'App',
    components: {ItemCard},
    data() {
        const config = window.Illuminar || {};

        return {
            basePath,
            appName: config.appName,
            tracking: config.tracking || [],
            dataTypes: DATA_TYPES,
            allTypes: Object.keys(DATA_TYPES),
            filters: this.filtersFromUrl(),
            items: [],
            typeCounts: {},
            page: 1,
            hasMorePages: false,
            loading: false,
            error: null,
            live: readStorage('illuminar-live') !== '0',
            dark: document.documentElement.classList.contains('dark'),
            timer: null,
            searchTimer: null,
            controller: null,
        };
    },
    watch: {
        filters: {
            deep: true,
            handler() {
                this.syncUrl();
                this.page = 1;
                this.refresh(true);
            },
        },
    },
    mounted() {
        this.refresh(true);
        this.schedule();

        document.addEventListener('visibilitychange', this.onVisibilityChange);
        document.addEventListener('keydown', this.onKeydown);
    },
    beforeUnmount() {
        clearTimeout(this.timer);
        document.removeEventListener('visibilitychange', this.onVisibilityChange);
        document.removeEventListener('keydown', this.onKeydown);
    },
    methods: {
        requestParams(page = 1) {
            const params = {page, types: this.filters.types};

            if (this.filters.search) {
                params.search = this.filters.search;
            }

            if (this.filters.context_id) {
                params.context_id = this.filters.context_id;
            }

            BOOLEAN_FILTERS.forEach(key => {
                if (this.filters[key]) {
                    params[key] = 1;
                }
            });

            return params;
        },
        /**
         * Load the first page. When replace is false (polling), already rendered entries keep
         * their state (expanded dumps etc.) unless their content was changed.
         */
        async refresh(replace = false) {
            if (!this.filters.types.length) {
                this.items = [];
                this.hasMorePages = false;
                return;
            }

            this.controller?.abort();
            this.controller = new AbortController();
            this.loading = true;

            try {
                const response = await fetchEntries(this.requestParams(1), this.controller.signal);

                this.typeCounts = response.type_counts || {};
                this.error = null;

                if (replace) {
                    this.items = response.data;
                    this.hasMorePages = response.meta.last_page > 1;
                } else {
                    this.mergeFirstPage(response.data);
                    this.hasMorePages = response.meta.last_page > this.page;
                }
            } catch (e) {
                if (e.name !== 'AbortError') {
                    this.error = e.message;
                }
            } finally {
                this.loading = false;
            }
        },
        mergeFirstPage(incoming) {
            const existing = new Map(this.items.map(item => [item.uuid, item]));
            const incomingUuids = new Set(incoming.map(item => item.uuid));

            const firstPage = incoming.map(item => {
                const current = existing.get(item.uuid);

                if (!current || current.content_hash !== item.content_hash) {
                    return item;
                }

                // Keep the same object, so the rendered dump is not re-created
                current.time = item.time;
                current.tags = item.tags;
                current.duplicates_count = item.duplicates_count;

                return current;
            });

            // When older pages are loaded, keep them below the fresh first page
            const older = this.page > 1 ? this.items.filter(item => !incomingUuids.has(item.uuid)) : [];

            this.items = [...firstPage, ...older];
        },
        async loadNextPage() {
            this.page++;

            try {
                const response = await fetchEntries(this.requestParams(this.page));
                const uuids = new Set(this.items.map(item => item.uuid));

                this.items = [...this.items, ...response.data.filter(item => !uuids.has(item.uuid))];
                this.hasMorePages = response.meta.last_page > this.page;
            } catch (e) {
                this.error = e.message;
            }
        },
        schedule() {
            clearTimeout(this.timer);

            this.timer = setTimeout(async () => {
                if (this.live && !document.hidden) {
                    await this.refresh(false);
                }

                this.schedule();
            }, REFRESH_INTERVAL);
        },
        onVisibilityChange() {
            if (!document.hidden && this.live) {
                this.refresh(false);
            }
        },
        onKeydown(event) {
            if (event.key === '/' && !['INPUT', 'TEXTAREA'].includes(document.activeElement?.tagName)) {
                event.preventDefault();
                this.$refs.searchInput.focus();
            }
        },
        onSearchInput(event) {
            clearTimeout(this.searchTimer);

            this.searchTimer = setTimeout(() => {
                this.filters.search = event.target.value;
            }, 400);
        },
        setContext(item) {
            this.filters.context_id = item ? item.context_id : null;
            this.filters.context = item ? item.context : null;
            window.scrollTo({top: 0, behavior: 'smooth'});
        },
        toggleAllTypes() {
            this.filters.types = this.filters.types.length === this.allTypes.length ? [] : [...this.allTypes];
        },
        toggleLive() {
            this.live = !this.live;
            writeStorage('illuminar-live', this.live ? '1' : '0');

            if (this.live) {
                this.refresh(false);
            }
        },
        toggleTheme() {
            this.dark = !this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            writeStorage('illuminar-theme', this.dark ? 'dark' : 'light');
        },
        async clearData() {
            try {
                await clearEntries();
                this.items = [];
                this.typeCounts = {};
                this.hasMorePages = false;
            } catch (e) {
                this.error = e.message;
            }
        },
        resetFilters() {
            this.filters = defaultFilters();
            this.$refs.searchInput.value = '';
        },
        filtersFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const filters = defaultFilters();

            const types = params.getAll('types[]').filter(type => type in DATA_TYPES);
            if (types.length) {
                filters.types = types;
            }

            filters.search = params.get('search') || '';
            filters.context_id = params.get('context_id');
            filters.context = params.get('context');

            BOOLEAN_FILTERS.forEach(key => {
                filters[key] = params.get(key) === '1';
            });

            return filters;
        },
        syncUrl() {
            const params = new URLSearchParams();

            if (this.filters.types.length !== this.allTypes.length) {
                this.filters.types.forEach(type => params.append('types[]', type));
            }

            ['search', 'context_id', 'context'].forEach(key => {
                if (this.filters[key]) {
                    params.set(key, this.filters[key]);
                }
            });

            BOOLEAN_FILTERS.forEach(key => {
                if (this.filters[key]) {
                    params.set(key, '1');
                }
            });

            const query = params.toString().replace(/%5B/g, '[').replace(/%5D/g, ']');
            window.history.replaceState({}, '', `${window.location.pathname}${query ? '?' + query : ''}`);
        },
    },
};
</script>
