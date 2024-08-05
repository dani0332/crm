<script setup>
const props = defineProps({
  modelType: {
    type: String,
    default: '',
  },
  quote: {
    type: Object,
    default: {},
  },
});
const modals = reactive({
  riskRatingScoreModal: false,
});
const scoreBreakdown = ref(false);
const riskRatingScore = quote => {
  console.log('quote.risk_score', quote.risk_score);
  if (quote?.risk_score != null) {
    modals.riskRatingScoreModal = true;
    let url = `/quotes/${props.modelType.toLowerCase()}/risk-rating-details/${quote.uuid}`;
    axios
      .get(url)
      .then(res => {
        scoreBreakdown.value = res.data;
      })
      .catch(err => {
        console.log(err);
      });
  }
};
</script>
<template>
  <div class="grid sm:grid-cols-2">
    <dt class="font-medium uppercase">Risk Category</dt>

    <dd @click.prevent="riskRatingScore(quote)">
      {{
        quote?.risk_score == null
          ? 'N/A'
          : quote?.risk_score <= 16
            ? 'Low Risk'
            : quote?.risk_score <= 31
              ? 'Medium Risk'
              : quote?.risk_score >= 32
                ? 'High Risk'
                : 'N/A'
      }}
    </dd>
    <x-modal
      v-model="modals.riskRatingScoreModal"
      title="Risk Rating - Score"
      show-close
      backdrop
      size="md"
    >
      <template #actions>
        <div class="space-x-2">
          <table class="x-table w-full relative table-bordered">
            <tbody class="vue3-easy-data-table__body border-inner">
              <tr v-for="scoreList in scoreBreakdown">
                <td class="w-50 z-10 text-left p-0">{{ scoreList.text }}</td>
                <td class="text-left w-30 p-0 capitalize">
                  {{ scoreList.value }}
                </td>
                <td class="text-left w-20 p-0">{{ scoreList.score }}</td>
              </tr>
              <tr>
                <td class="text-center" colspan="2">
                  <strong>Total Score</strong>
                </td>
                <td class="text-left w-20 p-0">{{ quote.risk_score }}</td>
              </tr>

              <tr class="bg-black color-white">
                <td class="w-50 z-10 text-left">Risk Category</td>
                <td class="text-left w-30" colspan="2">Risk Rating Score</td>
              </tr>
              <tr class="color-white" style="background: #28583b">
                <td class="w-50 z-10 text-left">Low Risk</td>
                <td class="text-left w-20" colspan="2">16 and below</td>
              </tr>
              <tr class="color-white" style="background: #65422a">
                <td class="w-50 z-10 text-left">Medium Risk</td>
                <td class="text-left w-20" colspan="2">17 - 31</td>
              </tr>
              <tr class="color-white" style="background: #643939">
                <td class="w-50 z-10 text-left">High Risk</td>
                <td class="text-left w-20" colspan="2">32</td>
              </tr>
            </tbody>
          </table>
        </div>
      </template>
    </x-modal>
  </div>
</template>
<style>
.bg-slate-50.p-4 {
  overflow: auto !important;
}
.color-white {
  color: #fff;
}
</style>
