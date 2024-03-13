<template>
    <div class="page min-h-screen bg-white dark:bg-gray-800">
        <header-component></header-component>

        <main>
            <div class="mx-auto max-w-7xl text-xs py-6 sm:px-6 lg:px-2 text-black dark:text-white">
                <div class="illuminar-content px-4 mx-auto lg:py-6 md:px-6">
                    <div class="flex flex-wrap mb-24">
                        <div class="w-full lg:w-1/4 lg:block">
                            <div class="p-6 rounded-lg border shadow-lg border-gray-200">
                                <h2 class="text-2xl font-bold">Types</h2>
                                <ul class="mt-2">
                                    <li class="mb-4" v-for="(name, type) in dataTypes" :key="type">
                                        <label :for="type" class="flex items-center">
                                            <input :id="type" :name="type" type="checkbox"
                                                   @change="addTypeForSearch(type)"
                                                   :checked="filters['types[]'].includes(type)" class="w-4 h-4 mr-2">
                                            <span class="text-sm">{{ name }}</span>
                                        </label>
                                    </li>
                                </ul>
                            </div>

                            <div v-if="filters['types[]'].includes('query')"
                                 class="p-6 rounded-lg mt-4 border shadow-lg border-gray-200">
                                <h2 class="text-2xl font-bold">Queries</h2>
                                <ul class="mt-2">
                                    <li class="mb-4">
                                        <label for="group_duplicates" class="flex items-center">
                                            <input :checked="(filters['group_duplicated_queries'] ?? false) == true"
                                                   @change="toggleBooleanFilter('group_duplicated_queries')"
                                                   id="group_duplicates"
                                                   name="group_duplicates"
                                                   type="checkbox"
                                                   class="w-4 h-4 mr-2">
                                            <span class="text-sm">Group duplicated queries</span>
                                        </label>
                                    </li>
                                    <li class="mb-4">
                                        <label for="is_duplicate" class="flex items-center">
                                            <input :checked="(filters['is_duplicate'] ?? false) == true"
                                                   @change="toggleBooleanFilter('is_duplicate')"
                                                   id="is_duplicate"
                                                   name="is_duplicate"
                                                   type="checkbox"
                                                   class="w-4 h-4 mr-2">
                                            <span class="text-sm">Show only duplicated queries</span>
                                        </label>
                                    </li>
                                    <li class="mb-4">
                                        <label for="is_slow" class="flex items-center">
                                            <input :checked="(filters['is_slow'] ?? false) == true"
                                                   @change="toggleBooleanFilter('is_slow')"
                                                   id="is_slow"
                                                   name="is_slow"
                                                   type="checkbox"
                                                   class="w-4 h-4 mr-2">
                                            <span class="text-sm">Show only slow queries</span>
                                        </label>
                                    </li>
                                </ul>
                            </div>
                            <button class="p-4 rounded-lg w-full mt-4 border shadow-lg border-gray-200"
                                    @click="resetFilters">
                                Reset Filters
                            </button>
                            <button class="p-4 rounded-lg w-full mt-4 border shadow-lg border-gray-200"
                                    @click="clearData">
                                Clear Data
                            </button>
                        </div>
                        <div class="w-full md:pl-4 mt-4 md:mt-0 lg:w-3/4">
                            <div class="w-full">
                                <div class="flex space-x-4">
                                    <div class="flex rounded-md overflow-hidden w-full">
                                        <input type="text"
                                               ref="searchInput"
                                               id="search"
                                               name="search"
                                               :value="filters.search"
                                               @input="delayedSearch"
                                               class="py-3 px-4 border ps-11 shadow-lg block w-full bg-white dark:bg-gray-800 border-gray-200 rounded-lg text-sm"
                                               placeholder="Search">
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center">
                                <section class="items-center font-poppins w-full">
                                    <div class="justify-center pt-4 mx-auto" v-if="data.length">
                                        <div class="w-full mx-auto">
                                            <item v-for="item in data" :key="item.uuid" :item="item"></item>
                                        </div>
                                    </div>
                                </section>
                                <button
                                    v-if="hasMorePages"
                                    @click="loadNextPage"
                                    class="w-full font-semibold py-3 px-4 border rounded-lg shadow-lg">
                                    Load older entries
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<script>
import ItemComponent from "./ItemComponent.vue";
import Header from "./Header.vue";

