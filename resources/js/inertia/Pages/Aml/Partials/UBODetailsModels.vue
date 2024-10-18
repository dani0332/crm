<script setup>
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

const uboMembers = ref(props.uboDetails);
const computedUboMembers = computed(() => {
  return uboMembers.value.filter(x => !x.is_third_party_payer);
});

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

const rules = {
  nameCheck: v => {
    const pattern = /^[a-zA-Z0-9\s]+$/;
    if (v == null || v == '') return true;
    return pattern.test(v) || 'Special characters are not allowed in Name';
  },
};

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
      text: 'Is this member is payer?',
      value: 'is_payer',
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
  quote_id: props.quoteDetails.id, //Added this quote_id because quote_request_id is not getting in Controller Request.
  customer_id: props.quoteDetails.customer_id,
  entity_id: props.entity_id ?? null,
  id: null,
  first_name: '',
  dob: null,
  relation_code: null,
  nationality_id: null,
  is_payer: props.is_payer ?? false,
  from_aml_model: true,
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
  uboForm.is_payer = data.is_payer;
}

const onUBOSubmit = isValid => {
  if (uboForm.nationality_id == null) isEmptyField.value = true;
  else isEmptyField.value = false;

  if (!isValid) return;
  loader.value.form = true;
  if (editUBODetails.value) {
    axios
      .post('/members/update', uboForm)
      .then(res => {
        notification.success({
          title: 'UBO Updated Successfully',
          position: 'top',
        });
        if (res.status) {
          let { data } = res.data;

          let index = uboMembers.value.findIndex(x => x.id == data.id);
          if (index != -1) {
            uboMembers.value[index] = {
              ...data,
              nationality: data.nationality,
              relation: data.relation,
            };
          }
        }
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
        if (res.status) {
          let { data } = res.data;

          uboMembers.value.push({
            ...data,
            nationality: data.nationality,
            relation: data.relation,
          });
        }
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
        <h3
          v-if="uboForm.entity_id !== null"
          class="font-semibold text-primary-800 text-lg mb-3"
        >
          Add UBO Details
        </h3>
        <x-button @click.prevent="addUBOToggle" size="sm" color="red">
          Hide
        </x-button>
      </div>

      <dl class="grid md:grid-cols-3 gap-x-6 gap-y-4">
        <x-field label="Full Name" required>
          <x-input
            v-model="uboForm.first_name"
            placeholder="Full Name"
            class="w-full"
            :rules="[isRequired, rules.nameCheck]"
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
        <x-tag size="sm">{{ computedUboMembers.length || 0 }}</x-tag>
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
        v-if="uboForm.entity_id !== null"
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
    :items="computedUboMembers || []"
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
    <template #item-is_payer="{ is_payer }">
      <div class="flex gap-2">
        <x-checkbox
          :modelValue="is_payer == 0 ? false : true"
          color="primary"
        />
      </div>
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
