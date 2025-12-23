<script setup>
import { Chart } from 'highcharts-vue';
import { toPng } from 'html-to-image';

const props = defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:modelValue', 'calculate']);

// Calculator mode: 'invest' or 'goal'
const calculatorMode = ref('goal');

// Form data
const form = reactive({
  currency: 'AED',
  investmentFrequency: 'Monthly',
  // For "I Want to Invest" mode
  investmentAmount: 5000,
  // For "I Want to Reach a Goal" mode
  goalAmount: 1000000,
  // Common fields
  investmentDuration: 10,
  withdrawalYears: 10,
  expectedRateOfReturn: 8,
});

// Currency options
const currencyOptions = [
  { value: 'AED', label: 'د.إ' },
  { value: 'USD', label: '$' },
  { value: 'EUR', label: '€' },
  { value: 'GBP', label: '£' },
];

// Investment frequency options
const frequencyOptions = [
  { value: 'Monthly', label: 'Monthly' },
  { value: 'Quarterly', label: 'Quarterly' },
  { value: 'Semi-Annual', label: 'Semi-Annual' },
  { value: 'Annual', label: 'Annual' },
];

// Get currency symbol
const currencySymbol = computed(() => {
  const currency = currencyOptions.find(c => c.value === form.currency);
  return currency ? currency.label : 'د.إ';
});

// Calculate results based on mode
const calculationResults = computed(() => {
  const rate = form.expectedRateOfReturn / 100;
  const monthlyRate = rate / 12;
  const months = form.investmentDuration * 12;

  if (calculatorMode.value === 'invest') {
    // Calculate future value from investment amount
    let periodicAmount = form.investmentAmount;
    let periodsPerYear = 12;

    if (form.investmentFrequency === 'Quarterly') {
      periodsPerYear = 4;
    } else if (form.investmentFrequency === 'Semi-Annual') {
      periodsPerYear = 2;
    } else if (form.investmentFrequency === 'Annual') {
      periodsPerYear = 1;
    }

    const totalPeriods = form.investmentDuration * periodsPerYear;
    const periodicRate = rate / periodsPerYear;

    // Future Value of Annuity formula: FV = P * [((1 + r)^n - 1) / r]
    const futureValue =
      periodicAmount *
      ((Math.pow(1 + periodicRate, totalPeriods) - 1) / periodicRate);
    const totalInvestment = periodicAmount * totalPeriods;
    const wealthGained = futureValue - totalInvestment;

    return {
      headerText: `Estimated returns of ${currencySymbol.value} ${formatNumber(Math.round(futureValue))}`,
      totalInvestment: Math.round(totalInvestment),
      wealthGained: Math.round(wealthGained),
      futureValue: Math.round(futureValue),
    };
  } else {
    // Calculate required investment from goal amount
    let periodsPerYear = 12;

    if (form.investmentFrequency === 'Quarterly') {
      periodsPerYear = 4;
    } else if (form.investmentFrequency === 'Semi-Annual') {
      periodsPerYear = 2;
    } else if (form.investmentFrequency === 'Annual') {
      periodsPerYear = 1;
    }

    const totalPeriods = form.investmentDuration * periodsPerYear;
    const periodicRate = rate / periodsPerYear;

    // PMT formula: PMT = FV * [r / ((1 + r)^n - 1)]
    const requiredPayment =
      form.goalAmount *
      (periodicRate / (Math.pow(1 + periodicRate, totalPeriods) - 1));
    const totalInvestment = requiredPayment * totalPeriods;
    const wealthGained = form.goalAmount - totalInvestment;

    const frequencyLabel =
      form.investmentFrequency === 'Monthly'
        ? 'Monthly'
        : form.investmentFrequency === 'Quarterly'
          ? 'Quarterly'
          : form.investmentFrequency === 'Semi-Annual'
            ? 'Semi-annual'
            : 'Annual';

    return {
      headerText: `${frequencyLabel} investment required of ${currencySymbol.value} ${formatNumber(Math.round(requiredPayment))}`,
      totalInvestment: Math.round(totalInvestment),
      wealthGained: Math.round(wealthGained),
      futureValue: form.goalAmount,
    };
  }
});

// Format number with commas
const formatNumber = num => {
  return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
};

