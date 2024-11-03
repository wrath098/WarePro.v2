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
            <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M16.444 18H19a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.556M17 11V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v6h10ZM7 15h10v4a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-4Z"/>
        </svg>
        </a>
        <div v-if="tooltip" class="absolute z-10 w-auto p-1 text-sm text-white bg-gray-800 rounded-lg tooltip">
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