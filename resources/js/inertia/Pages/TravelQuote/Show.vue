<script setup>
import { computed, ref, reactive, onMounted } from 'vue';
import { Head, usePage, router, useForm, Link } from '@inertiajs/vue3';
import { useDateFormat, useClipboard } from '@vueuse/core';
import LazyDocumentUploader from './Partials/DocumentUploader.vue';
import LazyAvailablePlan from './Partials/AvailablePlans.vue';
import LazyCreatePlan from './Partials/CreatePlan.vue';
import { useNotifications } from '@indielayer/ui';
import axios from 'axios';
import ComboBox from '@/inertia/Components/ComboBox.vue';
import PaymentTable from './Partials/PaymentTable.vue';

defineProps({
  quote: Object,
    allowedDuplicateLOB: Array,
    advisors: Array,
    renewalAdvisors: Array,
});

const page = usePage();

const notification = useNotifications('toast');

const rules = {
  isRequired: v => !!v || 'This field is required',
};

const assignSubteam = ref(page.props.quote.health_team_type || '');

const subTeamOptions = computed(() => {
    // if renewalAdvisors is not empty then return renewal advisor list otherwise return advisor list
    console.log(page.props.renewalAdvisors);
    console.log(page.props.advisors);
    if (page.props.renewalAdvisors.length > 0) {
        return page.props.renewalAdvisors.map(advisor => ({
            value: advisor.id,
            label: advisor.name,
        }));
    }
    return page.props.advisors.map(advisor => ({
        value: advisor.id,
        label: advisor.name,
    }));

})


const leadDuplicateForm = useForm({
  modelType: 'travel',
  parentType: 'travel',
  entityId: page.props.quote.id,
  entityCode: page.props.quote.code,
  entityUId: page.props.quote.uid,
  lob_team: [],
  lob_team_sub_selection: null,
});

const openDuplicate = () => {
  modals.duplicate = true;
  leadDuplicateForm.reset();
};

const onCreateDuplicate = isValid => {
  if (!isValid) return;
  leadDuplicateForm.post('/quotes/createDuplicate', {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Quote duplicated successfully',
        position: 'top',
      });
    },
    onFinish: () => {
      modals.duplicate = false;
    },
  });
};


const modals = reactive({
  duplicate: false
});

onMounted(() => {
    console.log(page.props.quote);
});
</script>
<template>
  <div>
    <Head title="Travel Detail" />
    <div class="flex justify-between items-center flex-wrap gap-2">
      <h2 class="text-xl font-semibold">Travel Detail</h2>
      <div class="flex gap-2">
        <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
          Duplicate Lead
        </x-button>

        <Link href="/quotes/travel" preserve-scroll>
          <x-button size="sm" color="primary" tag="div"> Travel List </x-button>
        </Link>

        <Link :href="`${quote.uuid}/edit`">
          <x-button size="sm" tag="div">Edit</x-button>
        </Link>
      </div>
    </div>

    <x-modal v-model="modals.duplicate" size="lg" show-close backdrop>
      <template #header> Duplicate Lead </template>
      <x-form @submit="onCreateDuplicate" :auto-focus="false">
        <div class="grid gap-4">
          <x-select
            v-model="leadDuplicateForm.lob_team"
            label="LOBs"
            :options="
              allowedDuplicateLOB.map(lob => ({
                value: lob,
                label: lob,
              }))
            "
            :rules="[rules.isRequired]"
            placeholder="Select LOB For Duplication"
            class="w-full"
            multiple
          />
          <x-select
            v-model="leadDuplicateForm.lob_team_sub_selection"
            label="Reason"
            :rules="[rules.isRequired]"
            class="w-full"
            :options="[
              { value: 'new_enquiry', label: 'New enquiry' },
              { value: 'record_only', label: 'Record purposes only' },
            ]"
          />

          <x-button
            color="orange"
            type="submit"
            :loading="leadDuplicateForm.processing"
          >
            Create Duplicate
          </x-button>
        </div>
      </x-form>
    </x-modal>

    <x-divider class="my-4" />

    <div class="p-4 rounded shadow mb-6 bg-primary-50/50">
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-1/2 flex gap-2 items-end">
          <x-select
            v-model="assignSubteam"
            label="Assign Subteam"
            :options="subTeamOptions"
            placeholder="Select Subteam"
            class="w-auto flex-1"
          />
          <div>
            <x-button
              color="orange"
              size="sm"
              @click.prevent="onTeamAssign"
              :loading="isDisabled"
            >
              Assign Team
            </x-button>
          </div>
        </div>
        <div class="w-full md:w-1/2 flex gap-2 items-end">
          <x-select
            v-model="assignLead"
            label="Assign Lead"
            :options="advisorOptions"
            placeholder="Select Lead"
            class="w-auto flex-1"
          />
          <div>
            <x-button
              color="orange"
              size="sm"
              @click.prevent="onAssignLead"
              :loading="isDisabled"
            >
              Assign
            </x-button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>
