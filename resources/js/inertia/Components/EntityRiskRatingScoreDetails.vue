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
  permissions: Object,
});
const modals = reactive({
  riskRatingScoreModal: false,
  risk_score_override: 'N/A',
  editOpen: false,
});
const scoreBreakdown = ref(false);
const riskRatingOptions = [
  {
    value: 'high',
    label: 'High',
  },
  {
    value: 'medium',
    label: 'Medium',
  },
  {
    value: 'low',
    label: 'Low',
  },
];
const riskRatingScore = quote => {
  if (quote.risk_score != null) {
    modals.riskRatingScoreModal = true;
    let url = `/quotes/business/risk-rating-details/${quote.uuid}`;
    axios
      .get(url)
      .then(res => {
        scoreBreakdown.value = res.data.score_list;
      })
      .catch(err => {
        console.log(err);
      });
  }
};
const quoteDetail = () => {
  let url = `/business/quote-detail/${props.quote.uuid}`;
  axios
    .get(url)
    .then(res => {
      formModal.risk_override = res.data.risk_score_override;
      modals.risk_score_override = res.data.risk_score_override;
    })
    .catch(err => {
      console.log(err);
    });
};
let formValues = {
  risk_override: '',
  quote_uuid: props.quote.uuid,
};
const formModal = reactive(formValues);
const can = permission => useCan(permission);
function toggleEdit() {
  modals.editOpen = !modals.editOpen;
}
const contentLoader = ref(false);
function saveRisk() {
  contentLoader.value = true;
  let url = `/business/update-risk`;
  axios
    .post(url, formModal)
    .then(res => {
      modals.editOpen = false;
      modals.risk_score_override = formModal.risk_override;
      contentLoader.value = false;
      router.reload({
        replace: true,
        preserveScroll: true,
        preserveState: true,
      });
    })
    .catch(err => {
      console.log(err);
    });
}
quoteDetail();
</script>
<template>
  <div class="grid sm:grid-cols-2">
    <dt class="font-medium">Risk Category</dt>

    <dd @click.prevent="riskRatingScore(quote)">
      {{
        quote.risk_score == null && modals.risk_score_override == null
          ? 'Pending for review'
          : modals.risk_score_override
            ? modals.risk_score_override
            : quote.risk_score <= 25
              ? 'Low Risk'
              : quote.risk_score <= 34
                ? 'Medium Risk'
                : quote.risk_score >= 35
                  ? 'High Risk'
                  : 'N/A'
      }}
    </dd>

    <x-modal
      v-model="modals.riskRatingScoreModal"
      show-close
      backdrop
      size="lg"
      title="Risk Rating - Score"
    >
      <div class="space-x-2">
        <table class="x-table w-full relative table-bordered">
          <tbody class="vue3-easy-data-table__body border-inner">
            <tr>
              <th class="w-50 z-10 text-left p-0">Risk Category</th>
              <th class="w-50 z-10 text-left p-0">Risk Type</th>
              <th class="text-left w-30 p-0 capitalize">Risk Items</th>
              <th class="text-left w-20 p-0">Risk Score</th>
            </tr>
            <tr v-for="(scoreList, index) in scoreBreakdown">
              <th v-if="index == 0" class="w-50 z-10 text-left" rowspan="8">
                Customer Risk
              </th>
              <th v-if="index == 8" class="w-50 z-10 text-left" rowspan="5">
                Geographic Risk
              </th>
              <th v-if="index == 13" class="w-50 z-10 text-left">
                Product Risk
              </th>
              <th v-if="index == 14" class="w-50 z-10 text-left" rowspan="4">
                Transaction Risk
              </th>
              <th v-if="index == 18" class="w-50 z-10 text-left" rowspan="3">
                Delivery Channel & Payment Risk
              </th>
              <td class="w-50 z-10 text-left p-0">{{ scoreList.text }}</td>
              <td class="text-left w-30 p-0 capitalize">
                {{ scoreList.value }}
              </td>
              <td class="text-left w-20 p-0">{{ scoreList.score }}</td>
            </tr>
            <tr>
              <td class="text-center" colspan="3">
                <strong>Total Score</strong>
              </td>
              <td class="text-left w-20 p-0">{{ quote.risk_score }}</td>
            </tr>
            <tr>
              <td class="text-center" colspan="3">
                <strong>Inherent Risk Level </strong>
              </td>
              <td class="text-left w-20 p-0 capitalize">
                {{
                  quote.risk_score <= 25
                    ? 'Low Risk'
                    : quote.risk_score <= 34
                      ? 'Medium Risk'
                      : quote.risk_score >= 35
                        ? 'High Risk'
                        : 'N/A'
                }}
              </td>
            </tr>
            <tr>
              <td class="text-center" colspan="3">
                <strong>Risk Override </strong>
              </td>
              <td class="capitalize flex" v-if="modals.editOpen == false">
                {{ modals.risk_score_override }}

                <span
                  v-if="can('customer-riskrrating-override')"
                  class="ml-2"
                  @click="toggleEdit()"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 50 50"
                    width="15px"
                    height="15px"
                  >
                    <path
                      d="M 43.125 2 C 41.878906 2 40.636719 2.488281 39.6875 3.4375 L 38.875 4.25 L 45.75 11.125 C 45.746094 11.128906 46.5625 10.3125 46.5625 10.3125 C 48.464844 8.410156 48.460938 5.335938 46.5625 3.4375 C 45.609375 2.488281 44.371094 2 43.125 2 Z M 37.34375 6.03125 C 37.117188 6.0625 36.90625 6.175781 36.75 6.34375 L 4.3125 38.8125 C 4.183594 38.929688 4.085938 39.082031 4.03125 39.25 L 2.03125 46.75 C 1.941406 47.09375 2.042969 47.457031 2.292969 47.707031 C 2.542969 47.957031 2.90625 48.058594 3.25 47.96875 L 10.75 45.96875 C 10.917969 45.914063 11.070313 45.816406 11.1875 45.6875 L 43.65625 13.25 C 44.054688 12.863281 44.058594 12.226563 43.671875 11.828125 C 43.285156 11.429688 42.648438 11.425781 42.25 11.8125 L 9.96875 44.09375 L 5.90625 40.03125 L 38.1875 7.75 C 38.488281 7.460938 38.578125 7.011719 38.410156 6.628906 C 38.242188 6.246094 37.855469 6.007813 37.4375 6.03125 C 37.40625 6.03125 37.375 6.03125 37.34375 6.03125 Z"
                    />
                  </svg>
                </span>
              </td>
              <td class="w-50" v-if="modals.editOpen == true">
                <x-select
                  name="product"
                  v-model="formModal.risk_override"
                  :options="riskRatingOptions"
                  placeholder="Select Risk"
                  required
                  size="xs"
                  class="w-full mr-2"
                />
                <div class="flex">
                  <x-button
                    size="xs"
                    color="#ff5e00"
                    class="mr-2"
                    @click="saveRisk()"
                    :loading="contentLoader"
                    >Save</x-button
                  >
                  <x-button size="xs" color="primary" @click="toggleEdit()">
                    Cancel
                  </x-button>
                </div>
              </td>
            </tr>

            <tr class="bg-black color-white">
              <td class="w-50 z-10 text-left">Risk Category</td>
              <td class="text-left w-30" colspan="3">Risk Rating Score</td>
            </tr>
            <tr class="color-white" style="background: rgb(47 229 25)">
              <td class="w-50 z-10 text-left">Low Risk</td>
              <td class="text-left w-20" colspan="3">1 to 25</td>
            </tr>
            <tr class="color-white" style="background: #ffff23; color: black">
              <td class="w-50 z-10 text-left">Medium Risk</td>
              <td class="text-left w-20" colspan="3">26 to 34</td>
            </tr>
            <tr class="color-white" style="background: red">
              <td class="w-50 z-10 text-left">High Risk</td>
              <td class="text-left w-20" colspan="3">35 and above</td>
            </tr>
          </tbody>
        </table>
      </div>
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
th {
  font-size: 13px;
  border-width: 1px;
  padding: 6px;
}
</style>
