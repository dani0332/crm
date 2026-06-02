<script setup>
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
  leads: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
  pendingRejectionsCount: {
    type: Number,
    default: 0,
  },
});

const page = usePage();

const quoteTypes = computed(() =>
  (page.props.quoteTypes ?? []).map(qt => ({ value: qt.id, label: qt.name })),
);

const eaStatusOptions = [
  { value: 'pending', label: 'Pending' },
  { value: 'approved', label: 'Approved' },
  { value: 'rejected', label: 'Rejected' },
];

const eaActionOptions = [
  { value: 'approve', label: 'Approve' },
  { value: 'reject', label: 'Reject' },
];

const eaModelOptions = [
  { value: 'referral', label: 'Referral' },
  { value: 'collaborate', label: 'Collaborate' },
];

const statusLabel = id => statusOptions.value.find(s => s.value === id)?.label ?? id ?? '—';

const tableHeaders = [
  { text: 'REF ID', value: 'code' },
  { text: 'EA ADVISOR', value: 'expert_advisor' },
  { text: 'LOB', value: 'quote_type' },
  { text: 'MODEL', value: 'ea_model' },
  { text: 'STATUS', value: 'quote_status_id' },
  { text: 'ACTION', value: 'action' },
];

const loaders = reactive({ table: false });

const rowKey = lead => `${lead.quote_type}-${lead.id}`;

const buildRowState = leads =>
  Object.fromEntries(
    leads.map(lead => [
      rowKey(lead),
      {
        model: lead.has_rejection && lead.ea_model === 'collaborate' ? null : lead.ea_model,
        action: null,
        loading: false,
        error: null,
      },
    ]),
  );

// Initialize synchronously so the template never reads undefined
const rowState = reactive(buildRowState(props.leads));

watch(
  () => props.leads,
  leads => Object.assign(rowState, buildRowState(leads)),
  { deep: true },
);

const filterForm = reactive({
  ref_id: props.filters.ref_id ?? '',
  lob: props.filters.lob ?? null,
  ea_model: props.filters.ea_model ?? null,
  status: props.filters.status ?? null,
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
});

const onSubmit = isValid => {
  if (!isValid) return;
  router.get(route('ea-manager.index'), filterForm, {
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onFinish: () => (loaders.table = false),
  });
};

const onReset = () => {
  Object.assign(filterForm, {
    ref_id: '',
    lob: null,
    ea_model: null,
    status: null,
    date_from: '',
    date_to: '',
  });
  router.get(route('ea-manager.index'), {}, {
    preserveState: true,
    preserveScroll: true,
    onBefore: () => (loaders.table = true),
    onFinish: () => (loaders.table = false),
  });
};

const exportLeads = () => {
  const params = new URLSearchParams(
    Object.fromEntries(Object.entries(filterForm).filter(([, v]) => v !== null && v !== '')),
  );
  window.location.href =
    route('ea-manager.export') + (params.toString() ? '?' + params.toString() : '');
};

const updateRow = async lead => {
  const key = rowKey(lead);
  const state = rowState[key];

  if (!state.action && !(lead.has_rejection && lead.ea_model === 'collaborate' && state.model)) {
    state.error = 'Please select an action.';
    return;
  }

  state.loading = true;
  state.error = null;

  try {
    const params = { quoteType: lead.quote_type, quoteId: lead.id };

    // EA approve/reject decision
    if (state.action) {
      await axios.post(route('ea-manager.decision', params), { action: state.action });
    }

    // Handle model change for rejected collaborative leads
    if (lead.has_rejection && lead.ea_model === 'collaborate' && state.model) {
      if (state.model === 'referral') {
        await axios.patch(route('ea-manager.change-model', params), { ea_model: 'referral' });
      } else if (state.model === 'collaborate') {
        await axios.post(route('ea-manager.decision', params), { action: 'approve' });
      }
    }

    router.reload({ only: ['leads', 'pendingRejectionsCount'] });
  } catch (err) {
    state.error = err?.response?.data?.message ?? 'Update failed.';
  } finally {
    state.loading = false;
  }
};
</script>

