<script setup>
defineProps({
	quote: Object,
	leadStatuses: Array, //
	ecomDetails: Object,
	membersDetail: Array,
	memberCategories: Array,
	salaryBands: Array,
	nationalities: Array,
	emirates: Array,
	advisors: Array,
	listQuotePlans: Array, //
	quoteDocuments: Object,
	documentTypes: Object,
	cdnPath: String,
	ecomHealthInsuranceQuoteUrl: String,
	activities: Array,
	customerAdditionalContacts: Array,
	lostReasons: Array,
	quoteStatusEnum: Object,
	modelType: String,
	notProductionApproval: Boolean,
	allowedDuplicateLOB: Array,
	permissions: Object,
	genderOptions: Object,
	isQuoteDocumentEnabled: Boolean,
	isBetaUser: Boolean,
	payments: Array,
	quoteRequest: Object,
	can: Object,
	paymentMethods: Object,
	sendPolicy: Boolean,
	//
	record: Object,
	isRenewalUser: Boolean,
});
const page = usePage();
const is = role => useHasRole(role);
const rolesEnum = page.props.rolesEnum;

const leadStatusForm = useForm({
  modelType: 'Car',
  leadId: page.props.record.id,
  quote_uuid: page.props.record.uuid,
  assigned_to_user_id: page.props.record.advisor_id,
  leadStatus:  null,
//   leadStatus: page.props.record.quote_status_id || null,
  notes: page.props.record.notes || null,
  trans_code: page.props.record.transapp_code || null,
  lostReason: page.props.record.lost_reason_id || null,
});

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});
</script>

