<script setup>
const props = defineProps({
  tmlead: Object,
  tmLeadStatusCode: String,
  tmInsuranceTypeCode: String,
  tmLeadStatuses: Array,
  isLeadEditable: String,
  customerCorrectPhoneNo: String,
});

const page = usePage();
const can = permission => useCan(permission);
const permissionsEnum = page.props.permissionsEnum;

const { isRequired } = useRules();

const leadForm = useForm({
  id: props.tmlead.id ?? null,
  tmLeadId: props.tmlead.id ?? null,
  no_answer_count: props.tmlead?.no_answer_count ?? null,
  tm_lead_statuses_id: props.tmlead?.tm_lead_statuses_id ?? null,
  next_followup_date: props.tmlead?.next_followup_date ?? null,
  notes: props.tmlead.notes ?? null,
});

function onSubmit(isValid) {
  if (isValid) {
    leadForm.get(route('tmLeadUpdate', props.tmlead.id), {
      preserveScroll: true,
      onSuccess: () => {},
      onError: errors => {},
    });
  }
}
</script>
<template>
  <Head title="TeleMarkating Details" />
  <div class="flex justify-between items-center flex-wrap gap-2 mb-5">
    <h2 class="text-xl font-semibold">TeleMarkating Detail</h2>
    <div class="flex gap-2">
      <Link
        :href="route('tmleads-delete', tmlead.id)"
        preserve-scroll
        v-if="can(permissionsEnum.TeleMarketingDelete)"
        method="delete"
        as="button"
      >
        <x-button size="sm" color="primary" tag="div"> Delete </x-button>
      </Link>
      <Link
        :href="route('tmleads-edit', tmlead.id)"
        v-if="can(permissionsEnum.TeleMarketingEdit) && isLeadEditable == '1'"
      >
        <x-button size="sm" tag="div">Edit</x-button>
      </Link>
      <Link :href="route('tmleads-list')">
        <x-button size="sm" color="#ff5e00"> TeleMarkating List </x-button>
      </Link>
    </div>
  </div>
  <x-divider class="my-4" />
  <div class="p-4 rounded shadow mb-6 bg-white">
    <div class="text-sm">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <div>
            <x-tooltip placement="bottom">
              <label
                class="font-medium text-gray-800 text-sm underline decoration-dotted decoration-primary-700"
              >
                Ref-ID
              </label>
              <template #tooltip> Reference ID </template>
            </x-tooltip>
          </div>
          <div>{{ tmlead.cdb_id }}</div>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Lead Type</dt>
          <dd>{{ tmlead.tmleadtype ? tmlead.tmleadtype.text : '' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Customer Name</dt>
          <dd>{{ tmlead.customer_name }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Insurance Type</dt>
          <dd>
            {{ tmlead.tminsurancetype ? tmlead.tminsurancetype.text : '' }}
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Email Address</dt>
          <dd>{{ tmlead.email_address }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Phone number</dt>
          <dd>{{ customerCorrectPhoneNo }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Enquiry date</dt>
          <dd>{{ tmlead.enquiry_date }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Allocation Date</dt>
          <dd>{{ tmlead.allocation_date }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Advisor</dt>
          <dd>{{ tmlead.assignedto ? tmlead.assignedto.name : '' }}</dd>
        </div>
        <div
          class="grid sm:grid-cols-2"
          v-if="
            tmInsuranceTypeCode == 'Car' ||
            tmInsuranceTypeCode == 'Bike' ||
            tmInsuranceTypeCode == 'Life' ||
            tmInsuranceTypeCode == 'Health'
          "
        >
          <dt class="font-medium">DOB</dt>
          <dd>{{ tmlead.dob }}</dd>
        </div>
      </dl>
    </div>
    <div class="text-sm mt-5" v-if="tmInsuranceTypeCode == 'Car'">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Nationality</dt>
          <dd>{{ tmlead.nationality ? tmlead.nationality.text : '' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Years of driving</dt>
          <dd>{{ tmlead.yearsofdriving ? tmlead.yearsofdriving.text : '' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Car Make</dt>
          <dd>
            {{ tmlead.carmake ? tmlead.carmake.text : '' }}
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Car Model</dt>
          <dd>{{ tmlead.carmodel ? tmlead.carmodel.text : '' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Year of Manufacture</dt>
          <dd>{{ tmlead.year_of_manufacture }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Car Value</dt>
          <dd>
            {{ tmlead.car_value }}
          </dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Car Type of Insurance</dt>
          <dd>
            {{
              tmlead.cartypeofinsurance ? tmlead.cartypeofinsurance.text : ''
            }}
          </dd>
        </div>
      </dl>
    </div>
    <div class="text-sm mt-5">
      <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created At</dt>
          <dd>{{ tmlead.created_at }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Created By</dt>
          <dd>{{ tmlead.createdby ? tmlead.createdby.name : '' }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated At</dt>
          <dd>{{ tmlead.updated_at }}</dd>
        </div>
        <div class="grid sm:grid-cols-2">
          <dt class="font-medium">Updated By</dt>
          <dd>{{ tmlead.updatedby ? tmlead.updatedby.name : '' }}</dd>
        </div>
      </dl>
    </div>
  </div>

  <div class="p-4 rounded shadow mb-6 bg-primary-50/25">
    <div>
      <h3 class="font-semibold text-primary-800 text-lg">
        Update TM Lead Status & Notes
      </h3>
      <x-divider class="mb-4 mt-1" />
    </div>
    <x-form @submit="onSubmit" :auto-focus="false">
      <div class="grid sm:grid-cols-2 gap-4">
        <x-field label="Lead Status" required>
          <x-select
            v-model="leadForm.tm_lead_statuses_id"
            :options="
              tmLeadStatuses.map(item => ({
                value: item.id,
                label: item.text,
              }))
            "
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-field label="Next Follow-up Date & Time" required>
          <DatePicker
            v-model="leadForm.next_followup_date"
            class="w-full"
            :rules="[isRequired]"
          />
        </x-field>
        <x-textarea
          v-model="leadForm.notes"
          type="text"
          label="Notes"
          placeholder="Lead Notes"
          class="w-full"
        />
        <x-field label="No Answer/Switched Off (Count)" required>
          <x-input
            :value="tmlead.no_answer_count ? tmlead.no_answer_count : 0"
            type="tel"
            class="w-full"
            disabled
          />
        </x-field>
      </div>
      <div class="flex justify-end">
        <x-button type="submit" class="mt-4" color="emerald" size="sm">
          Update
        </x-button>
      </div>
    </x-form>
  </div>
  <AuditLogs
    v-if="can(permissionsEnum.Auditable)"
    :type="'App\\Models\\TmLead'"
    :quoteType="'TmLead'"
    :id="$page.props.tmlead.id"
  />
</template>
