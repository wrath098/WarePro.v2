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
            <svg class="w-7 h-7 text-gray-800 hover:text-indigo-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path fill="currentColor" d="M13 13.15V10q0-.425-.288-.712T12 9t-.712.288T11 10v3.15l-.9-.875Q9.825 12 9.413 12t-.713.3q-.275.275-.275.7t.275.7l2.6 2.6q.3.3.7.3t.7-.3l2.6-2.6q.275-.275.287-.687T15.3 12.3q-.275-.275-.687-.288t-.713.263zM6 22q-.825 0-1.412-.587T4 20V8.825q0-.4.15-.762t.425-.638l4.85-4.85q.275-.275.638-.425t.762-.15H18q.825 0 1.413.588T20 4v16q0 .825-.587 1.413T18 22z"/>
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