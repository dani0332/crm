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
  expanded: {
    required: false,
    type: Boolean,
    default: true,
  },
});

const page = usePage();
const notification = useToast();
const { isRequired } = useRules();
const permissionsEnum = page.props.permissionsEnum;
const can = permission => useCan(permission);

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

const [AddMemberButtonTemplate, AddMemButtonReuseTemplate] =
  createReusableTemplate();
const [EditMemberButtonTemplate, EditMemberButtonReuseTemplate] =
  createReusableTemplate();
const [DeleteMemberButtonTemplate, DeleteMemberButtonReuseTemplate] =
  createReusableTemplate();

const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            Member Details
            <x-tag size="sm">{{ computedMembers.length || 0 }}</x-tag>
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />
        <AddMemberButtonTemplate v-slot="{ isDisabled }">
          <x-button
            @click.prevent="addMemberModal"
            size="sm"
            color="orange"
            :disabled="isDisabled"
            v-if="readOnlyMode.isDisable === true"
          >
            Add Member
          </x-button>
        </AddMemberButtonTemplate>
        <div
          class="flex mb-3 justify-end"
          v-if="
            props.quote.quote_status_id !=
              page.props.quoteStatusEnum.PolicyCancelled ||
            page.props.linkedQuoteDetails.childLeadsCount == 0
          "
        >
          <x-tooltip
            v-if="page.props.lockLeadSectionsDetails.member_details"
            position="bottom"
          >
            <AddMemButtonReuseTemplate :isDisabled="true" />
            <template #tooltip>
              This lead is now locked as the policy has been booked. If changes
              are needed such midterm addition of member, go to 'Send Update',
              select 'Add Update', and choose 'Endorsement Financial'
            </template>
          </x-tooltip>
          <AddMemButtonReuseTemplate v-else />
        </div>

        <EditMemberButtonTemplate v-slot="{ isDisabled, item }">
          <x-button
            size="xs"
            color="primary"
            outlined
            @click.prevent="onEditMember(item)"
            :disabled="isDisabled"
            v-if="readOnlyMode.isDisable === true"
          >
            Edit
          </x-button>
        </EditMemberButtonTemplate>

        <DeleteMemberButtonTemplate v-slot="{ isDisabled, item }">
          <x-button
            size="xs"
            color="error"
            outlined
            @click.prevent="memberDelete(item.id)"
            :disabled="isDisabled"
            v-if="readOnlyMode.isDisable === true"
          >
            Delete
          </x-button>
        </DeleteMemberButtonTemplate>

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
            <div
              v-if="
                props.quote.quote_status_id !=
                  page.props.quoteStatusEnum.PolicyCancelled ||
                page.props.linkedQuoteDetails.childLeadsCount == 0
              "
              class="flex gap-2"
            >
              <x-tooltip
                v-if="page.props.lockLeadSectionsDetails.member_details"
                position="left"
                align="top"
              >
                <EditMemberButtonReuseTemplate
                  :isDisabled="true"
                  :item="item"
                />
                <template #tooltip>
                  <div class="!whitespace-normal text-xs">
                    This lead is now locked as the policy has been booked. If
                    changes are needed such midterm deletion of member or
                    marital status change, go to 'Send Update', select 'Add
                    Update', and choose 'Endorsement Financial'
                  </div>
                </template>
              </x-tooltip>

              <EditMemberButtonReuseTemplate v-else :item="item" />

              <x-tooltip
                v-if="page.props.lockLeadSectionsDetails.member_details"
                position="left"
              >
                <DeleteMemberButtonReuseTemplate
                  :isDisabled="true"
                  :item="item"
                />
                <template #tooltip>
                  <div class="whitespace-normal text-xs">
                    This lead is now locked as the policy has been booked. If
                    changes are needed such midterm deletion of member or
                    marital status change, go to 'Send Update', select 'Add
                    Update', and choose 'Endorsement Financial'
                  </div>
                </template>
              </x-tooltip>

              <DeleteMemberButtonReuseTemplate v-else :item="item" />
            </div>
          </template>
        </DataTable>
      </template>
    </Collapsible>

    <x-modal
      v-model="modals.member"
      size="lg"
      :title="`${memberActionEdit ? 'Edit' : 'Add'} Member`"
      show-close
      backdrop
      is-form
      @submit="onMemberSubmit"
    >
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

      <template #secondary-action>
        <x-button
          ghost
          tabindex="-1"
          size="sm"
          @click.prevent="modals.member = false"
        >
          Cancel
        </x-button>
      </template>
      <template #primary-action>
        <x-button
          size="sm"
          color="emerald"
          :loading="memberForm.processing"
          type="submit"
        >
          {{ memberActionEdit ? 'Update' : 'Save' }}
        </x-button>
      </template>
    </x-modal>

    <x-modal
      v-model="modals.memberConfirm"
      title="Delete Member Detail"
      show-close
      backdrop
    >
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
  </div>
</template>
