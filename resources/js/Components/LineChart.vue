<script setup>
    import { defineProps } from 'vue';
    import { Line } from 'vue-chartjs';
    import {
        Chart as ChartJS,
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
        Title,
        Tooltip,
        Legend
    } from 'chart.js';

    ChartJS.register(
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
        Title,
        Tooltip,
        Legend
    );
    
    const props = defineProps({
        data: {
            type: Object,
            required: true
        },
        options: {
            type: Object,
            required: true
        }
    });
</script>

<template>
    <div>
        <Line :data="data" :options="options" />
    </div>
</template>