<template>
  <Head title="EA Manager" />

  <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
    EA Manager
    <x-badge v-if="pendingRejectionsCount > 0" color="red" size="sm" class="ml-2">
      {{ pendingRejectionsCount }} Pending Rejection{{ pendingRejectionsCount !== 1 ? 's' : '' }}
    </x-badge>
  </h1>

  <x-divider class="my-4" />

  <x-form @submit="onSubmit" :auto-focus="false">
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
      <x-input
        v-model="filterForm.ref_id"
        label="Ref ID"
        placeholder="Search Ref ID"
      />

      <x-select
        v-model="filterForm.lob"
        label="Line of Business"
        placeholder="Select Line of Business"
        :options="quoteTypes"
      />

      <x-input
        v-model="filterForm.date_from"
        label="Date From"
        type="date"
      />

      <x-input
        v-model="filterForm.date_to"
        label="Date To"
        type="date"
      />
    </div>

    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4 pt-2">
      <x-select
        v-model="filterForm.status"
        label="EA Status"
        placeholder="Filter by EA Status"
        :options="eaStatusOptions"
      />

      <x-select
        v-model="filterForm.ea_model"
        label="EA Model"
        placeholder="All Models"
        :options="eaModelOptions"
      />
    </div>

    <div class="flex gap-3 justify-end">
      <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
      <x-button size="sm" color="primary" @click.prevent="onReset">Reset</x-button>
      <x-button size="sm" color="secondary" @click.prevent="exportLeads">Export</x-button>
    </div>
  </x-form>

  <DataTable
    class="mt-4"
    :loading="loaders.table"
    :headers="tableHeaders"
    :items="leads"
    border-cell
    :empty-message="'No EA leads found.'"
    hide-footer
  >
    <template #item-code="item">
      <span class="font-medium text-blue-600">{{ item.code }}</span>
    </template>

    <template #item-expert_advisor="item">
      {{ item.expert_advisor?.name ?? '—' }}
    </template>

    <template #item-quote_type="item">
      <span class="capitalize">{{ item.quote_type }}</span>
    </template>

    <!-- Model: blank dropdown for rejected collaborative leads, badge otherwise -->
    <template #item-ea_model="item">
      <x-select
        v-if="item.has_rejection && item.ea_model === 'collaborate'"
        v-model="rowState[rowKey(item)].model"
        placeholder="Select model"
        :options="eaModelOptions"
        size="sm"
        class="w-full"
      />
      <span v-else class="capitalize">{{ item.ea_model ?? '—' }}</span>
    </template>

    <!-- Status: EA action dropdown (Approve / Reject) -->
    <template #item-quote_status_id="item">
      <div class="flex flex-col gap-1">
        <span
          class="text-xs font-semibold capitalize"
          :class="{
            'text-green-600': item.ea_status === 'approved',
            'text-red-600': item.ea_status === 'rejected',
            'text-yellow-600': item.ea_status === 'pending',
          }"
        >{{ item.ea_status }}</span>
        <x-select
          v-model="rowState[rowKey(item)].action"
          :options="eaActionOptions"
          placeholder="Select action"
          size="sm"
          class="w-full"
        />
      </div>
    </template>

    <!-- Update button + error per row -->
    <template #item-action="item">
      <div class="flex flex-col gap-1 items-start">
        <x-button
          size="xs"
          color="#ff5e00"
          :loading="rowState[rowKey(item)]?.loading"
          @click="updateRow(item)"
        >
          Update
        </x-button>
        <span
          v-if="rowState[rowKey(item)]?.error"
          class="text-xs text-red-600"
        >
          {{ rowState[rowKey(item)].error }}
        </span>
      </div>
    </template>
  </DataTable>
</template>
