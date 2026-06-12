<script setup>
import { useSavingsCalculator } from '@/inertia/Composables/useSavingsCalculator';
import { Chart } from 'highcharts-vue';
import { toPng } from 'html-to-image';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'calculate']);

// Use the composable
const { calculate, getYearlyProgression, formatNumber, formatNumberShort } =
  useSavingsCalculator();

// Calculator mode: 'invest' or 'goal'
const calculatorMode = ref('goal');

// Form data
const form = reactive({
  currency: 'AED',
  investmentFrequency: 'Monthly',
  investmentAmount: 5000,
  investmentDuration: 10,
  expectedRateOfReturn: 8,
});

// Currency options (USD, AED only)
const currencyOptions = [
  { value: 'AED', label: 'Ð' },
  { value: 'USD', label: '$' },
];

// Investment frequency options
const frequencyOptions = [
  { value: 'Single Payment', label: 'Single Payment' },
  { value: 'Monthly', label: 'Monthly' },
  { value: 'Quarterly', label: 'Quarterly' },
  { value: 'Half Yearly', label: 'Half Yearly' },
  { value: 'Yearly', label: 'Yearly' },
];

// Format number with commas for input
const formatWithCommas = value => {
  if (!value) return '';
  return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
};

// Parse number from formatted string
const parseFormattedNumber = value => {
  if (!value) return 0;
  return parseInt(value.toString().replace(/,/g, ''), 10) || 0;
};

// Get currency symbol
const currencySymbol = computed(() => {
  const currency = currencyOptions.find(c => c.value === form.currency);
  return currency ? currency.label : 'Ð';
});

// Calculate results using composable
const calculationResults = computed(() => {
  const result = calculate({
    mode: calculatorMode.value,
    amount: form.investmentAmount,
    rate: form.expectedRateOfReturn,
    years: form.investmentDuration,
    frequency: form.investmentFrequency,
  });

  // Add header text based on mode
  let headerText;
  if (calculatorMode.value === 'invest') {
    headerText = `Estimated returns of ${currencySymbol.value} ${formatNumber(result.futureValue)}`;
  } else {
    const freq = form.investmentFrequency;
    const label =
      freq === 'Single Payment' ? 'Single investment' : freq + ' investment';
    headerText = `${label} required of ${currencySymbol.value} ${formatNumber(result.requiredPayment || result.totalInvestment)}`;
  }

  return { ...result, headerText };
});

// Chart options for donut chart (Goal mode)
const donutChartOptions = computed(() => ({
  chart: {
    type: 'pie',
    backgroundColor: 'transparent',
    height: 280,
  },
  title: { text: '' },
  xAxis: { visible: false, categories: [] },
  yAxis: { visible: false },
  tooltip: { pointFormat: '<b>{point.percentage:.1f}%</b>' },
  accessibility: { point: { valueSuffix: '%' } },
  plotOptions: {
    pie: {
      innerSize: '60%',
      allowPointSelect: true,
      cursor: 'pointer',
      dataLabels: { enabled: false },
      showInLegend: false,
    },
  },
  series: [
    {
      name: 'Amount',
      colorByPoint: true,
      data: [
        {
          name: 'Total Investment',
          y: calculationResults.value.totalInvestment,
          color: '#0088CC',
        },
        {
          name: 'Wealth Gained',
          y: calculationResults.value.wealthGained,
          color: '#F5A623',
        },
      ],
    },
  ],
  credits: { enabled: false },
}));

// Yearly investment data for bar chart (using composable)
const yearlyInvestmentData = computed(() => {
  const progression = getYearlyProgression({
    amount: form.investmentAmount,
    rate: form.expectedRateOfReturn,
    years: form.investmentDuration,
    frequency: form.investmentFrequency,
  });

  return progression.map(d => ({
    name: `Year ${d.year}`,
    y: d.value,
    color: '#F5A623',
  }));
});