<template>
	<div>
		<Head title="Car Detail" />
		<div class="flex justify-between items-center flex-wrap gap-2">
			<h2 class="text-xl font-semibold">E-COM Detail</h2>
		</div>
		<x-divider class="my-4" />
		<!-- <x-modal v-model="modals.duplicate" size="lg" show-close backdrop>
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
            :rules="[isRequired]"
            placeholder="Select LOB For Duplication"
            class="w-full"
            multiple
          />
          <x-select
            v-model="leadDuplicateForm.lob_team_sub_selection"
            label="Reason"
            :rules="[isRequired]"
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
    <div
      v-if="!$page.props.can.isAdvisor"
      class="p-4 rounded shadow mb-6 bg-primary-50/50 saad"
    >
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
        <div
          v-if="!hasRole($page.props.rolesEnum.HealthWCUAdvisor)"
          class="w-full md:w-1/2 flex gap-2 items-end"
        >
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

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
        <div v-if="hasRole($page.props.rolesEnum.Engineering)" class="grid sm:grid-cols-2">
            <dt class="font-medium">ID</dt>
            <dd>{{ quote.id }}</dd>
        </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CDB ID</dt>
            <dd>{{ quote.code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CREATED DATE</dt>
            <dd>{{ quote.created_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SUBTEAM</dt>
            <dd>{{ quote.health_team_type }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">ADVISOR</dt>
            <dd>{{ quote.advisor_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SOURCE</dt>
            <dd>{{ quote.source }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST MODIFIED DATE</dt>
            <dd>{{ quote.updated_at }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PARENT CDB ID</dt>
            <dd>{{ quote.parent_duplicate_quote_id }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">IS ECOMMERCE</dt>
            <dd>{{ quote.is_ecommerce ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">IS EBP RENEWAL</dt>
            <dd>{{ quote.is_ebp_renewal ? 'Yes' : 'No' }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">RENEWAL BATCH</dt>
            <dd>{{ quote.renewal_batch }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LOST REASON</dt>
            <dd>{{ quote.lost_reason }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TRANSAPP CODE</dt>
            <dd>{{ quote.transapp_code }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DEVICE</dt>
            <dd>{{ quote.device }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Customer Profile</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">FIRST NAME</dt>
            <dd>{{ quote.first_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST NAME</dt>
            <dd>{{ quote.last_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MOBILE NUMBER</dt>
            <dd>{{ quote.mobile_no }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">EMAIL</dt>
            <dd>{{ quote.email }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">GENDER</dt>
            <dd>{{ genderText(quote.gender).value }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MARITAL STATUS</dt>
            <dd>{{ quote.marital_status_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NATIONALITY</dt>
            <dd>{{ quote.nationality_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DATE OF BIRTH</dt>
            <dd>{{ quote.dob }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">EMIRATE OF VISA</dt>
            <dd>{{ quote.emirate_of_your_visa_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MEMBER CATEGORY</dt>
            <dd>{{ quote.member_category_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SALARY BAND</dt>
            <dd>{{ quote.salary_band_id_text }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Quote Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">
              FOR WHOM DO YOU REQUIRE HEALTH INSURANCE?
            </dt>
            <dd>{{ quote.cover_for_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CURRENTLY INSURED WITH</dt>
            <dd>{{ quote.currently_insured_with_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TYPE OF PLAN</dt>
            <dd>{{ quote.plan_id }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
            <dd>{{ dateFormat(quote.next_followup_date) }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DETAILS</dt>
            <dd>{{ quote.details }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Additional Notes</dt>
            <dd>{{ quote.additional_notes }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">
          Last Year's Policy Details
        </h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY NUMBER</dt>
            <dd>{{ quote.previous_quote_policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY PREMIUM</dt>
            <dd>{{ quote.previous_quote_policy_premium }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY EXPIRY DATE</dt>
            <dd>{{ dateFormat(quote.previous_policy_expiry_date) }}</dd>
          </div>
        </dl>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div class="flex justify-between items-center mb-4">
        <h3 class="font-semibold text-primary-800 text-lg">
          Member Details
          <x-tag size="sm">{{ membersDetail.length || 0 }}</x-tag>
        </h3>
        <x-button @click.prevent="onAddMemberModal" size="sm" color="orange">
          Add Member
        </x-button>
      </div>

      <DataTable
        table-class-name="tablefixed compact"
        :headers="memberDetailsTable.columns"
        :items="membersDetail || []"
        show-index
        border-cell
        hide-rows-per-page
        hide-footer
      >
        <template #item-index="{ index }">
          <div>Member {{ index }}</div>
        </template>
        <template #item-gender="{ gender }">
          {{ genderText(gender).value }}
        </template>
        <template #item-dob="{ dob }">
          {{ dateFormat(dob) }}
        </template>
        <template #item-nationality="{ nationality }">
          {{ nationality?.text }}
        </template>
        <template #item-emirate="{ emirate }">
          {{ emirate?.text }}
        </template>
        <template #item-member_category_id="{ member_category_id }">
          {{ memberCategoryText(member_category_id).value }}
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

            <ComboBox
              v-model="memberForm.nationality_id"
              label="Nationality"
              :options="nationalityOptions"
              placeholder="Select Nationality"
              :single="true"
              :hasError="memberFieldReq.nationality"
            />

            <x-select
              v-model="memberForm.emirate_of_your_visa_id"
              label="Emirate of Visa"
              :options="emiratesOptions"
              :rules="[isRequired]"
              placeholder="Select Emirate of Visa"
              class="w-full"
            />

            <x-select
              v-model="memberForm.gender"
              label="Gender"
              :options="genderSelect"
              :rules="[isRequired]"
              placeholder="Select Gender"
              class="w-full"
            />

            <DatePicker
              v-model="memberForm.dob"
              label="DOB"
              :hasError="memberFieldReq.dob"
            />

            <x-select
              v-model="memberForm.member_category_id"
              label="Member Category"
              :options="memberCategoriesOptions"
              :rules="[isRequired]"
              placeholder="Select Member Category"
              class="w-full"
            />

            <x-select
              v-model="memberForm.salary_band_id"
              label="Salary Band"
              :options="salaryBandsOptions"
              placeholder="Select Salary Band"
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
    </div>

    <div class="p-4 rounded shadow mb-6 bg-primary-50/25">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
        <div class="w-full md:w-2/3">
          <x-textarea
            v-model="leadStatusForm.notes"
            type="text"
            label="Notes"
            placeholder="Lead Notes"
            class="w-full"
            :disabled="quote.quote_status_id == 15"
          />
        </div>
        <div class="w-full md:w-1/3">
          <div class="flex flex-col gap-4">
            <x-select
              v-model="leadStatusForm.leadStatus"
              label="Status"
              :options="leadStatusOptions"
              :disabled="quote.quote_status_id == 15"
              placeholder="Lead Status"
              class="w-full"
            />
            <x-input
              v-if="leadStatusForm.leadStatus == 15"
              v-model="leadStatusForm.trans_code"
              label="TransApp Code"
              placeholder="TransApp Code is required"
              class="w-full"
              :error="leadStatusForm.errors.trans_code"
            />
            <x-select
              v-if="leadStatusForm.leadStatus == 17"
              v-model="leadStatusForm.lostReason"
              label="Lost Reason"
              :options="
                lostReasons?.map(item => ({
                  value: item.id,
                  label: item.text,
                }))
              "
              placeholder="Lost Reason is required"
              class="w-full"
              :error="leadStatusForm.errors.lostReason"
            />
          </div>

          <div class="flex justify-end">
            <x-button
              class="mt-4"
              color="emerald"
              size="sm"
              :loading="leadStatusForm.processing"
              @click.prevent="onLeadStatus"
            >
              Change Status
            </x-button>
          </div>
        </div>
      </div>
    </div>

    <div class="p-4 rounded shadow mb-6 bg-white">
      <div>
        <h3 class="font-semibold text-primary-800 text-lg">E-COM Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>
      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PLAN NAME</dt>
            <dd>{{ ecomDetails.planName }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PROVIDER NAME</dt>
            <dd>{{ ecomDetails.providerName }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAYMENT STATUS</dt>
            <dd>{{ ecomDetails.paymentStatus }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PAID AT</dt>
            <dd>{{ ecomDetails.paidAt }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NETWORK</dt>
            <dd>{{ ecomDetails.network }}</dd>
          </div>
        <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TOTAL PRICE (with VAT)</dt>
            <dd>{{ fixedValue(ecomDetails.priceWithVAT) }}</dd>
        </div>
        </dl>
      </div>
    </div> -->

		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="text-sm">
				<dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">PREMIUM</dt>
						<dd>{{ record.premium ?? '' }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">PAID AT</dt>
						<dd>{{ record.paid_at ?? '' }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">PAYMENT STATUS</dt>
						<dd>{{ record.payment_status_id_text ?? '' }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">PROVIDER NAME</dt>
						<dd>{{ record.car_plan_provider_id_text ?? '' }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">PAYMENT METHOD</dt>
						<dd>
							{{
								record.payment_gateway === 'NGENIUS'
								? 'CREDIT CARD'
								: record.payment_gateway
							}}
						</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">PLAN NAME</dt>
						<dd>{{ record.plan_id_text }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">ECOMMERCE</dt>
						<dd>{{ record.is_ecommerce == 1 ? 'Yes' : 'No' }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">QUOTE LINK</dt>
						<dd>{{ record.quote_link ?? '' }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">ORDER REFERENCE</dt>
						<dd>{{ record.order_reference ?? '' }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">PAYMENT REFERENCE</dt>
						<dd>{{ record.payment_reference ?? '' }}</dd>
					</div>
				</dl>
				<div class="grid sm:grid-cols-1">
					<dt class="font-medium">ADDONS</dt>
					<dd>{{ record.payment_reference }}</dd>
				</div>
			</div>

			<!-- <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Customer Profile</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">FIRST NAME</dt>
            <dd>{{ quote.first_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">LAST NAME</dt>
            <dd>{{ quote.last_name }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MOBILE NUMBER</dt>
            <dd>{{ quote.mobile_no }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">EMAIL</dt>
            <dd>{{ quote.email }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">GENDER</dt>
            <dd>{{ genderText(quote.gender).value }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MARITAL STATUS</dt>
            <dd>{{ quote.marital_status_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NATIONALITY</dt>
            <dd>{{ quote.nationality_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DATE OF BIRTH</dt>
            <dd>{{ quote.dob }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">EMIRATE OF VISA</dt>
            <dd>{{ quote.emirate_of_your_visa_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">MEMBER CATEGORY</dt>
            <dd>{{ quote.member_category_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">SALARY BAND</dt>
            <dd>{{ quote.salary_band_id_text }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">Quote Details</h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">
              FOR WHOM DO YOU REQUIRE HEALTH INSURANCE?
            </dt>
            <dd>{{ quote.cover_for_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">CURRENTLY INSURED WITH</dt>
            <dd>{{ quote.currently_insured_with_id_text }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">TYPE OF PLAN</dt>
            <dd>{{ quote.plan_id }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
            <dd>{{ dateFormat(quote.next_followup_date) }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">DETAILS</dt>
            <dd>{{ quote.details }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">Additional Notes</dt>
            <dd>{{ quote.additional_notes }}</dd>
          </div>
        </dl>
      </div>

      <div class="mt-6">
        <h3 class="font-semibold text-primary-800">
          Last Year's Policy Details
        </h3>
        <x-divider class="mb-4 mt-1" />
      </div>

      <div class="text-sm">
        <dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY NUMBER</dt>
            <dd>{{ quote.previous_quote_policy_number }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY PREMIUM</dt>
            <dd>{{ quote.previous_quote_policy_premium }}</dd>
          </div>
          <div class="grid sm:grid-cols-2">
            <dt class="font-medium">PREVIOUS POLICY EXPIRY DATE</dt>
            <dd>{{ dateFormat(quote.previous_policy_expiry_date) }}</dd>
          </div>
        </dl>
      </div> -->
		</div>

		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">Car Details</h3>
				<div>
					<x-button class="mr-2" size="sm" color="#ff5e00" @click.prevent="openDuplicate">
						Duplicate Lead
					</x-button>

					<!-- <Link :href="route('health.index')" preserve-scroll>
              <x-button size="sm" color="primary" tag="div"> Health List </x-button>
            </Link> -->

					<Link :href="route('car.index')">
					<x-button size="sm" tag="div">Car List</x-button>
					</Link>
				</div>
			</div>
			<x-divider class="mb-4 mt-1" />
			<div class="text-sm">
				<dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CDB ID</dt>
						<dd>{{ record.code }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">BATCH</dt>
						<dd>{{ record.quote_batch_id_text }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">FIRST NAME</dt>
						<dd>{{ record.first_name }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">LAST NAME</dt>
						<dd>{{ record.last_name }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">DATE OF BIRTH</dt>
						<dd>{{ record.dob }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CUSTOMER AGE</dt>
						<dd>{{ record.customer_age }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">PHONE NUMBER</dt>
						<dd>{{ record.mobile_no }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">EMAIL</dt>
						<dd>{{ record.email }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">LEAD SOURCE</dt>
						<dd>{{ record.source }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">NATIONALITY</dt>
						<dd>{{ record.nationality_id_text }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">UAE LICENCE HELD FOR</dt>
						<dd>{{ record.uae_license_held_for_id_text }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">HOME COUNTRY DRIVING LICENSE HELD FOR</dt>
						<dd>{{ record.back_home_license_held_for_id_text ?? '' }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CAR MAKE</dt>
						<dd>{{ record.car_make_id_text }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CAR MODEL</dt>
						<dd>{{ record.car_model_id_text }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CYLINDER</dt>
						<dd>{{ record.cylinder }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">TRIM</dt>
						<dd>{{ record.trim }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CAR MODEL YEAR</dt>
						<dd>{{ record.year_of_manufacture }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">FIRST REGISTRATION DATE</dt>
						<dd>{{ record.year_of_first_registration }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CAR VALUE</dt>
						<dd>{{ record.car_value }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CAR VALUE (AT ENQUIRY)</dt>
						<dd>{{ record.car_value_tier }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">VEHICLE TYPE</dt>
						<dd>{{ record.vehicle_type_id_text }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">SEAT CAPACITY</dt>
						<dd>{{ record.seat_capacity }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">EMIRATE OF REGISTRATION</dt>
						<dd>{{ record.dob }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">TYPE OF CAR INSURANCE</dt>
						<dd>{{ record.current_insurance_status }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CURRENTLY INSURED WITH</dt>
						<dd>{{ record.currently_insured_with_text }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CLAIM HISTORY</dt>
						<dd>{{ record.claim_history_id_text }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">
							CAN YOU PROVIDE NO-CLAIMS LETTER FROM YOUR PREVIOUS INSURERS?
						</dt>
						<dd>{{ record.has_ncd_supporting_documents ? 'Yes' : 'No' }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CREATED DATE</dt>
						<dd>{{ record.created_at }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">ADVISOR ASSIGNED DATE</dt>
						<dd>{{ record.advisor_assigned_date }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">LEAD COST</dt>
						<dd>{{ record.cost_per_lead }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">FOLLOW UP DATE</dt>
						<dd>{{ record.next_followup_date }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">LAST MODIFIED DATE</dt>
						<dd>{{ record.updated_at }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">UPDATED BY</dt>
						<dd>{{ record.updated_by }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">ADDITIONAL NOTES</dt>
						<dd>{{ record.additional_notes }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">ADVISOR</dt>
						<dd>{{ record.advisor_id_text }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">ADVISOR/PROMO CODE</dt>
						<dd>{{ record.promo_code }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">DEVICE</dt>
						<dd>{{ record.device }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CALCULATED VALUE</dt>
						<dd>{{ record.calculated_value ?? '' }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">CREATED BY</dt>
						<dd>{{ record.created_by }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">PARENT CDB ID</dt>
						<dd>{{ record.parent_duplicate_quote_id ?? '' }}</dd>
					</div>
				</dl>
			</div>
			<x-divider class="mb-4 mt-4" />
			<div class="flex justify-end mb-4">
				<Link :href="route('car.edit', record.uuid)">
				<x-button size="sm" tag="div">Edit</x-button>
				</Link>
			</div>
		</div>

		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Last Year's Policy Details
				</h3>
			</div>
			<x-divider class="mb-4 mt-1" />
			<div class="text-sm">
				<dl class="grid md:grid-cols-2 gap-x-6 gap-y-4">
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">Renewal Batch#</dt>
						<dd>{{ record.renewal_batch }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">Previous Policy Number</dt>
						<dd>{{ record.previous_quote_policy_number ?? '' }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">Previous Policy Expiry Date</dt>
						<dd>{{ record.previous_policy_expiry_date ?? '' }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">Previous Policy Premium</dt>
						<dd>{{ record.previous_quote_policy_premium ?? '' }}</dd>
					</div>
					<template v-if="is(rolesEnum.Admin)">
						<div class="grid sm:grid-cols-2">
							<dt class="font-medium">Previous Import Code</dt>
							<dd>{{ record.renewal_import_code }}</dd>
						</div>
						<div class="grid sm:grid-cols-2">
							<dt class="font-medium"></dt>
							<dd></dd>
						</div>
					</template>					
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">Policy Number</dt>
						<dd>{{ record.policy_number }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">Renewal Expiry Date</dt>
						<dd>{{ record.renewal_expiry_date }}</dd>
					</div>
					<div class="grid sm:grid-cols-2">
						<dt class="font-medium">Lost reason</dt>
						<dd>{{ record.lost_reason }}</dd>
					</div>
				</dl>
			</div>
		</div>

		<div class="p-4 rounded shadow mb-6 bg-white">
			<div>
				<h3 class="font-semibold text-primary-800 text-lg">Lead Status</h3>
				<x-divider class="mb-4 mt-1" />
			</div>
			<div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
				<div class="w-full md:w-2/3">
					<x-textarea
						v-model="leadStatusForm.notes"
						type="text"
						label="Notes"
						placeholder="Lead Notes"
						class="w-full"
						:disabled="record.quote_status_id == 15"
					/>
				</div>
				<div class="w-full md:w-1/3">
					<div class="flex flex-col gap-4">
						<x-select
							v-model="leadStatusForm.leadStatus"
							label="Status"
							:options="leadStatusOptions"
							:disabled="record.quote_status_id == 15"
							placeholder="Lead Status"
							class="w-full"
						/>
					</div>

					<div class="flex justify-end">
						<x-button
							class="mt-4"
							color="emerald"
							size="sm"
							:loading="leadStatusForm.processing"
							@click.prevent="onLeadStatus"
						>
							Change Status
						</x-button>
					</div>
				</div>
			</div>
			</div>
	</div>
</template>
