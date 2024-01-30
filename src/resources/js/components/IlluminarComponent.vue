<template>
    <div class="illuminar-content px-4 mx-auto lg:py-6 md:px-6">
        <div class="flex flex-wrap mb-24">
            <div class="w-full lg:w-1/4 lg:block">
                <div class="p-4 rounded-lg border shadow-lg border-gray-200">
                    <h2 class="text-2xl font-bold dark:text-gray-400">Types</h2>
                    <ul class="mt-2">
                        <li class="mb-4" v-for="(name, type) in dataTypes" :key="type">
                            <label :for="type" class="flex items-center">
                                <input :id="type" :name="type" type="checkbox" @change="addTypeForSearch(type)"
                                       :checked="filters['types[]'].includes(type)" class="w-4 h-4 mr-2">
                                <span class="text-sm">{{ name }}</span>
                            </label>
                        </li>
                    </ul>
                </div>

                <div v-if="filters['types[]'].includes('query')"
                     class="p-4 rounded-lg mt-4 border shadow-lg border-gray-200">
                    <h2 class="text-2xl font-bold dark:text-gray-400">Queries</h2>
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
                                   @input="onInput"
                                   class="py-3 px-4 border ps-11 shadow-lg block w-full border-gray-200 rounded-lg text-sm"
                                   placeholder="Search">
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap items-center">
                    <section class="items-center font-poppins w-full">
                        <div class="justify-center pt-4 mx-auto" v-if="data.length">
                            <div class="w-full mx-auto">
                                <item v-for="(item, index) in data" :key="index" :item="item"></item>
                            </div>
                        </div>
                    </section>
                    <button
                        v-if="hasMorePages"
                        @click="loadNextPage"
                        class="bg-white hover:bg-gray-100 w-full font-semibold py-3 px-4 border rounded-lg shadow-lg">
                        Load more entries
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import ItemComponent from "./ItemComponent.vue";

export default {
    name: 'IlluminateComponent',
    components: {
        'item': ItemComponent
    },
    data() {
        return {
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
    created() {
        this.resolveQueryParams();
        this.fetchData();
        this.fetchDataByTimer();
    },
    methods: {
        /**
         * Fetch data
         */
        fetchData(mode = 'replace', page = 1) {
            this.axios
                .get('/illuminar/data', {
                    params: {
                        ...this.filters,
                        page: page
                    }
                })
                .then(response => {
                    if (mode === 'replace') {
                        this.data.unshift(...response.data.data.filter(item => !this.data.find(i => i.uuid === item.uuid)));
                    } else {
                        this.data.push(...response.data.data.filter(item => !this.data.find(i => i.uuid === item.uuid)));
                    }
                    this.hasMorePages = response.data.meta.last_page > this.page;
                })
                .catch(error => {
                    console.log(error)
                })
        },
        fetchDataByTimer() {
            setInterval(() => {
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
        onInput() {
            this.filters['search'] = this.$refs.searchInput.value;
            this.updateUrlParams('search', this.filters['search']);
        },
        /**
         * Load next page
         */
        loadNextPage() {
            this.page++;
            this.fetchData('append', this.page);
        }
    }
}
</script>
