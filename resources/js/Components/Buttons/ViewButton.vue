<script setup>
    import { ref } from 'vue';

    const props = defineProps({
        href: {
            type: [String, Number],
            required: true,
        },
        tooltip: {
            type: String,
            default: '',
        },
        target: {
            type: String,
            default: '_self',
        }
    });

    const showTooltip = ref(false);
</script>

<template>
    <div class="relative inline-block">
        <a 
            :href="href" 
            class="w-full inline-flex justify-center text-base sm:ml-3 sm:w-auto sm:text-sm" 
            :aria-label="tooltip"
            :target="target"
            @mouseover="showTooltip = true" 
            @mouseleave="showTooltip = false" 
            @focus="showTooltip = true" 
            @blur="showTooltip = false"
        >
            <slot />
            <svg class="w-7 h-7 text-green-700 hover:text-indigo-900" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="M5 7h14M5 12h14M5 17h14"/>
            </svg>
        </a>
        <div 
            v-if="tooltip && showTooltip" 
            class="absolute z-10 w-auto p-1 text-sm text-white bg-green-700 rounded-lg tooltip"
        >
            {{ tooltip }}
        </div>
    </div>
</template>

<style scoped>
    .tooltip {
        bottom: 70%;
        left: 50%;
        transform: translateX(-50%);
        margin-bottom: 0.5rem;
        opacity: 0;
        transition: opacity 0.2s;
        visibility: hidden;
    }

    .relative:hover .tooltip,
    .relative:focus-within .tooltip {
        opacity: 1;
        visibility: visible;
    }
</style>