<script setup>
const props = defineProps({
    links: {
        type: Array,
    }
});
</script>
<template>
    <div>
        <Component
            v-for="link in props.links"
            :is="link.url ? 'Link' : 'span'"
            :key="link.label"
            :href="link.url"
            v-html="link.label"
            class="px-1"
            :class="link.url ? '' : 'text-gray-500'"
        />
    </div>
</template>