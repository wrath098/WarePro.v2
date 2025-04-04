<script setup>
    import { Line } from "vue-chartjs";
    import { Chart, registerables } from "chart.js";

    Chart.register(...registerables);

    const props = defineProps({
        chartData: {
            type: Object,
            required: true,
        },
        chartOptions: {
            type: Object,
            default: () => ({
                responsive: true,
                plugins: {
                    legend: {
                        position: "top",
                    },
                },
            }),
        },
    });
</script>

<template>
    <div class="chart-container">
      <Line :chart-data="chartData" :chart-options="chartOptions" />
    </div>
</template>

<style scoped>
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
</style>