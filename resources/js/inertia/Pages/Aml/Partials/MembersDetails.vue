<script setup>
const props = defineProps({
  customerType: String,
  isPayerDetails: Boolean,
});
const page = usePage();
const { isRequired } = useRules();
const notification = useToast();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const generateOptions = (items, valueKey, labelKey) =>
  useGenerateOptions(items, valueKey, labelKey);
const dateFormat = date =>
  date ? useDateFormat(date, 'DD-MM-YYYY').value : '-';
const isMemberFormEnabled = ref(false);
const isMemberEditEnabled = ref(false);
const isLoading = ref(false);
const memberFormEnableToggle = () => {
  isMemberFormEnabled.value = !isMemberFormEnabled.value;
  memberForm.reset();
};
const rules = {
  nameCheck: v => {
    const pattern = /^[a-zA-Z0-9\s]+$/;
    if (v == null || v == '') return true;
    return (
      pattern.test(v) || 'Special characters are not allowed in Member Name'
    );
  },
};
const nationalitiesOptions = computed(() => {
  return generateOptions(page.props.nationalities, 'id', 'text');
});
const memberRelationOptions = computed(() => {
  return generateOptions(page.props.lookups.member_relation, 'code', 'text');
});
const uboRelationOptions = computed(() => {
  return generateOptions(page.props.lookups.ubo_relation, 'code', 'text');
});
const computedMembers = computed(() => {
  if (props.customerType == page.props.customerTypeEnum.Individual) {
    if (props.isPayerDetails) {
      return page.props.membersDetails.filter(x => x.is_third_party_payer);
    }
    return page.props.membersDetails.filter(x => !x.is_third_party_payer);
  } else {
    if (props.isPayerDetails) {
      return page.props.uboDetails.filter(x => x.is_third_party_payer);
    }
    return page.props.uboDetails.filter(x => !x.is_third_party_payer);
  }
});
const membersTableHeader = reactive({
  isLoading: false,
  columns: computed(() => {
    const baseColumns = [
      {
        text: props.isPayerDetails ? 'Name' : 'Full Name',
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
        text:
          props.customerType == page.props.customerTypeEnum.Individual
            ? 'Relation'
            : 'Position',
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
    ];
    if (props.isPayerDetails) {
      return baseColumns.filter(
        column => column.value !== 'relation' && column.value !== 'is_payer',
      );
    }
    return baseColumns;
  }),
});
const memberForm = useForm({
  quote_type: page.props.quoteType.code,
  customer_type:
    page.props.screeningType == page.props.customerTypeEnum.EntityShort
      ? page.props.customerTypeEnum.Entity
      : page.props.customerTypeEnum.Individual,
  quote_request_id: page.props.quoteRequest.id,
  quote_id: page.props.quoteRequest.id,
  customer_id: page.props.quoteRequest.customer_id,
  id: null,
  first_name: null,
  last_name: null,
  dob: null,
  relation_code: null,
  nationality_id: null,
  is_payer: props.is_payer ?? false,
  from_aml_model: true,
  entity_id: page.props.entityDetails?.entity?.id ?? null,
  ...(props.isPayerDetails && {
    first_name: page.props.cardHolderName
      ? page.props.cardHolderName.card_holder_name
      : null,
    is_third_party_payer: true,
  }),
});
const createOrUpdateMember = async (memberForm, isMemberEditEnabled) => {
  let sectionName = props.isPayerDetails
    ? 'Payer'
    : props.customerType == page.props.customerTypeEnum.Individual
      ? 'Member'
      : 'UBO';

  const members = ref(
    props.customerType == page.props.customerTypeEnum.Individual
      ? page.props.membersDetails
      : page.props.uboDetails,
  );

  try {
    isLoading.value = true;
    const url = `/members${isMemberEditEnabled ? `/${memberForm.id}` : ''}`;
    const method = isMemberEditEnabled ? 'put' : 'post';
    const res = await axios[method](url, memberForm);
    if (res.status) {
      const { data } = res.data;
      const memberData = {
        ...data,
        nationality: data.nationality,
        relation: data.relation,
      };
      if (isMemberEditEnabled) {
        const index = members.value.findIndex(x => x.id === data.id);
        if (index !== -1) {
          members.value[index] = memberData;
        }
      } else {
        console.log(memberData);
        members.value.push(memberData);
      }
      notification.success({
        title: `${sectionName} ${isMemberEditEnabled ? 'Updated' : 'Added'} Successfully`,
        position: 'top',
      });
      memberForm.reset();
      isMemberFormEnabled.value = false;
    }
  } catch (err) {
    notification.error({
      title: err.response.data.message || 'Something went wrong',
      position: 'top',
    });
  } finally {
    isLoading.value = false;
  }
};
function onEditMember(member) {
  memberForm.clearErrors();
  isMemberFormEnabled.value = true;
  isMemberEditEnabled.value = true;
  memberForm.quote_type = page.props.quoteType.code;
  memberForm.quote_request_id = page.props.quoteRequest.id;
  memberForm.id = member.id;
  memberForm.first_name = member.first_name;
  if (
    props.customerType == page.props.customerTypeEnum.Individual &&
    !props.isPayerDetails
  ) {
    (memberForm.last_name == page.props.quoteType.code) ==
    page.props.quoteTypeCodeEnum.Health
      ? member.last_name
      : null;
  }
  memberForm.dob = member.dob;
  memberForm.relation_code = member.relation_code;
  memberForm.nationality_id = member.nationality_id;
  memberForm.is_payer = member.is_payer;
}
function memberSubmit(isValid) {
  if (!isValid) return;

  createOrUpdateMember(memberForm, isMemberEditEnabled.value);
}

const [AddMemberUBOPayerBtnTemplate, AddMemberUBOPayerBtnReuseTemplate] =
  createReusableTemplate();
</script>
<template>
  <x-form @submit="memberSubmit" auto-focus="false">
    <div v-show="isMemberFormEnabled" class="mb-4">
      <div class="flex justify-between">
        <h3 class="font-semibold text-primary-800 text-lg mb-3">
          {{
            isPayerDetails
              ? 'Add Payer Details'
              : props.customerType == page.props.customerTypeEnum.Individual
                ? 'Add Member'
                : 'Add UBO Details'
          }}
        </h3>
        <x-button
          v-if="isMemberFormEnabled"
          @click.prevent="memberFormEnableToggle"
          size="sm"
          color="red"
        >
          Hide
        </x-button>
      </div>
      <div
        v-if="isMemberFormEnabled"
        class="grid md:grid-cols-3 gap-x-6 gap-y-4 items-center"
      >
        <x-field
          :label="
            props.isPayerDetails
              ? 'Payer Name'
              : props.customerType == page.props.customerTypeEnum.Individual
                ? page.props.quoteTypeCodeEnum.Health ==
                  page.props.quoteType.code
                  ? 'Member First Name'
                  : 'Member Name'
                : 'Full Name'
          "
          required
        >
          <x-input
            v-model="memberForm.first_name"
            :placeholder="
              props.isPayerDetails
                ? 'Payer Name'
                : props.customerType == page.props.customerTypeEnum.Individual
                  ? page.props.quoteTypeCodeEnum.Health ==
                    page.props.quoteType.code
                    ? 'Member First Name'
                    : 'Member Name'
                  : 'Full Name'
            "
            class="w-full"
            :rules="[isRequired, rules.nameCheck]"
          />
        </x-field>
        <x-field
          v-if="
            !props.isPayerDetails &&
            props.customerType == page.props.customerTypeEnum.Individual &&
            page.props.quoteTypeCodeEnum.Health == page.props.quoteType.code
          "
          label="Member Last Name"
          required
        >
          <x-input
            v-model="memberForm.last_name"
            placeholder="Member Last Name"
            class="w-full"
            :rules="[isRequired, rules.nameCheck]"
          />
        </x-field>
        <x-field label="Nationality" required>
          <x-select
            :single="true"
            v-model="memberForm.nationality_id"
            placeholder="Select Nationality"
            :options="nationalitiesOptions"
            class="w-full"
            filterable
            filterPlaceholder="Select Nationality...."
            :rules="[isRequired]"
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
        <x-field
          v-if="
            props.customerType == page.props.customerTypeEnum.Individual &&
            !props.isPayerDetails
          "
          label="Relation"
          required
        >
          <x-select
            v-model="memberForm.relation_code"
            placeholder="Select Relation"
            :options="memberRelationOptions"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-checkbox
          v-if="
            props.customerType == page.props.customerTypeEnum.Individual &&
            !props.isPayerDetails
          "
          label="Is This Member a Payer?"
          v-model="memberForm.is_payer"
          color="primary"
          class="mb-0 mt-6"
        />
        <x-field
          v-if="
            props.customerType == page.props.customerTypeEnum.Entity &&
            !props.isPayerDetails
          "
          label="Position"
          required
        >
          <x-select
            v-model="memberForm.relation_code"
            placeholder="Select Position"
            :options="uboRelationOptions"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
      </div>
    </div>

    <AddMemberUBOPayerBtnTemplate>
      <x-button
        v-if="isMemberFormEnabled"
        size="sm"
        color="primary"
        type="submit"
        :loading="isLoading"
        :disabled="!can(permissionsEnum.AMLList)"
      >
        {{
          isPayerDetails
            ? 'Submit Payer Details'
            : props.customerType == page.props.customerTypeEnum.Individual
              ? 'Submit Member'
              : 'Submit UBO Details'
        }}
      </x-button>
      <x-button
        v-else
        v-if="
          props.customerType == page.props.customerTypeEnum.Individual ||
          isPayerDetails ||
          props.customerType == page.props.customerTypeEnum.Entity
        "
        size="sm"
        @click.prevent="memberFormEnableToggle"
        color="orange"
        type="button"
        :loading="isLoading"
        :disabled="!can(permissionsEnum.AMLList)"
      >
        {{
          isPayerDetails
            ? 'Add Third Party Payer'
            : props.customerType == page.props.customerTypeEnum.Individual
              ? 'Add Member'
              : 'Add UBO Details'
        }}
      </x-button>
    </AddMemberUBOPayerBtnTemplate>
    <x-divider v-if="isMemberFormEnabled" class="mb-3 mt-1" />
    <div class="flex justify-between items-center mb-4">
      <h3 class="font-semibold text-primary-800 text-lg">
        {{
          isPayerDetails
            ? 'Payer Details'
            : props.customerType == page.props.customerTypeEnum.Individual
              ? 'Member Details'
              : 'UBO Details'
        }}
        <x-tag size="sm">{{ computedMembers.length || 0 }}</x-tag>
      </h3>
      <x-tooltip v-if="!can(permissionsEnum.AMLList)" placement="bottom">
        <AddMemberUBOPayerBtnReuseTemplate />
        <template #tooltip>
          {{
            isPayerDetails
              ? "You don't have permission to add Third Party Payer"
              : props.customerType == page.props.customerTypeEnum.Individual
                ? "You don't have permission to add Member"
                : "You don't have permission to add UBO Details"
          }}
        </template>
      </x-tooltip>
      <template v-else>
        <AddMemberUBOPayerBtnReuseTemplate />
      </template>
    </div>
  </x-form>
  <DataTable
    table-class-name="tablefixed compact"
    :headers="membersTableHeader.columns"
    :items="computedMembers || []"
    show-index
    border-cell
    hide-rows-per-page
    hide-footer
  >
    <template #item-index="{ code }">
      <div>{{ code }}</div>
    </template>
    <template
      v-if="props.customerType == page.props.customerTypeEnum.Individual"
      #item-first_name="{ first_name, last_name }"
    >
      <div>
        {{ first_name }}
        {{
          page.props.quoteType.code == page.props.quoteTypeCodeEnum.Health ||
          page.props.quoteType.code == page.props.quoteTypeCodeEnum.Travel
            ? last_name
            : ''
        }}
      </div>
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
        <x-checkbox :modelValue="is_payer" color="primary" />
      </div>
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
</template>
