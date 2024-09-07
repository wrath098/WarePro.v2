<script setup>
    import { computed } from 'vue';
    import { Link } from '@inertiajs/vue3';

    const props = defineProps({
        href: {
            type: String,
            required : true,
        },
        active: {
            type:Boolean
        }
    });
    const classes = computed(() =>
        props.active
            ? 'flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:text-gray-50 hover:bg-indigo-900'
            : 'flex items-center p-2 pl-11 w-full text-base font-normal text-gray-900 rounded-lg transition duration-75 group hover:text-gray-50 hover:bg-indigo-900'
    );
</script>
<template>
    <Link :href="props.href" :class="classes">
        <slot />
    </Link>
</template>