export default {
    name: 'IlluminateComponent',
    components: {
        'header-component': Header,
        'item': ItemComponent
    },
    data() {
        return {
            refreshTimer: null,
            filtersInitiated: false,
            page: 1,
            filters: {
                'types[]': [],
                'search': '',
                'group_duplicated_queries': 0,
                'is_duplicate': 0,
                'is_slow': 0,
            },
            data: [],
            hasMorePages: false,
            dataTypes: {
                'dump': 'Dumps',
                'model': 'Models',
                'query': 'Queries',
                'cache': 'Cache',
                'event': 'Events',
                'mail': 'Mails',
                'http_request': 'Http Requests',
                'job': 'Jobs',
                'exception': 'Exceptions'
            },
        }
    },
    watch: {
        'filters': {
            handler() {
                if (this.filtersInitiated) {
                    this.page = 1;
                    this.fetchData('replace');

                    clearInterval(this.refreshTimer);
                    this.fetchDataByTimer();
                }
            },
            deep: true
        }
    },
    created() {
        this.resolveQueryParams();
        this.fetchDataByTimer();
    },
    methods: {
        /**
         * Fetch data
         */
        fetchData(mode = 'prepend', page = 1) {
            this.axios
                .get('/illuminar/data', {
                    params: {
                        ...this.filters,
                        page: page
                    }
                })
                .then(response => {
                    if (mode === 'replace') {
                        this.data = response.data.data;
                    } else {
                        // Get the uuids of the existing data
                        let existingUuids = this.data.map(item => item.uuid);

                        // Separate the new data into updates and new items
                        let updates = response.data.data.filter(item => existingUuids.includes(item.uuid));
                        let newItems = response.data.data.filter(item => !existingUuids.includes(item.uuid));

                        // Update existing items
                        updates.forEach(update => {
                            let index = this.data.findIndex(item => item.uuid === update.uuid);
                            if (this.data[index].content_hash !== update.content_hash) {
                                this.$set(this.data, index, update);
                            } else {
                                this.data[index].time = update.time;
                                this.data[index].tags = update.tags;
                            }
                        });

                        // Prepend or append new items
                        if (mode === 'prepend') {
                            this.data = [...newItems, ...this.data];
                        } else {
                            this.data = [...this.data, ...newItems];
                        }
                    }
                    this.hasMorePages = response.data.meta.last_page > this.page;
                })
                .catch(error => {
                    console.log(error)
                })
        },
        fetchDataByTimer() {
            this.refreshTimer = setInterval(() => {
                this.fetchData();
            }, 3000);
        },
        /**
         * Toggle type for search
         * @param type
         */
        addTypeForSearch(type) {
            const index = this.filters['types[]'].indexOf(type);
            if (index >= 0) {
                this.filters['types[]'].splice(index, 1);
            } else {
                this.filters['types[]'].push(type);
            }

            this.updateUrlParams('types[]', this.filters['types[]'], true);
        },
        /**
         * Update URL parameters
         * @param param
         * @param value
         * @param isMultiple
         */
        updateUrlParams(param, value, isMultiple = false) {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.delete(param);

            if (isMultiple) {
                value.forEach(val => urlParams.append(param, val));
            } else {
                urlParams.set(param, value);
            }

            let paramString = urlParams.toString();
            paramString = paramString.replace(/%5B/g, '[').replace(/%5D/g, ']');

            window.history.replaceState({}, '', `${window.location.pathname}?${paramString}`);
        },
        /**
         * Resolve URL parameters
         */
        resolveQueryParams() {
            const urlParams = new URLSearchParams(window.location.search);

            for (let key in this.filters) {
                let paramValue = urlParams.getAll(key);
                if (paramValue.length) {
                    // If the filter is an array, get all values from the URL parameters
                    if (Array.isArray(this.filters[key])) {
                        this.filters[key] = paramValue;
                    } else {
                        // If the filter is not an array, get the first value from the URL parameters
                        this.filters[key] = paramValue[0];
                    }
                }
            }

            // If 'types[]' filter is empty, set it to all dataTypes
            if (!this.filters['types[]'].length) {
                this.filters['types[]'] = Object.keys(this.dataTypes);
                this.updateUrlParams('types[]', this.filters['types[]'], true)
            }

            this.filtersInitiated = true;
        },
        /**
         * Toggle boolean filter and set 0 or 1
         * @param key
         */
        toggleBooleanFilter(key) {
            this.filters[key] = this.filters[key] == 1 ? 0 : 1;
            this.updateUrlParams(key, this.filters[key]);
        },
        /**
         * On input
         */
        delayedSearch() {
            clearTimeout(this.refreshTimer);
            this.refreshTimer = setTimeout(() => {
                this.filters['search'] = this.$refs.searchInput.value;
                this.updateUrlParams('search', this.filters['search']);
            }, 500);
        },
        /**
         * Load next page
         */
        loadNextPage() {
            this.page++;
            this.fetchData('append', this.page);
        },
        clearData() {
            this.axios
                .delete('/illuminar/clear')
                .then(() => {
                    this.data = [];
                })
                .catch(error => {
                    console.log(error)
                })
        },
        resetFilters() {
            this.filters = {
                'types[]': Object.keys(this.dataTypes),
                'search': '',
                'group_duplicated_queries': 0,
                'is_duplicate': 0,
                'is_slow': 0,
            };
            this.updateUrlParams('types[]', this.filters['types[]'], true);
            this.updateUrlParams('search', this.filters['search']);
            this.updateUrlParams('group_duplicated_queries', this.filters['group_duplicated_queries']);
            this.updateUrlParams('is_duplicate', this.filters['is_duplicate']);
            this.updateUrlParams('is_slow', this.filters['is_slow']);
        }
    }
}
</script>
