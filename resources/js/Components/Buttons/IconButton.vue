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
        </button>
        <div v-if="tooltip" class="absolute z-10 w-20 p-1 text-sm text-white bg-indigo-700 rounded-lg tooltip">
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