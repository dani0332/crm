<script setup>
const props = defineProps({
  quote: Object,
  UBOsDetails: Object,
  UBORelations: {
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
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;
const { isRequired } = useRules();
const modals = reactive({
  ubo: false,
});

const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';

const nationalitiesOptions = computed(() => {
  return page.props.nationalities.map(nat => ({
    value: nat.id,
    label: nat.text,
  }));
});

const UBORelationOptions = computed(() => {
  return page.props.UBORelations.map(relation => ({
    value: relation.code,
    label: relation.text,
  }));
});

const uboMembers = ref(props.UBOsDetails);
const computedUboMembers = computed(() => {
  return (
    uboMembers &&
    uboMembers.value &&
    uboMembers.value.filter(x => !x.is_third_party_payer)
  );
});

const isLoading = ref(false);
const UBOActionEdit = ref(false);
const UBODetailsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Name',
      value: 'first_name',
    },
    {
      text: 'Owner / Partner',
      value: 'relation',
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
      text: 'Action',
      value: 'action',
    },
  ],
});

const UBOFieldReq = reactive({
  nationality: false,
  dob: false,
});

const UBOForm = useForm({
  id: null,
  first_name: '',
  dob: null,
  relation_code: null,
  nationality_id: null,
  quote_request_id: page.props.quote.id,
  customer_id: page.props.quote.customer_id,
  quote_type: props.quote_type,
  customer_type: page.props.quote.customer_type,
  entity_id:
    page.props.quote?.quote_request_entity_mapping?.entity_id ??
    page.props.quote.entity_id,
});

const addUBOModal = () => {
  UBOForm.reset();
  UBOActionEdit.value = false;
  modals.UBO = true;
};

function onEditUBO(data) {
  UBOActionEdit.value = true;
  modals.UBO = true;
  UBOForm.id = data.id;
  UBOForm.first_name = data.first_name;
  UBOForm.dob = data.dob;
  UBOForm.relation_code = data.relation_code;
  UBOForm.nationality_id = data.nationality_id;
  UBOForm.quote_request_id = data.quote_id;
  UBOForm.quote_type = props.quote_type;
}

