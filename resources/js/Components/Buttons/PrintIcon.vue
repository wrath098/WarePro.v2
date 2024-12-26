<script setup>

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
});

</script>

<template>
    <a :href="href" class="flex items-center font-bold hover:bg-indigo-600 rounded-full p-1 text-lg text-center me-2 mb-2 transition duration-150 ease-in-out">
        <slot />
        <svg class="w-7 h-7 text-indigo-900 hover:text-gray-50 transition duration-75" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
            <path stroke="currentColor" stroke-linejoin="round" stroke-width="2" d="M16.444 18H19a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.556M17 11V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v6h10ZM7 15h10v4a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-4Z"/>
        </svg>
    </a>
</template>
