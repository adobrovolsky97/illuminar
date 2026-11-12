<template>
    <article class="card border-l-4 p-4" :class="borderColor">
        <div class="flex flex-wrap items-start justify-between gap-2">
            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <span class="badge" :class="typeColor">{{ typeLabel }}</span>
                <span v-if="item.title" class="truncate text-sm font-medium" :title="item.title">{{ item.title }}</span>
            </div>
            <span class="shrink-0 text-gray-500" :title="item.timestamp">{{ item.time }}</span>
        </div>

        <div v-if="item.caller" class="mt-2 truncate text-gray-500">
            <a v-if="item.caller_url" :href="item.caller_url" class="hover:text-blue-500 hover:underline"
               title="Open in editor">{{ item.caller }}</a>
            <span v-else>{{ item.caller }}</span>
        </div>

        <div v-if="item.sql" class="group relative mt-3">
            <pre class="code-block whitespace-pre-wrap break-words"><code ref="sql" class="language-sql" v-html="highlightedSql"></code></pre>
            <button type="button" class="btn absolute right-2 top-2 opacity-0 transition group-hover:opacity-100"
                    @click="copy(item.sql)">{{ copied ? 'Copied' : 'Copy' }}</button>
        </div>

        <div v-if="item.content && item.content.length" ref="content" class="mt-3 flex flex-col gap-2">
            <div v-for="(section, index) in item.content" :key="index">
                <div v-if="section.label" class="mb-1 font-semibold text-gray-500">{{ section.label }}</div>
                <div class="overflow-x-auto rounded-lg" v-html="section.html"></div>
            </div>
        </div>

        <button v-if="item.preview" @click="previewOpen = true" type="button" class="btn mt-3 w-full justify-center py-2">
            Preview content
        </button>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
            <button v-if="item.context" type="button"
                    class="truncate text-gray-500 hover:text-blue-500 hover:underline"
                    title="Show everything from this request / command / job"
                    @click="$emit('filter-context', item)">
                ⤷ {{ item.context }}
            </button>
            <span v-else></span>

            <div class="flex flex-wrap justify-end gap-2">
                <span v-for="(tag, index) in item.tags" :key="index" class="badge" :class="tagColor(tag)">{{ tag }}</span>
                <span v-if="item.duplicates_count" class="badge border-yellow-500 text-yellow-600 dark:text-yellow-400">
                    duplicates: {{ item.duplicates_count }}
                </span>
            </div>
        </div>

        <preview-modal v-if="previewOpen" :html="item.preview" :title="item.title" @close="previewOpen = false"></preview-modal>
    </article>
</template>

<script>
import {format} from 'sql-formatter';
import hljs from 'highlight.js/lib/core';
import sql from 'highlight.js/lib/languages/sql';
import PreviewModal from './PreviewModal.vue';

hljs.registerLanguage('sql', sql);

const TYPE_LABELS = {
    http_request: 'http',
    request: 'profile',
};

const TYPE_COLORS = {
    dump: 'border-cyan-500 text-cyan-600 dark:text-cyan-400',
    query: 'border-blue-500 text-blue-600 dark:text-blue-400',
    model: 'border-yellow-500 text-yellow-600 dark:text-yellow-400',
    exception: 'border-red-500 text-red-600 dark:text-red-400',
    http_request: 'border-purple-500 text-purple-600 dark:text-purple-400',
    job: 'border-pink-500 text-pink-600 dark:text-pink-400',
    event: 'border-indigo-500 text-indigo-600 dark:text-indigo-400',
    cache: 'border-teal-500 text-teal-600 dark:text-teal-400',
    mail: 'border-green-500 text-green-600 dark:text-green-400',
    log: 'border-amber-500 text-amber-600 dark:text-amber-400',
    request: 'border-slate-500 text-slate-700 dark:text-slate-300',
};

const BORDER_COLORS = {
    orange: 'border-l-orange-500',
    red: 'border-l-red-500',
    green: 'border-l-green-500',
    blue: 'border-l-blue-500',
    purple: 'border-l-purple-500',
    gray: 'border-l-gray-500',
};