const onUBOSubmit = isValid => {
  UBOFieldReq.nationality = UBOForm.nationality_id == null;
  UBOFieldReq.dob = UBOForm.dob == null;
  if (!isValid) return;
  isLoading.value = true;

  if (UBOActionEdit.value) {
    UBOForm.put(`/members/${UBOForm.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'UBO Updated',
          position: 'top',
        });
        UBOForm.reset();
      },
      onFinish: () => {
        modals.UBO = false;
        isLoading.value = false;
      },
    });
  } else {
    UBOForm.post(`/members`, {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'UBO Added',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.UBO = false;
        isLoading.value = false;
      },
    });
  }
};

const confirmDeleteData = reactive({
  UBO: null,
});

const UBODelete = id => {
  modals.UBOConfirm = true;
  confirmDeleteData.UBO = id;
};

const UBODeleteConfirmed = () => {
  UBOForm.delete(
    `/members/${props.quote.customer_type}-${props.quote_type}-${confirmDeleteData.UBO}`,
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.success({
          title: 'UBO Deleted',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.UBOConfirm = false;
      },
    },
  );
};
const readOnlyMode = reactive({
  isDisable: true,
});
onMounted(() => {
  readOnlyMode.isDisable = !can(permissionsEnum.All_QUOTES_VIEWONLY_ACCESS);
});

const [AddUBOButtonTemplate, AddUBOButtonReuseTemplate] =
  createReusableTemplate();
const [EditUBOButtonTemplate, EditUBOButtonReuseTemplate] =
  createReusableTemplate();
const [DeleteUBOButtonTemplate, DeleteUBOButtonReuseTemplate] =
  createReusableTemplate();
</script>

<template>
  <div class="p-4 rounded shadow mb-6 bg-white">
    <Collapsible :expanded="expanded">
      <template #header>
        <div class="flex justify-between items-center">
          <h3 class="font-semibold text-primary-800 text-lg">
            UBO Details
            <x-tag size="sm">{{
              (computedUboMembers && computedUboMembers.length) || 0
            }}</x-tag>
          </h3>
        </div>
      </template>
      <template #body>
        <x-divider class="my-4" />

        <AddUBOButtonTemplate v-slot="{ isDisabled }">
          <div v-if="readOnlyMode.isDisable === true">
            <x-button
              v-if="
                page.props.quote?.quote_request_entity_mapping?.entity_id ??
                page.props.quote.entity_id
              "
              @click.prevent="addUBOModal"
              size="sm"
              color="orange"
              :loading="isLoading"
              :disabled="isDisabled"
            >
              Add UBO
            </x-button>
          </div>
        </AddUBOButtonTemplate>

        <div class="flex mb-3 justify-end">
          <x-tooltip
            v-if="page.props.lockLeadSectionsDetails.member_details"
            placement="bottom"
          >
            <AddUBOButtonReuseTemplate :isDisabled="true" />
            <template #tooltip>
              This lead is now locked as the policy has been booked. If changes
              are needed such midterm addition of member, go to 'Send Update',
              select 'Add Update', and choose 'Endorsement Financial'
            </template>
          </x-tooltip>
          <AddUBOButtonReuseTemplate v-else />
        </div>

        <EditUBOButtonTemplate v-slot="{ isDisabled, item }">
          <x-button
            size="xs"
            color="primary"
            outlined
            @click.prevent="onEditUBO(item)"
            :disabled="isDisabled"
            v-if="readOnlyMode.isDisable === true"
          >
            Edit
          </x-button>
        </EditUBOButtonTemplate>

        <DeleteUBOButtonTemplate v-slot="{ isDisabled, item }">
          <x-button
            size="xs"
            color="error"
            outlined
            @click.prevent="UBODelete(item.id)"
            :disabled="isDisabled"
            v-if="readOnlyMode.isDisable === true"
          >
            Delete
          </x-button>
        </DeleteUBOButtonTemplate>

        <DataTable
          table-class-name="tablefixed compact"
          :headers="UBODetailsTable.columns"
          :items="computedUboMembers || []"
          show-index
          border-cell
          hide-rows-per-page
          hide-footer
        >
          <template #item-index="{ index, code }">
            <div>{{ code ?? 'UBO ' + index }}</div>
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
              <x-tooltip
                v-if="page.props.lockLeadSectionsDetails.member_details"
                placement="bottom"
              >
                <EditUBOButtonReuseTemplate :isDisabled="true" :item="item" />
                <template #tooltip>
                  This lead is now locked as the policy has been booked. If
                  changes are needed such midterm deletion of member or marital
                  status change, go to 'Send Update', select 'Add Update', and
                  choose 'Endorsement Financial'
                </template>
              </x-tooltip>
              <EditUBOButtonReuseTemplate v-else :item="item" />

              <x-tooltip
                v-if="page.props.lockLeadSectionsDetails.member_details"
                placement="bottom"
              >
                <DeleteUBOButtonReuseTemplate :isDisabled="true" :item="item" />
                <template #tooltip>
                  This lead is now locked as the policy has been booked. If
                  changes are needed such midterm deletion of member or marital
                  status change, go to 'Send Update', select 'Add Update', and
                  choose 'Endorsement Financial'
                </template>
              </x-tooltip>
              <DeleteUBOButtonReuseTemplate v-else :item="item" />
            </div>
          </template>
        </DataTable>
      </template>
    </Collapsible>

    <x-modal
      v-model="modals.UBO"
      size="lg"
      :title="`${UBOActionEdit ? 'Edit' : 'Add'} UBO`"
      show-close
      backdrop
      is-form
      @submit="onUBOSubmit"
    >
      <div class="grid md:grid-cols-2 gap-4">
        <input type="hidden" :value="UBOForm.id" />
        <x-input
          v-model="UBOForm.first_name"
          :rules="[isRequired]"
          label="Name"
          placeholder="Name"
        />
        <x-select
          v-model="UBOForm.relation_code"
          :rules="[isRequired]"
          label="Owner / Partner"
          :options="UBORelationOptions"
          placeholder="Select Owner / Partner"
          class="w-full"
        />
        <DatePicker
          :rules="[isRequired]"
          v-model="UBOForm.dob"
          label="DOB"
          :hasError="UBOFieldReq.dob"
        />
        <ComboBox
          required
          v-model="UBOForm.nationality_id"
          label="Nationality"
          :options="nationalitiesOptions"
          placeholder="Select Nationality"
          :single="true"
          :hasError="UBOFieldReq.nationality"
        />
      </div>

      <template #secondary-action>
        <x-button
          ghost
          tabindex="-1"
          size="sm"
          @click.prevent="modals.UBO = false"
        >
          Cancel
        </x-button>
      </template>
      <template #primary-action>
        <x-button
          size="sm"
          color="emerald"
          :loading="UBOForm.processing"
          type="submit"
        >
          {{ UBOActionEdit ? 'Update' : 'Save' }}
        </x-button>
      </template>
    </x-modal>

    <x-modal
      v-model="modals.UBOConfirm"
      title="Delete UBO Detail"
      show-close
      backdrop
    >
      <template #header> </template>
      <p>Are you sure you want to delete this?</p>
      <template #actions>
        <div class="text-right space-x-4">
          <x-button size="sm" ghost @click.prevent="modals.UBOConfirm = false">
            Cancel
          </x-button>
          <x-button
            size="sm"
            color="error"
            @click.prevent="UBODeleteConfirmed"
            :loading="UBOForm.processing"
          >
            Delete
          </x-button>
        </div>
      </template>
    </x-modal>
  </div>
</template>
