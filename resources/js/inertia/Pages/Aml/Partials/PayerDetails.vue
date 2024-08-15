<script setup>
const props = defineProps({
  quoteType: Object,
  quoteDetails: Object,
  nationalities: Object,
  membersDetails: Object,
  memberRelations: Object,
  customerType: String,
  entity_id: Number,
  cardHolderName: Object,
});

const { isRequired } = useRules();
const isLoading = ref(false);
const showPayerForm = ref(false);

const notification = useToast();
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

const nationalitiesOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const members = ref([...props.membersDetails]);

const computedMembers = computed(() => {
  return members.value.filter(x => x.is_third_party_payer);
});

const addMember = ref(false);
const editMemberDetails = ref(false);

const memberDetailsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Name',
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
      text: 'Action',
      value: 'action',
    },
  ],
});

function onEditMember(data) {
  memberForm.clearErrors();
  showPayerForm.value = true;
  editMemberDetails.value = true;
  memberForm.quote_type = props.quoteType.code;
  memberForm.quote_request_id = props.quoteDetails.id;
  memberForm.id = data.id;
  memberForm.first_name = data.first_name;
  memberForm.dob = data.dob;
  memberForm.relation_code = data.relation_code;
  memberForm.nationality_id = data.nationality_id;
  memberForm.is_payer = data.is_payer ? true : false;
}

const memberForm = useForm({
  quote_type: props.quoteType.code,
  customer_type: props.customerType,
  quote_request_id: props.quoteDetails.id,
  customer_id: props.quoteDetails.customer_id,
  entity_id: props.entity_id ?? null,
  id: null,
  first_name: props.cardHolderName ? props.cardHolderName.card_holder_name : '',
  dob: null,
  relation_code: null,
  nationality_id: null,
  is_third_party_payer: true,
  from_aml_model: true,
});

const rules = {
  nameCheck: v => {
    const pattern = /^[a-zA-Z0-9\s]+$/;
    if (v == null || v == '') return true;
    return (
      pattern.test(v) || 'Special characters are not allowed in Payer Name'
    );
  },
};

function onMemberSubmit(isValid) {
  if (!isValid) return;

  isLoading.value = true;
  if (editMemberDetails.value) {
    axios
      .put(`/members/${memberForm.id}`, memberForm)
      .then(res => {
        notification.success({
          title: 'Payer Updated Successfully',
          position: 'top',
        });
        memberForm.reset();
        if (res.status) {
          let { data } = res.data;

          let index = members.value.findIndex(x => x.id == data.id);
          if (index != -1) {
            members.value[index] = {
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
      .finally(() => {
        isLoading.value = false;
        showPayerForm.value = false;
        editMemberDetails.value = false;
      });
  } else {
    axios
      .post(`/members`, memberForm)
      .then(res => {
        notification.success({
          title: 'Payer Added Successfully',
          position: 'top',
        });
        memberForm.reset();
        if (res.status) {
          let { data } = res.data;

          members.value.push({
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
      .finally(() => {
        isLoading.value = false;
        showPayerForm.value = false;
      });
  }
}
</script>

<template>
  <div class="flex justify-between mt-5">
    <h3 class="font-semibold text-primary-800 text-lg mb-3">Payer Details</h3>
    <x-button
      size="sm"
      color="orange"
      :loading="isLoading"
      @click.prevent="showPayerForm = true"
    >
      Add Third Party Payer
    </x-button>
  </div>
  <DataTable
    table-class-name="tablefixed compact mt-5"
    :headers="memberDetailsTable.columns"
    :items="computedMembers || []"
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
          @click.prevent="onEditMember(item)"
        >
          Edit
        </x-button>
      </div>
    </template>
  </DataTable>

  <AppModal
    :showClose="true"
    :showHeader="true"
    v-model:modelValue="showPayerForm"
  >
    <template #header>Please Confirm Below Details</template>
    <template #default>
      <x-form @submit="onMemberSubmit" :auto-focus="false">
        <div class="grid md:grid-cols-2 mb-5 gap-4">
          <x-field label="Payer Name" required>
            <x-input
              v-model="memberForm.first_name"
              :rules="[isRequired, rules.nameCheck]"
              placeholder="Payer Name"
              type="text"
              class="w-full"
            />
          </x-field>
          <x-field label="Nationality">
            <ComboBox
              :single="true"
              v-model="memberForm.nationality_id"
              placeholder="Select Nationality"
              :options="nationalitiesOptions"
              class="w-full"
            />
          </x-field>
          <x-field label="Date Of Birth">
            <DatePicker
              v-model="memberForm.dob"
              placeholder="Date of Birth"
              class="w-full"
            />
          </x-field>
        </div>
        <div class="flex justify-end">
          <x-button type="submit" size="sm" color="orange" :loading="isLoading">
            Save
          </x-button>
        </div>
      </x-form>
    </template>
  </AppModal>
</template>
