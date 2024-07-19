<script setup>
const props = defineProps({
  quote: Object,
  membersDetails: Object,
  memberRelations: {
    required: true,
    type: Array,
    default: [],
  },
  nationalities: {
    required: true,
    type: Array,
    default: [],
  },
  quote_type: {
    required: true,
    type: String,
  },
});

const page = usePage();
const notification = useToast();
const { isRequired } = useRules();

const modals = reactive({
  member: false,
});

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

const nationalitiesOptions = computed(() => {
  return page.props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});
const memberRelationOptions = computed(() => {
  return page.props.memberRelations.map(relation => ({
    value: relation.code,
    label: relation.text,
  }));
});

const members = ref(props.membersDetails);
const computedMembers = computed(() => {
  return members?.value?.filter(x => !x.is_third_party_payer);
});

const memberActionEdit = ref(false);
const memberDetailsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Member Name',
      value: 'first_name',
    },
    {
      text: 'Nationality',
      value: 'nationality',
    },
    {
      text: 'Date of Birth',
      value: 'dob',
    },
    {
      text: 'Relation',
      value: 'relation',
    },
    {
      text: 'Action',
      value: 'action',
    },
  ],
});
const memberFieldReq = reactive({
  nationality: false,
  dob: false,
});
const memberForm = useForm({
  id: null,
  first_name: '',
  dob: null,
  relation_code: null,
  nationality_id: null,
  quote_request_id: page.props.quote.id,
  quote_type: props.quote_type,
  customer_id: page.props.quote.customer_id,
  customer_type: page.props.quote.customer_type,
});
const addMemberModal = () => {
  memberForm.reset();
  memberActionEdit.value = false;
  modals.member = true;
};
function onEditMember(data) {
  memberActionEdit.value = true;
  modals.member = true;
  memberForm.id = data.id;
  memberForm.first_name = data.first_name;
  memberForm.dob = data.dob;
  memberForm.relation_code = data.relation_code;
  memberForm.nationality_id = data.nationality_id;
  memberForm.quote_request_id = data.quote_id;
  memberForm.quote_type = props.quote_type;
}
const onMemberSubmit = isValid => {
  memberFieldReq.nationality = memberForm.nationality_id == null;
  memberFieldReq.dob = memberForm.dob == null;
  if (!isValid) return;
  if (memberActionEdit.value) {
    memberForm.put(`/members/${memberForm.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Member Updated',
          position: 'top',
        });
        memberForm.reset();
      },
      onFinish: () => {
        modals.member = false;
      },
    });
  } else {
    memberForm.post(`/members`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Member Added',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.member = false;
      },
    });
  }
};
const confirmDeleteData = reactive({
  member: null,
});
const memberDelete = id => {
  modals.memberConfirm = true;
  confirmDeleteData.member = id;
};
const memberDeleteConfirmed = () => {
  memberForm.delete(
    `/members/${props.quote.customer_type}-${props.quote_type}-${confirmDeleteData.member}`,
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'Member Deleted',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.memberConfirm = false;
      },
    },
  );
};
</script>

<template>
  <x-collapse show-icon class="p-4 rounded shadow mb-6 bg-white">
    <h3 class="font-semibold text-primary-800 text-lg">
      Member Details
      <x-tag size="sm">{{ computedMembers.length || 0 }}</x-tag>
    </h3>
    <template #content>
      <x-divider class="mb-4 mt-1" />
      <div class="flex justify-end gap-4 items-center mb-4">
        <x-button @click.prevent="addMemberModal" size="sm" color="orange">
          Add Member
        </x-button>
      </div>
      <DataTable
        table-class-name="tablefixed compact"
        :headers="memberDetailsTable.columns"
        :items="computedMembers || []"
        show-index
        border-cell
        hide-rows-per-page
        hide-footer
      >
        <template #item-index="{ index, code }">
          <div>{{ code ?? 'Member ' + index }}</div>
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
            <x-button
              size="xs"
              color="error"
              outlined
              @click.prevent="memberDelete(item.id)"
            >
              Delete
            </x-button>
          </div>
        </template>
      </DataTable>

      <x-modal v-model="modals.member" size="lg" show-close backdrop>
        <template #header>
          {{ memberActionEdit ? 'Edit' : 'Add' }} Member
        </template>

        <x-form @submit="onMemberSubmit" :auto-focus="false">
          <div class="grid md:grid-cols-2 gap-4">
            <input type="hidden" :value="memberForm.id" />
            <x-input
              v-model="memberForm.first_name"
              label="Member Name*"
              placeholder="Member Name"
              :rules="[isRequired]"
            />
            <ComboBox
              v-model="memberForm.nationality_id"
              label="Nationality"
              :options="nationalitiesOptions"
              placeholder="Select Nationality"
              :single="true"
              :hasError="memberFieldReq.nationality"
            />
            <DatePicker
              v-model="memberForm.dob"
              label="DOB*"
              :hasError="memberFieldReq.dob"
              :rules="[isRequired]"
            />
            <x-select
              v-model="memberForm.relation_code"
              label="Relation"
              :options="memberRelationOptions"
              placeholder="Select Relation"
              class="w-full"
            />
          </div>

          <div class="text-right space-x-4 mt-8">
            <x-button size="sm" @click.prevent="modals.member = false">
              Cancel
            </x-button>

            <x-button
              size="sm"
              color="emerald"
              :loading="memberForm.processing"
              type="submit"
            >
              {{ memberActionEdit ? 'Update' : 'Save' }}
            </x-button>
          </div>
        </x-form>
      </x-modal>

      <x-modal v-model="modals.memberConfirm" show-close backdrop>
        <template #header> Delete Member Detail </template>
        <p>Are you sure you want to delete this?</p>
        <template #actions>
          <div class="text-right space-x-4">
            <x-button
              size="sm"
              ghost
              @click.prevent="modals.memberConfirm = false"
            >
              Cancel
            </x-button>
            <x-button
              size="sm"
              color="error"
              @click.prevent="memberDeleteConfirmed"
              :loading="memberForm.processing"
            >
              Delete
            </x-button>
          </div>
        </template>
      </x-modal>
    </template>
  </x-collapse>
  <!-- <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        Member Details
        <x-tag size="sm">{{ computedMembers.length || 0 }}</x-tag>
      </h3>
      <x-button @click.prevent="addMemberModal" size="sm" color="orange">
        Add Member
      </x-button>
    </div>

    <DataTable
      table-class-name="tablefixed compact"
      :headers="memberDetailsTable.columns"
      :items="computedMembers || []"
      show-index
      border-cell
      hide-rows-per-page
      hide-footer
    >
      <template #item-index="{ index, code }">
        <div>{{ code ?? 'Member ' + index }}</div>
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
          <x-button
            size="xs"
            color="error"
            outlined
            @click.prevent="memberDelete(item.id)"
          >
            Delete
          </x-button>
        </div>
      </template>
    </DataTable>

    <x-modal v-model="modals.member" size="lg" show-close backdrop>
      <template #header>
        {{ memberActionEdit ? 'Edit' : 'Add' }} Member
      </template>

      <x-form @submit="onMemberSubmit" :auto-focus="false">
        <div class="grid md:grid-cols-2 gap-4">
          <input type="hidden" :value="memberForm.id" />
          <x-input
            v-model="memberForm.first_name"
            label="Member Name*"
            placeholder="Member Name"
            :rules="[isRequired]"
          />
          <ComboBox
            v-model="memberForm.nationality_id"
            label="Nationality"
            :options="nationalitiesOptions"
            placeholder="Select Nationality"
            :single="true"
            :hasError="memberFieldReq.nationality"
          />
          <DatePicker
            v-model="memberForm.dob"
            label="DOB*"
            :hasError="memberFieldReq.dob"
            :rules="[isRequired]"
          />
          <x-select
            v-model="memberForm.relation_code"
            label="Relation"
            :options="memberRelationOptions"
            placeholder="Select Relation"
            class="w-full"
          />
        </div>

        <div class="text-right space-x-4 mt-8">
          <x-button size="sm" @click.prevent="modals.member = false">
            Cancel
          </x-button>

          <x-button
            size="sm"
            color="emerald"
            :loading="memberForm.processing"
            type="submit"
          >
            {{ memberActionEdit ? 'Update' : 'Save' }}
          </x-button>
        </div>
      </x-form>
    </x-modal>

    <x-modal v-model="modals.memberConfirm" show-close backdrop>
      <template #header> Delete Member Detail </template>
      <p>Are you sure you want to delete this?</p>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button
            size="sm"
            ghost
            @click.prevent="modals.memberConfirm = false"
          >
            Cancel
          </x-button>
          <x-button
            size="sm"
            color="error"
            @click.prevent="memberDeleteConfirmed"
            :loading="memberForm.processing"
          >
            Delete
          </x-button>
        </div>
      </template>
    </x-modal>
  </div> -->
</template>
