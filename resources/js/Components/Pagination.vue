<script setup>
import { Link } from '@inertiajs/vue3';
const props = defineProps({
    links: {
        type: Array,
    }
});
</script>

<template>
    <div>
        <template v-for="link in props.links" :key="link.label">
            <Component
                v-if="link.url" 
                :is="Link"
                v-bind:href="link.url"
                v-html="link.label"
                :class="{
                    'px-4 py-2 text-xs mx-1 rounded-md inline transition-colors duration-300 transform bg-blue-700 text-white': link.active,
                    'px-4 py-2 text-xs mx-1 text-blue-700 bg-white rounded-md sm:inline hover:bg-blue-700 hover:text-blue-50': !link.active
                }"
            />
            <span v-else class="px-4 py-2 text-xs mx-1 text-gray-500 capitalize rounded-md cursor-not-allowed" v-html="link.label"></span>
        </template>
    </div>
</template>