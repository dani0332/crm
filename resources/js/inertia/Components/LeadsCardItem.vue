<script setup>
import { router } from '@inertiajs/vue3';
import { useSortable } from '@vueuse/integrations/useSortable';
import axios from 'axios';

const page = usePage();

const props = defineProps({
  leads: {
    type: Array,
    require: true,
  },
  id: Number,
  title: String,
});

const emit = defineEmits(['UpdateLeadsCount']);
const loader = ref(false);

const quoteStatusEnum = inject('quoteStatusEnum');
const quoteTypeId = inject('quoteTypeId');
const lostReasons = inject('lostReasons');
const quoteType = inject('quoteType');

const { isRequired } = useRules();

const daysSinceStale = date => useDaysSinceStale(date);

const leads = computed(() => {
  return props.leads;
});

const leadForm = useForm({
  lostreason: null,
});

const canDrag = computed(() => {
  return props.id == quoteStatusEnum?.Lost ||
    props.id == quoteStatusEnum?.TransactionApproved ||
    props.id == quoteStatusEnum?.PolicyIssued
    ? false
    : true;
});

const notification = useToast();

const updateList = async data => {
  try {
    let response = await axios.post(route('update-lead-status-drag-drop'), {
      data,
    });
    emit('UpdateLeadsCount', data);
    notification.success({
      title:
        typeof response.data.message != 'string'
          ? response.data.message[0]
          : response.data.message,
      position: 'top',
    });
    router.reload();
    return true;
  } catch ({ response }) {
    if (response) {
      notification.error({
        title: response.data.message,
        position: 'top',
      });
    }
    router.reload();
    return false;
  }
};

let resolveConfirm;

const moveTask = async () => {
  // Show the confirmation modal
  showModal.value = true;

  // Wait for the confirmation result
  const confirmed = await new Promise(resolve => {
    resolveConfirm = resolve;
    loader.value = false;
  });

  return confirmed;
};

useSortable(`#${props.title}`, leads.value, {
  group: {
    name: 'shared',
    put: true,
    pull: canDrag.value,
  },
  animation: 500,
  onAdd: async function (e) {
    let data = {
      form: {
        id: e.item.getAttribute('id') ?? e.from.children[e.oldIndex].id,
        quoteTypeId: quoteTypeId,
        quote_status_id: e.from.getAttribute('quote_status_id'),
      },
      to: { quote_status_id: e.to.getAttribute('quote_status_id') },
    };

    // Todo: Need to update with Enum
    if (
      data &&
      data.to.quote_status_id == quoteStatusEnum?.Lost &&
      quoteTypeId == '3'
    ) {
      let response = await moveTask(e);
      if (!response) {
        moveElemToOriginalList(e);
        showModal.value = false;
        return;
      } else {
        data.to['lost_reason'] = leadForm.lostreason;
      }
    }
    let listResponse = await updateList(data);

    if (!listResponse) moveElemToOriginalList(e);
    showModal.value = false;
  },
});

const moveElemToOriginalList = e => {
  var itemEl = e.item; // dragged HTMLElement
  let originalList = e.from; // previous list
  var newIndex = e.oldIndex;

  var referenceNode = originalList.children[newIndex];

  // Insert the dragged element back to its original position
  originalList.insertBefore(itemEl, referenceNode);
};

const lostReasonsOptions = computed(() => {
  return lostReasons.map(reason => ({
    value: reason.id,
    label: reason.text,
  }));
});

const showModal = ref(false);

const onSubmit = isValid => {
  if (!isValid) return false;
  loader.value = true;
  handleConfirmation(true);
};

const handleConfirmation = result => {
  resolveConfirm(result);
};

