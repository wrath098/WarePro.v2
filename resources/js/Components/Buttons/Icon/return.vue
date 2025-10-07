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
            <svg class="w-6 h-6 text-rose-700 hover:text-rose-900" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14 14">
                <path fill="currentColor" fill-rule="evenodd" d="M10.343.475C9.269.355 8.148.25 7 .25S4.731.355 3.657.475A3.63 3.63 0 0 0 .463 3.678C.348 4.747.25 5.86.25 7s.098 2.253.213 3.322a3.63 3.63 0 0 0 3.194 3.203c1.074.12 2.195.225 3.343.225s2.269-.105 3.343-.225a3.63 3.63 0 0 0 3.194-3.203c.115-1.069.213-2.182.213-3.322s-.098-2.253-.213-3.322A3.63 3.63 0 0 0 10.343.475m-3.055 5.98l.168.67H6.5A1.125 1.125 0 0 1 5.375 6V4a.875.875 0 1 0-1.75 0v2A2.875 2.875 0 0 0 6.5 8.875h.956l-.168.67a.75.75 0 0 0 .728.932c.618 0 1.178-.382 1.555-.76c.401-.4.752-.947.9-1.535a.75.75 0 0 0 0-.364c-.148-.588-.499-1.135-.9-1.536c-.377-.377-.937-.759-1.555-.759a.75.75 0 0 0-.728.932" clip-rule="evenodd"/>
            </svg>
        </button>
        <div v-if="tooltip" class="absolute z-10 w-auto p-1 text-xs text-white bg-green-700 rounded-lg tooltip">
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
