<script setup>
const props = defineProps({
    onClick: {
        type: Function,
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
        <button @click="onClick" class="inline-flex justify-center text-base sm:ml-3 sm:w-auto sm:text-sm" :aria-label="tooltip">
            <slot />
            <svg class="w-6 h-6 text-blue-700 hover:text-indigo-900" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M10 12v1h4v-1m4 7H6a1 1 0 0 1-1-1V9h14v9a1 1 0 0 1-1 1ZM4 5h16a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
            </svg>
            
        </button>
        <div v-if="tooltip" class="absolute z-10 w-14 p-1 text-sm text-white bg-blue-700 rounded-lg tooltip">
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
button:hover + .tooltip,
button:focus + .tooltip {
    opacity: 1;
}
</style>