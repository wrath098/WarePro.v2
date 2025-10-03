<script setup>
const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    tooltip: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="relative inline-block">
        <a :href="href" class="w-full inline-flex justify-center text-base sm:ml-3 sm:w-auto sm:text-sm" target="_blank" :aria-label="tooltip">
            <slot />
            <svg class="w-7 h-7 text-rose-700 hover:text-rose-900" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24">
                <path fill="currentColor" fill-rule="evenodd" d="M9 2.221V7H4.221a2 2 0 0 1 .365-.5L8.5 2.586A2 2 0 0 1 9 2.22ZM11 2v5a2 2 0 0 1-2 2H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2V4a2 2 0 0 0-2-2zm-6 9a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h.5a2.5 2.5 0 0 0 0-5zm1.5 3H6v-1h.5a.5.5 0 0 1 0 1m4.5-3a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h1.376A2.626 2.626 0 0 0 15 15.375v-1.75A2.626 2.626 0 0 0 12.375 11zm1 5v-3h.375a.626.626 0 0 1 .625.626v1.748a.625.625 0 0 1-.626.626zm5-5a1 1 0 0 0-1 1v5a1 1 0 1 0 2 0v-1h1a1 1 0 1 0 0-2h-1v-1h1a1 1 0 1 0 0-2z" clip-rule="evenodd"/>
            </svg>
        </a>
        <div v-if="tooltip" class="absolute z-10 w-auto p-1 text-xs text-white bg-gray-800 rounded-lg tooltip">
            {{ tooltip }}
        </div>
    </div>
</template>

<style scoped>
.tooltip {
    bottom: 70%;
    left: 65%;
    transform: translateX(-50%);
    margin-bottom: 0.5rem;
    opacity: 0;
    transition: opacity 0.2s;
}
a:hover + .tooltip,
a:focus + .tooltip {
    opacity: 1;
}
</style>