// Chart options for bar chart (Invest mode)
const barChartOptions = computed(() => ({
  chart: {
    type: 'column',
    backgroundColor: 'transparent',
    height: 350,
    marginLeft: 80,
    marginRight: 10,
    marginBottom: 80,
    spacingLeft: 0,
    spacingRight: 0,
    width: null, // Take full width from container
  },
  title: {
    text: '',
  },
  xAxis: {
    categories: yearlyInvestmentData.value.map((d, i) => i + 1),
    min: 0,
    max: yearlyInvestmentData.value.length - 1,
    title: {
      text: 'Investment Tenure',
      margin: 15,
      style: {
        fontSize: '12px',
        color: '#666',
        fontWeight: 'bold',
      },
    },
    labels: {
      style: {
        fontSize: '11px',
        color: '#666',
      },
    },
  },
  yAxis: {
    title: {
      text: 'Maturity Amount',
      style: {
        fontSize: '12px',
        color: '#666',
        fontWeight: 'bold',
      },
    },
    labels: {
      formatter: function () {
        return formatNumberShort(this.value);
      },
      style: {
        fontSize: '11px',
        color: '#666',
      },
    },
    gridLineColor: '#E5E5E5',
  },
  tooltip: {
    formatter: function () {
      return `<b>${this.x}</b><br/>${currencySymbol.value} ${formatNumber(this.y)}`;
    },
  },
  legend: {
    enabled: false,
  },
  plotOptions: {
    column: {
      borderRadius: 4,
      color: '#F5A623',
      pointPadding: 0.1,
      groupPadding: 0.05,
      borderWidth: 0,
      dataLabels: {
        enabled: true,
        formatter: function () {
          return formatNumberShort(this.y);
        },
        style: {
          fontSize: '10px',
          fontWeight: 'normal',
          color: '#666',
          textOutline: 'none',
        },
      },
    },
  },
  series: [
    {
      name: 'Value',
      data: yearlyInvestmentData.value,
    },
  ],
  credits: {
    enabled: false,
  },
}));

// Get current chart options based on mode
const chartOptions = computed(() => {
  return calculatorMode.value === 'invest'
    ? barChartOptions.value
    : donutChartOptions.value;
});

// Modal visibility
const modalVisible = computed({
  get: () => props.modelValue,
  set: val => emit('update:modelValue', val),
});

// Download calculator as image
const chartRef = ref(null);
const calculatorRef = ref(null);
const isDownloading = ref(false);

const downloadImage = async () => {
  if (!calculatorRef.value) return;

  isDownloading.value = true;

  try {
    const dataUrl = await toPng(calculatorRef.value, {
      quality: 1,
      pixelRatio: 2, // Higher quality
      backgroundColor: '#f9fafb', // Match bg-gray-50
      cacheBust: true,
    });

    // Download the image
    const link = document.createElement('a');
    link.download = `savings-calculator-${Date.now()}.png`;
    link.href = dataUrl;
    link.click();
  } catch (error) {
    console.error('Error capturing calculator:', error);
  } finally {
    isDownloading.value = false;
  }
};

// Switch calculator mode
const setMode = mode => {
  calculatorMode.value = mode;
};
</script>

