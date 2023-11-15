<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
  quoteType: Object,
  quoteDetails: Object,
  nationalities: Object,
  membersDetails: Object,
  memberRelations: Object,
  customerType: String,
});

const { isRequired } = useRules();
const isEmptyField = ref(false);
const isLoading = ref(false);

const members = ref(props.membersDetails);
const notification = useToast();
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

const nationalitiesOptions = computed(() => {
  return props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const memberRelationOptions = computed(() => {
  return props.memberRelations.map(relation => ({
    value: relation.code,
    label: relation.text,
  }));
});

const addMember = ref(false);
const editMemberDetails = ref(false);
const addMemberToggle = (payload = false) => {
  addMember.value = !addMember.value;
  if (payload) memberForm.reset();
};
const memberDetailsTable = reactive({
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
      text: 'Relation',
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
function onEditMember(data) {
  memberForm.clearErrors();
  addMember.value = true;
  editMemberDetails.value = true;
  memberForm.quote_type = props.quoteType.code;
  memberForm.quote_request_id = props.quoteDetails.id;
  memberForm.id = data.id;
  memberForm.first_name = data.first_name;
  memberForm.dob = data.dob;
  memberForm.relation_code = data.relation_code;
  memberForm.nationality_id = data.nationality_id;
}

const memberForm = useForm({
  quote_type: props.quoteType.code,
  customer_type: props.customerType,
  quote_request_id: props.quoteDetails.id,
  customer_id: props.quoteDetails.customer_id,
  id: null,
  first_name: null,
  dob: null,
  relation_code: null,
  nationality_id: null,
  is_payer: props.is_payer ?? false,
});

function onMemberSubmit(isValid) {
  if (memberForm.nationality_id == null) isEmptyField.value = true;
  else isEmptyField.value = false;

  if (!isValid) return;

  isLoading.value = true;
  if (editMemberDetails.value) {
    axios
      .put(`/members/${memberForm.id}`, memberForm)
      .then(res => {
        notification.success({
          title: 'Member Updated Successfully',
          position: 'top',
        });
        memberForm.reset();
        addMember.value = false;
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
      .finally(() => (isLoading.value = false));
  } else {
    axios
      .post(`/members`, memberForm)
      .then(res => {
        notification.success({
          title: 'Member Added Successfully',
          position: 'top',
        });
        memberForm.reset();
        addMember.value = false;
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
      .finally(() => (isLoading.value = false));
  }
}
</script>

<template>
  <x-form @submit="onMemberSubmit" :auto-focus="false">
    <div v-show="addMember" class="mb-4">
      <div class="flex justify-between">
        <h3 class="font-semibold text-primary-800 text-lg mb-3">Add Member</h3>
        <x-button
          v-if="addMember"
          @click.prevent="addMemberToggle"
          size="sm"
          color="red"
        >
          Hide
        </x-button>
      </div>

      <div
        class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center"
        v-if="addMember"
      >
        <x-field label="Member Name" required>
          <x-input
            v-model="memberForm.first_name"
            placeholder="Member Name"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="Nationality" required>
          <ComboBox
            :single="true"
            v-model="memberForm.nationality_id"
            placeholder="Select Nationality"
            :options="nationalitiesOptions"
            class="w-full"
            :hasError="isEmptyField"
          />
        </x-field>
        <x-field label="Date of Birth" required>
          <DatePicker
            v-model="memberForm.dob"
            placeholder="Date of Birth"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="Relation" required>
          <x-select
            v-model="memberForm.relation_code"
            placeholder="Select Relation"
            :options="memberRelationOptions"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-checkbox
          v-model="memberForm.is_payer"
          label="Is This Member a Payer?"
          color="primary"
          class="mb-0 mt-6"
        />
      </div>
    </div>
    <x-divider v-if="addMember" class="mb-3 mt-1" />

    <div class="flex justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Member Details
        <x-tag size="sm">{{ membersDetails.length || 0 }}</x-tag>
      </h3>
      <x-button
        v-if="addMember"
        size="sm"
        color="primary"
        type="submit"
        :loading="isLoading"
      >
        Submit Member
      </x-button>
      <x-button
        v-else
        size="sm"
        @click.prevent="addMemberToggle(true)"
        color="orange"
        type="button"
        :loading="isLoading"
      >
        Add Member
      </x-button>
    </div>
  </x-form>
  <DataTable
    table-class-name="tablefixed compact"
    :headers="memberDetailsTable.columns"
    :items="members || []"
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
    <template #item-is_payer="{ is_payer }">
      <div class="flex gap-2">
        <x-checkbox
          :modelValue="is_payer == 0 ? false : true"
          color="primary"
        />
      </div>
    </template>
  </DataTable>
</template>