const RED_TAGS = ['slow', 'failed', 'DELETE', 'missed', 'forgotten', 'deleted', 'connection failed', 'error', 'critical', 'emergency', 'alert'];
const RED_TAG_PREFIXES = ['N+1:', 'slow queries:'];
const GREEN_TAGS = ['POST', 'created', 'written', 'processed', 'hit', 'restored'];
const BLUE_TAGS = ['GET', 'queued', 'preview'];
const YELLOW_TAGS = ['processing', 'pending', 'duplicate', 'PUT', 'PATCH', 'OPTIONS', 'updated', 'macro', 'warning'];

function formatSql(query) {
    for (const language of ['mysql', 'postgresql', 'sqlite', 'sql']) {
        try {
            return format(query, {language});
        } catch (e) {
            // try next dialect
        }
    }

    return query;
}

export default {
    name: 'ItemCard',
    components: {PreviewModal},
    props: {
        item: {type: Object, required: true},
    },
    emits: ['filter-context'],
    data() {
        return {
            previewOpen: false,
            copied: false,
        };
    },
    computed: {
        typeLabel() {
            return TYPE_LABELS[this.item.type] || this.item.type;
        },
        typeColor() {
            return TYPE_COLORS[this.item.type] || '';
        },
        borderColor() {
            if (BORDER_COLORS[this.item.color]) {
                return BORDER_COLORS[this.item.color];
            }

            const tags = this.item.tags || [];

            if (this.item.type === 'exception' || tags.includes('failed') || tags.includes('slow') || tags.includes('connection failed')
                || tags.some(tag => this.isRedTag(tag))) {
                return 'border-l-red-500';
            }

            return 'border-l-transparent';
        },
        highlightedSql() {
            const formatted = formatSql(this.item.sql);

            try {
                return hljs.highlight(formatted, {language: 'sql'}).value;
            } catch (e) {
                return formatted.replace(/[&<>]/g, char => ({'&': '&amp;', '<': '&lt;', '>': '&gt;'}[char]));
            }
        },
    },
    watch: {
        'item.content_hash'() {
            this.$nextTick(this.initDumps);
        },
    },
    mounted() {
        this.initDumps();
    },
    methods: {
        /**
         * Symfony dumps need JS (expand / collapse, search). Script tags from v-html are not executed,
         * so every dump is initialized manually once.
         */
        initDumps() {
            if (!this.$refs.content || typeof window.Sfdump !== 'function') {
                return;
            }

            this.$refs.content.querySelectorAll('pre.sf-dump[id]').forEach(dump => {
                if (dump.dataset.illuminarInit) {
                    return;
                }

                dump.dataset.illuminarInit = '1';

                try {
                    window.Sfdump(dump.id);
                } catch (e) {
                    // broken dump should not break the page
                }
            });
        },
        isRedTag(tag) {
            const value = String(tag);

            return RED_TAGS.includes(value)
                || RED_TAG_PREFIXES.some(prefix => value.startsWith(prefix))
                || /^5\d\d$/.test(value);
        },
        tagColor(tag) {
            const value = String(tag);

            if (this.isRedTag(value)) {
                return 'border-red-500 text-red-600 dark:text-red-400';
            }
            if (/^4\d\d$/.test(value)) {
                return 'border-yellow-500 text-yellow-600 dark:text-yellow-400';
            }
            if (/^[23]\d\d$/.test(value)) {
                return 'border-green-500 text-green-600 dark:text-green-400';
            }
            if (RED_TAGS.includes(tag)) {
                return 'border-red-500 text-red-600 dark:text-red-400';
            }
            if (GREEN_TAGS.includes(tag)) {
                return 'border-green-500 text-green-600 dark:text-green-400';
            }
            if (BLUE_TAGS.includes(tag)) {
                return 'border-blue-500 text-blue-600 dark:text-blue-400';
            }
            if (YELLOW_TAGS.includes(tag)) {
                return 'border-yellow-500 text-yellow-600 dark:text-yellow-400';
            }

            return 'border-gray-300 dark:border-gray-600';
        },
        async copy(text) {
            try {
                await navigator.clipboard.writeText(text);
                this.copied = true;
                setTimeout(() => (this.copied = false), 1500);
            } catch (e) {
                // clipboard is not available (http without localhost)
            }
        },
    },
};
</script>
