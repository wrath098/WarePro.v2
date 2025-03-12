<script setup>
const props = defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <a :href="href" class="flex items-center justify-center text-white bg-gradient-to-r from-indigo-400 via-indigo-600 to-indigo-800 hover:bg-gradient-to-br font-medium rounded-lg text-sm text-center px-4 py-1">
        <svg class="w-6 h-6 mr-2" fill="none" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 32 32">
            <path fill="currentColor" d="M16 7L6 17l1.41 1.41L15 10.83V28H2v2h13a2 2 0 0 0 2-2V10.83l7.59 7.58L26 17Z"/>
            <path fill="currentColor" d="M6 8V4h20v4h2V4a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v4Z"/>
        </svg>
        <slot />
    </a>
</template>