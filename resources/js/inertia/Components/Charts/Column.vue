<script setup>
import { Chart } from 'highcharts-vue';
const props = defineProps({
  data: {
    type: Array,
    required: true,
  },
  seriesName: String,
  title: String,
  yAxisTitle: String,
  dataLabelsFormat: String,
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
    type: 'column',
  },
  title: {
    align: 'center',
    text: props.title,
    fontSize: '40',
  },
  xAxis: {
    type: 'category',
  },
  yAxis: {
    title: {
      text: props.yAxisTitle,
    },
  },
  legend: {
    enabled: false,
  },
  plotOptions: {
    series: {
      borderWidth: 0,
      dataLabels: {
        enabled: true,
        format: props.dataLabelsFormat ?? '{point.y:.2f}%',
      },
    },
  },
  tooltip: {
    headerFormat: '<span style="font-size:11px">{series.name}</span><br>',
    pointFormat: `<span style="color:{point.color}">{point.name}</span>: <b>${props.dataLabelsFormat ?? '{point.y:.2f}%'}</b>`,
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
