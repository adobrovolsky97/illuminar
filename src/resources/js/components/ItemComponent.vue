<template>
    <div class="relative flex-1 mb-4 rounded-lg shadow-lg border"
         :class="getMappedColor(item.color ?? '')">
        <div class="relative z-1 p-6">
            <div class="data flex flex-row justify-between">
                <p class="mb-2" v-if="item.caller"><i>{{ item.caller }}</i></p>
                <p class="mb-2" v-if="item.time"><i>{{ item.time }}</i></p>
            </div>
            <div class="item-content w-full inline-block"
                 ref="dumpContainer"
                 v-if="item.content">
                <div class="flex flex-col gap-2">
                    <code v-for="(content, index) in item.content"
                          class="border-gray-200 rounded-lg bg-gray-50 border p-2" :key="index"
                          v-html="content"></code>
                </div>
            </div>

            <div v-if="item.sql"
                 class="sql-code inline-block border p-2 border-gray-200 rounded-lg bg-gray-50 w-full">
                <code class="language-sql whitespace-pre-wrap break-words" ref="sqlCode"
                      v-html="formatSql(item.sql)"></code>
            </div>

            <div class="preview" v-if="item.preview">
                <button @click="openPreview" type="button"
                        class="text-gray-900 mt-2 w-full bg-white border border-gray-300 hover:bg-gray-100 font-medium rounded-lg text-sm px-5 py-2.5 me-2">
                    Preview Content
                </button>
                <modal height="auto" scrollable :name="'preview-'+item.uuid">
                    <div v-html="item.preview ?? ''"></div>
                </modal>
            </div>

            <div class="data flex mt-4 flex-row justify-between">
                <div
                    class="inline-block border-gray-500 text-gray-800 px-3 py-2 text-xs font-medium rounded-lg border">
                    {{ item.type }}
                </div>
                <div class="tags flex flex-row gap-2 justify-end" v-if="Object.values(item.tags ?? [])?.length">
                    <div
                        v-if="item.duplicates_count"
                        class="inline-block border-yellow-500 text-yellow-800 px-3 py-2 text-xs font-medium rounded-lg border">
                        duplicates: {{ item.duplicates_count }}
                    </div>
                    <div
                        v-for="(tag, index) in Object.values(item.tags)" :key="index"
                        :class="getTagColorOptions(tag)"
                        class="inline-block px-3 py-2 text-xs font-medium rounded-lg border">
                        {{ tag }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import {Sfdump} from "../dump";
import {format} from "sql-formatter";
import hljs from "highlight.js/lib/core";
import 'highlight.js/styles/github.css'

export default {
    props: {
        item: {
            type: Object,
            required: true
        }
    },
    mounted() {
        this.highlightSql();
    },
    updated() {
        this.highlightSql();
    },
    watch: {
        item: {
            immediate: true,
            handler() {
                this.$nextTick(() => {
                    const container = this.$refs.dumpContainer;

                    if (!container) {
                        return;
                    }

                    container.querySelectorAll('.sf-dump').forEach((dump) => {
                        Sfdump(dump.id);
                    });
                });
            }
        }
    },
    methods: {
        highlightSql() {
            if (this.$refs.sqlCode) {
                this.$nextTick(() => {
                    if (this.$refs.sqlCode.dataset.highlighted !== 'yes') {
                        hljs.highlightElement(this.$refs.sqlCode);
                        this.$refs.sqlCode.dataset.highlighted = 'yes';
                    }
                });
            }
        },
        openPreview() {
            this.$modal.show('preview-' + this.item.uuid)
        },
        formatSql(sql) {
            return format(sql);
        },
        getMappedColor(type) {
            switch (type) {
                case 'dump':
                case 'event':
                case 'orange':
                    return 'border-yellow-500 ';
                case 'exception':
                case 'mail':
                case 'red':
                    return 'border-red-500';
                case 'cache':
                case 'model':
                case 'green':
                    return 'border-green-500';
                case 'job':
                case 'query':
                case 'blue':
                    return 'border-blue-500';
                case 'http_request':
                    return 'border-purple-500';
                default:
                    return '';
            }
        },
        getTagColorOptions(tag) {
            switch (tag) {
                case 'dump':
                case 'event':
                case 'PUT':
                case 'PATCH':
                case 'OPTIONS':
                case 'updated':
                    return 'border-yellow-500 text-yellow-800';
                case 'exception':
                case 'mail':
                    return 'border-red-500 text-red-800';
                case 'cache':
                case 'model':
                case 'POST':
                case 'created':
                case 'written':
                    return 'border-green-500 text-greed-800';
                case 'job':
                case 'query':
                case 'queued':
                case 'GET':
                    return 'border-blue-500 text-blue-800';
                case 'http_request':
                    return 'border-purple-500 text-purple-800';
                case 'slow':
                case 'failed':
                case 'DELETE':
                case 'missed':
                case 'deleted':
                    return 'border-red-500 text-red-800';
                case 'processing':
                case 'duplicate':
                case 'macro':
                    return 'border-yellow-500 text-yellow-800';
                case 'processed':
                    return 'border-green-500 text-green-800';
                default:
                    return 'border-gray-500';
            }
        }
    }
}
</script>
