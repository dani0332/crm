<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  quoteDetails: Object,
  quoteType: Object,
  nationalities: Object,
  uboDetails: Object,
  uboRelations: Object,
  customerType: String,
  entity_id: Number,
});

const { isRequired } = useRules();
const isEmptyField = ref(false);

const notification = useToast();
const loader = ref({
  form: false,
});
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

const nationalitiesOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const uboRelationOptions = computed(() => {
  return props.uboRelations.map(relation => ({
    value: relation.code,
    label: relation.text,
  }));
});

const addUBODetails = ref(false);
const editUBODetails = ref(false);
const addUBOToggle = payload => {
  addUBODetails.value = !addUBODetails.value;
  if (payload) uboForm.reset();
};
const UBODetailsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Full Name',
      value: 'first_name',
    },
    {
      text: 'Date of Birth',
      value: 'dob',
    },
    {
      text: 'Nationality',
      value: 'nationality',
    },
    {
      text: 'Position',
      value: 'relation',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const uboForm = useForm({
  quote_type: props.quoteType.code,
  customer_type: props.customerType,
  quote_request_id: props.quoteDetails.id,
  customer_id: props.quoteDetails.customer_id,
  entity_id: props.entity_id ?? null,
  id: null,
  first_name: '',
  dob: null,
  relation_code: null,
  nationality_id: null,
});

function onEditUBO(data) {
  addUBODetails.value = true;
  editUBODetails.value = true;
  uboForm.quote_type = props.quoteType.code;
  uboForm.quote_request_id = data.quote_request_id;
  uboForm.id = data.id;
  uboForm.first_name = data.first_name;
  uboForm.dob = data.dob;
  uboForm.relation_code = data.relation_code;
  uboForm.nationality_id = data.nationality_id;
}

const onUBOSubmit = isValid => {
  if (uboForm.nationality_id == null) isEmptyField.value = true;
  else isEmptyField.value = false;

  if (!isValid) return;
  loader.value.form = true;
  if (editUBODetails.value) {
    axios
      .put(`/members/${uboForm.id}`, uboForm)
      .then(res => {
        notification.success({
          title: 'UBO Updated Successfully',
          position: 'top',
        });
        uboForm.reset();
      })
      .catch(err => {
        notification.error({
          title: 'Something went wrong',
          position: 'top',
        });
      })
      .finally(() => (loader.value.form = false));
  } else {
    axios
      .post(`/members`, uboForm)
      .then(res => {
        notification.success({
          title: 'UBO Added Successfully',
          position: 'top',
        });
        uboForm.reset();
        addUBODetails.value = false;
      })
      .catch(err => {
        notification.error({
          title: 'Something went wrong',
          position: 'top',
        });
      })
      .finally(() => (loader.value.form = false));
  }
};
</script>

<template>
  <x-form @submit="onUBOSubmit" :auto-focus="false">
    <div v-show="addUBODetails" class="mb-4">
      <div class="flex justify-between">
        <h3 class="font-semibold text-primary-800 text-lg mb-3">
          Add UBO Details
        </h3>
        <x-button
          @click.prevent="addUBOToggle"
          size="sm"
          color="red"
        >
          Hide
        </x-button>
      </div>

      <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4">
        <x-field label="Full Name" required>
          <x-input
            v-model="uboForm.first_name"
            placeholder="Full Name"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="Nationality" required>
          <ComboBox
            :single="true"
            v-model="uboForm.nationality_id"
            placeholder="Select Nationality"
            :options="nationalitiesOptions"
            class="w-full"
            :hasError="isEmptyField"
          />
        </x-field>
        <x-field label="Date of Birth" required>
          <DatePicker
            v-model="uboForm.dob"
            placeholder="Date of Birth"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="Position" required>
          <x-select
            v-model="uboForm.relation_code"
            placeholder="Select Position"
            :options="uboRelationOptions"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
      </dl>
    </div>
    <x-divider v-if="addUBODetails" class="mb-3 mt-1" />

    <div class="flex justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        UBO Details
        <x-tag size="sm">{{ uboDetails.length || 0 }}</x-tag>
      </h3>
      <x-button
        v-if="addUBODetails"
        :loading="loader.form"
        size="sm"
        color="success"
        type="submit"
      >
        Submit UBO Details
      </x-button>
      <x-button
        v-else
        v-if="uboForm.entity_id"
        @click.prevent="addUBOToggle(true)"
        size="sm"
        color="orange"
      >
        Add UBO Details
      </x-button>
    </div>
  </x-form>
  <DataTable
    table-class-name="tablefixed compact"
    :headers="UBODetailsTable.columns"
    :items="uboDetails || []"
    show-index
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-index="{ code }">
      <div>{{ code }}</div>
    </template>
    <template #item-dob="{ dob }">
      {{ dateFormat(dob) }}
    </template>
    <template #item-relation="{ relation }">
      {{ relation?.text }}
    </template>
    <template #item-nationality="{ nationality }">
      {{ nationality?.text }}
    </template>
    <template #item-action="item">
      <div class="flex gap-2">
        <x-button
          size="xs"
          color="primary"
          outlined
          @click.prevent="onEditUBO(item)"
        >
          Edit
        </x-button>
      </div>
    </template>
  </DataTable>
</template>
