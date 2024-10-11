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
        <button @click="onClick" class="w-full inline-flex justify-center text-base sm:ml-3 sm:w-auto sm:text-sm" :aria-label="tooltip">
            <slot />
            <svg class="w-7 h-7 text-blue-700 hover:text-indigo-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16h13M4 16l4-4m-4 4 4 4M20 8H7m13 0-4 4m4-4-4-4"/>
            </svg>
        </button>
        <div v-if="tooltip" class="absolute z-10 w-auto p-1 text-sm text-white bg-blue-700 rounded-lg tooltip">
            {{ tooltip }}
        </div>
    </div>
</template>

<style scoped>
.tooltip {
    bottom: 70%;
    left: 60%;
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
