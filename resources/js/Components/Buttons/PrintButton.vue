<script setup>

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
});

</script>

<template>
    <a :href="href" class="flex items-center justify-center text-white bg-rose-900 hover:bg-rose-500 font-medium rounded-lg text-sm  px-4 py-1 text-center me-2 transition duration-150 ease-in-out">
        <slot />
        <svg class="w-6 h-6" aria-hidden="true" fill="none" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15">
            <path fill="currentColor" d="M3.5 8H3V7h.5a.5.5 0 0 1 0 1M7 10V7h.5a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.5.5z"/>
            <path fill="currentColor" fill-rule="evenodd" d="M1 1.5A1.5 1.5 0 0 1 2.5 0h8.207L14 3.293V13.5a1.5 1.5 0 0 1-1.5 1.5h-10A1.5 1.5 0 0 1 1 13.5zM3.5 6H2v5h1V9h.5a1.5 1.5 0 1 0 0-3m4 0H6v5h1.5A1.5 1.5 0 0 0 9 9.5v-2A1.5 1.5 0 0 0 7.5 6m2.5 5V6h3v1h-2v1h1v1h-1v2z" clip-rule="evenodd"/>
        </svg>
    </a>
</template>