<template>
  <x-modal
    v-model="modalVisible"
    size="xl"
    title="Savings Calculator"
    show-close
    backdrop
  >
    <div class="p-4 bg-gray-50">
      <div ref="calculatorRef" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left Card: Calculator Form -->
        <div class="bg-white rounded-xl shadow-md p-6">
          <!-- Mode Toggle Buttons -->
          <div class="flex gap-3 mb-6">
            <div class="flex-1">
              <x-button
                size="sm"
                :color="calculatorMode === 'invest' ? 'primary' : 'default'"
                :outlined="calculatorMode !== 'invest'"
                @click="setMode('invest')"
                class="w-full"
              >
                I Want to Invest
              </x-button>
            </div>
            <div class="flex-1">
              <x-button
                size="sm"
                :color="calculatorMode === 'goal' ? 'primary' : 'default'"
                :outlined="calculatorMode !== 'goal'"
                @click="setMode('goal')"
                class="w-full"
              >
                I Want to Reach a Goal
              </x-button>
            </div>
          </div>

          <!-- Form Fields -->
          <div class="space-y-5">
            <!-- Currency -->
            <div class="flex items-center">
              <label class="text-sm text-gray-600 w-40">Currency</label>
              <x-select
                v-model="form.currency"
                :options="currencyOptions"
                size="sm"
                class="w-24"
              />
            </div>

            <!-- Investment Frequency -->
            <div class="flex items-center">
              <label class="text-sm text-gray-600 w-40"
                >Invested Frequency</label
              >
              <x-select
                v-model="form.investmentFrequency"
                :options="frequencyOptions"
                size="sm"
                class="w-32"
              />
            </div>

            <!-- Investment Amount (shows for both modes with different labels) -->
            <div class="flex items-center">
              <label class="text-sm text-gray-600 w-40">{{
                calculatorMode === 'invest'
                  ? 'Investment Amount'
                  : 'Goal Amount'
              }}</label>
              <x-input
                :model-value="formatWithCommas(form.investmentAmount)"
                @update:model-value="
                  form.investmentAmount = parseFormattedNumber($event)
                "
                type="text"
                size="sm"
                class="w-40"
                placeholder="0"
              />
            </div>

            <!-- Investment Duration (Up to 100 years) -->
            <div class="flex items-center">
              <label class="text-sm text-gray-600 w-40"
                >Investment Duration</label
              >
              <div class="flex items-center gap-3 flex-1">
                <x-slider
                  v-model="form.investmentDuration"
                  :min="1"
                  :max="100"
                  color="primary"
                  class="w-32"
                />
                <x-input
                  v-model.number="form.investmentDuration"
                  type="number"
                  size="sm"
                  class="w-16"
                  :min="1"
                  :max="100"
                />
                <span class="text-sm text-gray-600">Years</span>
              </div>
            </div>

            <!-- Expected Rate of Return (Up to 50%) -->
            <div class="flex items-center">
              <label class="text-sm text-gray-600 w-40"
                >Expected Rate of Return</label
              >
              <div class="flex items-center gap-3 flex-1">
                <x-slider
                  v-model="form.expectedRateOfReturn"
                  :min="1"
                  :max="50"
                  :step="0.5"
                  color="primary"
                  class="w-32"
                />
                <x-input
                  v-model.number="form.expectedRateOfReturn"
                  type="number"
                  size="sm"
                  class="w-16"
                  :min="1"
                  :max="50"
                  :step="0.5"
                />
                <span class="text-sm text-gray-600">%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Card: Results -->
        <div class="bg-white rounded-xl shadow-md p-6">
          <!-- Result Header for Goal Mode -->
          <div
            v-if="calculatorMode === 'goal'"
            class="bg-teal-100 text-teal-800 text-center py-3 px-4 rounded-md mb-4"
          >
            <span class="font-medium">{{ calculationResults.headerText }}</span>
          </div>

          <!-- Result Headers for Invest Mode (Two boxes) -->
          <div v-else class="flex gap-3 mb-4">
            <div
              class="flex-1 bg-teal-100 text-teal-800 text-center py-3 px-4 rounded-md"
            >
              <span class="font-medium"
                >Amount Invested: {{ currencySymbol }}
                {{
                  formatNumberShort(calculationResults.totalInvestment)
                }}</span
              >
            </div>
            <div
              class="flex-1 bg-teal-100 text-teal-800 text-center py-3 px-4 rounded-md"
            >
              <span class="font-medium"
                >Estimated Return: {{ currencySymbol }}
                {{ formatNumberShort(calculationResults.futureValue) }}</span
              >
            </div>
          </div>

          <!-- Chart -->
          <div
            class="flex"
            :class="calculatorMode === 'invest' ? 'w-full' : 'justify-center'"
          >
            <div :class="calculatorMode === 'invest' ? 'w-full' : ''">
              <Chart
                :key="calculatorMode"
                ref="chartRef"
                :options="chartOptions"
                :class="
                  calculatorMode === 'invest' ? 'w-full h-96' : 'w-64 h-64'
                "
              />
            </div>
          </div>

          <!-- Legend for Goal Mode (Donut Chart) -->
          <div
            v-if="calculatorMode === 'goal'"
            class="flex flex-col items-end gap-2 mt-4"
          >
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-[#0088CC]"></span>
              <span class="text-sm text-gray-600">Total Investment</span>
              <span class="text-sm font-semibold text-gray-800"
                >{{ currencySymbol }}
                {{ formatNumber(calculationResults.totalInvestment) }}</span
              >
            </div>
            <div class="flex items-center gap-2">
              <span class="w-3 h-3 rounded-full bg-[#F5A623]"></span>
              <span class="text-sm text-gray-600">Wealth Gained</span>
              <span class="text-sm font-semibold text-gray-800"
                >{{ currencySymbol }}
                {{ formatNumber(calculationResults.wealthGained) }}</span
              >
            </div>
          </div>
        </div>
      </div>

      <!-- Download Button - Outside cards, bottom right -->
      <div class="flex justify-end mt-4">
        <x-button
          color="orange"
          size="sm"
          :loading="isDownloading"
          :disabled="isDownloading"
          @click="downloadImage"
        >
          {{ isDownloading ? 'Downloading...' : 'Download Image' }}
        </x-button>
      </div>
    </div>
  </x-modal>
</template>

<style scoped>
/* Custom range slider styling */
input[type='range']::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #0066cc;
  cursor: pointer;
}

input[type='range']::-moz-range-thumb {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #0066cc;
  cursor: pointer;
  border: none;
}
</style>
