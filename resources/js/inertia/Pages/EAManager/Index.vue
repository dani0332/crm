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

const filterForm = reactive({
  ref_id: props.filters.ref_id ?? '',
  lob: props.filters.lob ?? null,
  status: props.filters.status ?? null,
  ea_model: props.filters.ea_model ?? null,
  date_from: props.filters.date_from ?? '',
  date_to: props.filters.date_to ?? '',
});

const page = usePage();
const quoteTypes = computed(() =>
  (page.props.quoteTypes ?? []).map(qt => ({ value: qt.id, label: qt.name })),
);

const eaModelOptions = [
  { value: 'referral', label: 'Referral' },
  { value: 'collaborate', label: 'Collaborate' },
];

const applyFilters = () => {
  router.get(route('ea-manager.index'), filterForm, { preserveState: true });
};

const resetFilters = () => {
  Object.assign(filterForm, {
    ref_id: '',
    lob: null,
    status: null,
    ea_model: null,
    date_from: '',
    date_to: '',
  });
  applyFilters();
};

const isActionLoading = ref(null);
const actionError = ref(null);

const approveLead = async lead => {
  isActionLoading.value = lead.id;
  actionError.value = null;
  try {
    await axios.post(
      route('ea-manager.approve', { quoteType: lead.quote_type, quoteId: lead.id }),
    );
    router.reload({ only: ['leads', 'pendingRejectionsCount'] });
  } catch (err) {
    actionError.value = err?.response?.data?.message ?? 'Error approving lead.';
  } finally {
    isActionLoading.value = null;
  }
};

const changeToReferral = async lead => {
  isActionLoading.value = lead.id;
  actionError.value = null;
  try {
    await axios.patch(
      route('ea-manager.change-model', { quoteType: lead.quote_type, quoteId: lead.id }),
      { ea_model: 'referral' },
    );
    router.reload({ only: ['leads', 'pendingRejectionsCount'] });
  } catch (err) {
    actionError.value = err?.response?.data?.message ?? 'Error changing model.';
  } finally {
    isActionLoading.value = null;
  }
};

const hasRejection = lead =>
  lead.ea_assigned_advisor_rejected_at || lead.ea_expert_advisor_rejected_at;
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800">EA Manager Dashboard</h1>
      <x-badge v-if="pendingRejectionsCount > 0" color="red" size="lg">
        {{ pendingRejectionsCount }} Pending Rejection{{ pendingRejectionsCount !== 1 ? 's' : '' }}
      </x-badge>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded shadow p-4 mb-6">
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <x-input
          v-model="filterForm.ref_id"
          label="Ref ID"
          name="ref_id"
          placeholder="Search ref ID"
          clearable
        />

        <x-select
          v-model="filterForm.lob"
          label="LOB"
          name="lob"
          :options="quoteTypes"
          placeholder="All LOBs"
          clearable
        />

        <x-select
          v-model="filterForm.ea_model"
          label="EA Model"
          name="ea_model"
          :options="eaModelOptions"
          placeholder="All Models"
          clearable
        />

        <x-input
          v-model="filterForm.date_from"
          label="Date From"
          name="date_from"
          type="date"
        />

        <x-input
          v-model="filterForm.date_to"
          label="Date To"
          name="date_to"
          type="date"
        />

        <div class="flex items-end gap-2">
          <x-button size="sm" color="primary" @click="applyFilters">Filter</x-button>
          <x-button size="sm" ghost @click="resetFilters">Reset</x-button>
        </div>
      </div>
    </div>

    <div v-if="actionError" class="mb-4 p-3 bg-red-50 border border-red-300 text-red-700 rounded">
      {{ actionError }}
    </div>

    <!-- Leads table -->
    <div class="bg-white rounded shadow overflow-x-auto">
      <table class="min-w-full text-sm text-left">
        <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
          <tr>
            <th class="px-4 py-3">Ref ID</th>
            <th class="px-4 py-3">Created</th>
            <th class="px-4 py-3">LOB</th>
            <th class="px-4 py-3">EA Model</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Lead Generator</th>
            <th class="px-4 py-3">Advisor</th>
            <th class="px-4 py-3">Expert Advisor</th>
            <th class="px-4 py-3">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="lead in leads"
            :key="`${lead.quote_type}-${lead.id}`"
            :class="{ 'bg-red-50': hasRejection(lead) }"
          >
            <td class="px-4 py-3 font-medium text-blue-600">{{ lead.code }}</td>
            <td class="px-4 py-3 whitespace-nowrap">
              {{ new Date(lead.created_at).toLocaleDateString() }}
            </td>
            <td class="px-4 py-3 capitalize">{{ lead.quote_type }}</td>
            <td class="px-4 py-3">
              <x-badge :color="lead.ea_model === 'collaborate' ? 'blue' : 'gray'" size="sm">
                {{ lead.ea_model ?? '—' }}
              </x-badge>
            </td>
            <td class="px-4 py-3">{{ lead.quote_status_id }}</td>
            <td class="px-4 py-3">{{ lead.lead_generator?.name ?? '—' }}</td>
            <td class="px-4 py-3">{{ lead.advisor?.name ?? '—' }}</td>
            <td class="px-4 py-3">{{ lead.expert_advisor?.name ?? '—' }}</td>
            <td class="px-4 py-3">
              <div v-if="lead.ea_model === 'collaborate'" class="flex gap-2">
                <x-button
                  size="xs"
                  color="emerald"
                  :loading="isActionLoading === lead.id"
                  @click="approveLead(lead)"
                >
                  Approve
                </x-button>
                <x-button
                  size="xs"
                  color="orange"
                  :loading="isActionLoading === lead.id"
                  @click="changeToReferral(lead)"
                >
                  → Referral
                </x-button>
              </div>
            </td>
          </tr>

          <tr v-if="leads.length === 0">
            <td colspan="9" class="text-center py-8 text-gray-400">No EA leads found.</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