// Chart options for donut chart (Goal mode)
const donutChartOptions = computed(() => ({
  chart: {
    type: 'pie',
    backgroundColor: 'transparent',
    height: 280,
  },
  title: {
    text: '',
  },
  xAxis: {
    visible: false,
    categories: [],
  },
  yAxis: {
    visible: false,
  },
  tooltip: {
    pointFormat: '<b>{point.percentage:.1f}%</b>',
  },
  accessibility: {
    point: {
      valueSuffix: '%',
    },
  },
  plotOptions: {
    pie: {
      innerSize: '60%',
      allowPointSelect: true,
      cursor: 'pointer',
      dataLabels: {
        enabled: false,
      },
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
  credits: {
    enabled: false,
  },
}));

// Format number with K/M suffix
const formatNumberShort = num => {
  if (num >= 1000000) {
    return (num / 1000000).toFixed(num % 1000000 === 0 ? 0 : 2) + 'M';
  }
  if (num >= 1000) {
    return Math.round(num / 1000) + 'k';
  }
  return num.toString();
};

// Calculate yearly investment values for bar chart
const yearlyInvestmentData = computed(() => {
  const rate = form.expectedRateOfReturn / 100;
  const years = form.investmentDuration;
  let periodsPerYear = 12;

  if (form.investmentFrequency === 'Quarterly') {
    periodsPerYear = 4;
  } else if (form.investmentFrequency === 'Semi-Annual') {
    periodsPerYear = 2;
  } else if (form.investmentFrequency === 'Annual') {
    periodsPerYear = 1;
  }

  const periodicRate = rate / periodsPerYear;
  const periodicAmount = form.investmentAmount;
  const data = [];

  for (let year = 1; year <= years; year++) {
    const totalPeriods = year * periodsPerYear;
    // Future Value of Annuity formula
    const futureValue =
      periodicAmount *
      ((Math.pow(1 + periodicRate, totalPeriods) - 1) / periodicRate);
    data.push({
      name: `Year ${year}`,
      y: Math.round(futureValue),
      color: '#F5A623',
    });
  }

  return data;
});

// Chart options for bar chart (Invest mode)
const barChartOptions = computed(() => ({
  chart: {
    type: 'column',
    backgroundColor: 'transparent',
    height: 300,
    marginLeft: 50,
    marginRight: 10,
    spacingLeft: 0,
    spacingRight: 0,
    width: null, // Take full width from container
  },
  title: {
    text: '',
  },
  xAxis: {
    categories: yearlyInvestmentData.value.map(d => d.name),
    min: 0,
    max: yearlyInvestmentData.value.length - 1,
    labels: {
      style: {
        fontSize: '11px',
        color: '#666',
      },
    },
  },
  yAxis: {
    title: {
      text: '',
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
            <button
              type="button"
              :class="[
                'px-6 py-2.5 rounded-md text-sm font-medium transition-all duration-200',
                calculatorMode === 'invest'
                  ? 'bg-primary-600 text-white shadow-md'
                  : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
              ]"
              @click="setMode('invest')"
            >
              I Want to Invest
            </button>
            <button
              type="button"
              :class="[
                'px-6 py-2.5 rounded-md text-sm font-medium transition-all duration-200',
                calculatorMode === 'goal'
                  ? 'bg-primary-600 text-white shadow-md'
                  : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50',
              ]"
              @click="setMode('goal')"
            >
              I Want to Reach a Goal
            </button>
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

            <!-- Investment Amount (for "I Want to Invest" mode) -->
            <div v-if="calculatorMode === 'invest'" class="flex items-center">
              <label class="text-sm text-gray-600 w-40"
                >Investment Amount</label
              >
              <x-input
                v-model.number="form.investmentAmount"
                type="number"
                size="sm"
                class="w-40"
              />
            </div>

            <!-- Goal Amount (for "I Want to Reach a Goal" mode) -->
            <div v-if="calculatorMode === 'goal'" class="flex items-center">
              <label class="text-sm text-gray-600 w-40">Goal Amount</label>
              <x-input
                v-model.number="form.goalAmount"
                type="number"
                size="sm"
                class="w-40"
              />
            </div>

            <!-- Investment Duration -->
            <div class="flex items-center">
              <label class="text-sm text-gray-600 w-40"
                >Investment Duration</label
              >
              <div class="flex items-center gap-3 flex-1">
                <input
                  v-model.number="form.investmentDuration"
                  type="range"
                  min="1"
                  max="40"
                  class="w-32 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary-600"
                />
                <x-input
                  v-model.number="form.investmentDuration"
                  type="number"
                  size="sm"
                  class="w-16"
                  min="1"
                  max="40"
                />
                <span class="text-sm text-gray-600">Years</span>
              </div>
            </div>

            <!-- Withdrawal -->
            <div class="flex items-center">
              <label class="text-sm text-gray-600 w-40">Withdrawal</label>
              <div class="flex items-center gap-3 flex-1">
                <input
                  v-model.number="form.withdrawalYears"
                  type="range"
                  min="1"
                  max="40"
                  class="w-32 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary-600"
                />
                <x-input
                  v-model.number="form.withdrawalYears"
                  type="number"
                  size="sm"
                  class="w-16"
                  min="1"
                  max="40"
                />
                <span class="text-sm text-gray-600">years</span>
              </div>
            </div>

            <!-- Expected Rate of Return -->
            <div class="flex items-center">
              <label class="text-sm text-gray-600 w-40"
                >Expected Rate of Return</label
              >
              <div class="flex items-center gap-3 flex-1">
                <input
                  v-model.number="form.expectedRateOfReturn"
                  type="range"
                  min="1"
                  max="20"
                  step="0.5"
                  class="w-32 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer accent-primary-600"
                />
                <x-input
                  v-model.number="form.expectedRateOfReturn"
                  type="number"
                  size="sm"
                  class="w-16"
                  min="1"
                  max="20"
                  step="0.5"
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
                  calculatorMode === 'invest' ? 'w-full h-72' : 'w-64 h-64'
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
