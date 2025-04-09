<script setup>
    import { Line } from 'vue-chartjs';
    import {
        Chart as ChartJS,
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
        Title,
        Tooltip,
        Legend,
        Filler
    } from 'chart.js';

    ChartJS.register(
        CategoryScale,
        LinearScale,
        PointElement,
        LineElement,
        Title,
        Tooltip,
        Legend,
        Filler
    );
    
    const props = defineProps({
        data: {
            type: Object,
            required: true
        },
        height: {
            type: String,
            default: '400px'
        },
        options: {
            type: Object,
            required: true
        }
    });
</script>

<template>
    <div :style="{ height: height }">
        <Line :data="data" :options="options" />
    </div>
</template>