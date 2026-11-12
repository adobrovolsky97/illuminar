<template>
    <teleport to="body">
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" @click.self="$emit('close')">
            <div class="flex h-full max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white shadow-xl dark:bg-gray-800">
                <div class="flex items-center justify-between border-b border-gray-200 px-4 py-2 text-sm dark:border-gray-700">
                    <span class="truncate font-medium">{{ title || 'Preview' }}</span>
                    <button type="button" class="btn" @click="$emit('close')">Close</button>
                </div>
                <!-- Sandboxed: mail HTML can not run scripts or break the Illuminar styles -->
                <iframe class="w-full flex-1 bg-white" sandbox="allow-popups allow-popups-to-escape-sandbox" :srcdoc="html"></iframe>
            </div>
        </div>
    </teleport>
</template>

<script>
export default {
    name: 'PreviewModal',
    props: {
        html: {type: String, required: true},
        title: {type: String, default: null},
    },
    emits: ['close'],
    mounted() {
        document.addEventListener('keydown', this.onKeydown);
    },
    beforeUnmount() {
        document.removeEventListener('keydown', this.onKeydown);
    },
    methods: {
        onKeydown(event) {
            if (event.key === 'Escape') {
                this.$emit('close');
            }
        },
    },
};
</script>