const getUrl = (url, quoteTypeId) => useGetShowPageRoute(url, quoteTypeId);
</script>
<template>
  <div
    :id="title"
    :quote_status_id="id"
    class="shared"
    :class="{ 'h-full': leads.length == 0 }"
  >
    <a
      v-for="{
        id,
        uuid,
        first_name,
        last_name,
        premium,
        updated_at,
        company_name,
        leadName,
        price_with_vat,
        health_cover_for,
        business_type_of_insurance,
        stale_at,
      } in leads"
      :key="id"
      :href="getUrl(uuid, quoteTypeId)"
      target="_blank"
      :id="id"
      class="block p-3 mt-2 border space-y-2 hover:transition hover:border-primary-500 rounded"
      :class="[
        daysSinceStale(stale_at) === false
          ? 'bg-white border-gray-300'
          : 'bg-error-50 border-error-500',
        { 'cursor-not-allowed': !canDrag },
      ]"
    >
      <div class="flex flex-col">
        <stale-leads-badge :date="stale_at" :position="'bottom'" />
        <span class="font-semibold text-sm">
          {{ first_name }} {{ last_name }}
        </span>
      </div>

      <div
        v-if="quoteTypeId == 3 || quoteTypeId == 5"
        class="flex items-center gap-2"
      >
        <x-tooltip align="left">
          <x-icon icon="person" size="sm" class="text-primary-400" />
          <template #tooltip>
            <div class="max-w-[194px] text-xs">
              This indicates the specific type of insurance coverage.
            </div>
          </template>
        </x-tooltip>
        <p class="text-xs">
          {{
            quoteTypeId == 3
              ? health_cover_for?.text
              : business_type_of_insurance?.text
          }}
        </p>
      </div>

      <div v-if="company_name" class="flex items-center gap-2">
        <x-icon icon="company" size="sm" class="text-primary-400" />
        <p class="text-xs">{{ company_name }}</p>
      </div>

      <div class="flex items-center gap-2">
        <x-tooltip align="left">
          <x-icon icon="money" size="sm" class="text-primary-400" />
          <template #tooltip>
            <div class="max-w-[194px] text-xs">
              <span
                v-if="
                  (quoteType == 'Health' && title === 'Quoted') ||
                  (quoteType == 'Health' && title === 'FollowedUp')
                "
              >
                'Price Starting from' represents the lowest premium amount that
                a client can pay to initiate insurance coverage, giving you an
                overview of the potential business to close.
              </span>
              <span v-else>
                The complete amount due including VAT and before any potential
                discounts. Remember, VAT is exempt for Life Insurance policies.
              </span>
            </div>
          </template>
        </x-tooltip>
        <p class="text-xs">
          {{
            quoteType == 'Health' || quoteType == 'Travel'
              ? Number(premium).toLocaleString()
              : Number(price_with_vat).toLocaleString()
          }}
        </p>
      </div>

      <div class="flex items-center gap-2">
        <x-tooltip align="left">
          <x-icon icon="calendar" size="sm" class="text-primary-400" />
          <template #tooltip>
            <div class="max-w-[194px] text-xs">
              The 'Last Modified Date' displays the most recent date and time
              when the lead was last worked on.
            </div>
          </template>
        </x-tooltip>
        <p class="text-xs">{{ updated_at }}</p>
      </div>
    </a>
  </div>

  <x-modal
    v-model="showModal"
    showClose
    backdrop
    @update:modelValue="handleConfirmation(false)"
  >
    <template #header>
      <span>Kinldy choose a reason for marking as 'Lost' </span>
    </template>

    <x-form @submit="onSubmit" :auto-focus="false">
      <x-field label="Lost Reason" required>
        <x-select
          v-model="leadForm.lostreason"
          :options="lostReasonsOptions"
          placeholder="Lost Reason is required"
          class="w-full"
          :rules="[isRequired]"
        />
      </x-field>
      <div class="text-right space-x-4 mt-4">
        <x-button type="submit" :loading="loader">Continue</x-button>
        <x-button color="orange" @click.prevent="handleConfirmation(false)">
          Go Back
        </x-button>
      </div>
    </x-form>
  </x-modal>
</template>
