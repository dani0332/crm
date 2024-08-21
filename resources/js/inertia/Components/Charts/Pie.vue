<script setup>
import { Chart } from 'highcharts-vue';

const props = defineProps({
  title: String,
  seriesName: String,
  data: Array,
});

const chartRef = ref(null);

watch(
  () => props.data,
  () => {
    updateSeries();
  },
  { deep: true },
);

const updateSeries = () => {
  const chart = chartRef.value.chart;
  chart.showLoading();
  chart.update({
    series: [
      {
        data: props.data,
      },
    ],
  });
  chart.hideLoading();
};

const chartOptions = ref({
  chart: {
    plotBackgroundColor: null,
    plotBorderWidth: null,
    plotShadow: false,
    type: 'pie',
  },
  title: {
    text: props.title,
    align: 'center',
  },
  tooltip: {
    pointFormat: '{series.name}: <b>{point.percentage:.1f}%</b>',
  },
  accessibility: {
    point: {
      valueSuffix: '%',
    },
  },
  plotOptions: {
    pie: {
      allowPointSelect: true,
      cursor: 'pointer',
      dataLabels: {
        enabled: true,
        format: '{point.name} - <b>{y} Leads',
      },
    },
  },
  series: [
    {
      name: props.seriesName,
      colorByPoint: true,
      data: props.data,
    },
  ],
  credits: {
    enabled: false,
  },
});
</script>
<template>
  <Chart ref="chartRef" :options="chartOptions"></Chart>
</template>
