<script setup>
import { computed } from "vue";

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
	quoteType: String,
  	paymentEntityModel: Object,
	displaySendPolicyButton: Number,
	isRenewalUser: Boolean,
	emailStatuses: Array,
	carQuotePlanAddons: Array,
});
const page = usePage();
const permissionEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;



const is = role => useHasRole(role);
const can = permission => useCan(permission);
const notification = useNotifications('toast');

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


const bookingDetailForm = useForm({
  requestType: 'New Business',
  bookingDate: '17-07-2023',
  invoiceDescription: 'ORI.MOTORFEI-T',
  mainClassInsurance: 'Motor',
  invoicePaymentStatus: '',
  subClass: 'Comprehensive',
  insurerInvoiceDate: '15-01-2023',
  brokerInvoiceNumber: 'IM-1234-N567892',
  insurerPremiumTaxInvoiceNumber: 'AM-1234-N567892',
  discount: '',
  insurerCommissionTaxInvoiceNumber: 'IM-1234-N567892',
  commissionPercentage: '12',
  commission: '0',
  vatOnCommission: '0.00',
  commissionhVatApplicable: '15.00',
  commissionIncludingVat: '0.0'  
});


const onSubmitBookingDetails = isValid => {
  if (!isValid) return;
  let url = `/quotes/car/post-sage-data`;
  let method = `post`;
  
  bookingDetailForm.submit(method, url, {
    preserveScroll: true,
    onSuccess: (response) => {
		//console.log('hafeez-'+JSON.stringify(response.message));
		console.log('hafeez-'+JSON.stringify(response));
      //activityForm.reset();
      notification.success({
        title: response.message,
        position: 'top',
      });
    }, onError: (errors) => {
      //console.error('Form submission error:', errors);
      notification.error({
        title: 'Booking detail error, Enter Insurer Premium Tax Invoice Number',
        position: 'top',
      });
    },
    onFinish: () => {
      //modals.activity = false;
    },
  });
};


const paymentDetailsTable = reactive({
  isLoading: false,
  columns: [
    {
      text: 'Payment ID',
      value: 'code',
    },
    {
      text: 'Payment Status',
      value: 'payment_status',
    },
    {
      text: 'Plan Name',
      value: 'plan_name',
    },
    {
      text: 'Captured Amount',
      value: 'captured_amount',
    },
    {
      text: 'Status Change Date',
      value: 'created_at',
    },
    {
      text: 'Captured At',
      value: 'captured_at',
    },
    {
      text: 'Authorized At',
      value: 'authorized_at',
    },
    {
      text: 'Payment method',
      value: 'payment_method_name',
    },
    {
      text: 'Reference',
      value: 'reference',
    },
    
    {
      text: 'Action',
      value: 'action',
    },
  ],
});

const availablePlansTable = reactive({
	columns: [
		{ text: 'Provider Name', value: 'providerName' },
		{ text: 'Plan Name', value: '' },
		{ text: 'Repair Type', value: '' },
		{ text: 'Insurer Quote No.', value: '' },
		{ text: 'TPL Limit', value: '' },
		{ text: 'Car Trim', value: '' },
		{ text: 'PAB cover', value: '' },
		{ text: 'Roadside assistance', value: '' },
		{ text: 'Oman cover TPL', value: '' },
		{ text: 'Actual Premium', value: '' },
		{ text: 'Discounted Premium', value: '' },
		{ text: 'Premium with VAT.', value: '' },
		{ text: 'Excess', value: '' },
		{ text: 'Action', value: '' },
	]
})

const documentsTable = reactive({
	columns: [
		{ text: 'Document Type', value: 'providerName' },
		{ text: 'Document Name', value: '' },
		{ text: 'Created At', value: '' },
		{ text: 'Created By', value: '' },
		{ text: 'Action', value: '' },
	]
})

const documentsTableItems = computed(() => {
	return page.props.quoteDocuments.map(doc => {
		return {
			document_type_text: doc.document_type_text
		}
	})
})

const notesForCustomer = reactive({
	columns: [
		{ text: 'Id', value: '' },
		{ text: 'Description', value: '' },
		{ text: 'Created At', value: '' },
		{ text: 'Created By', value: '' },
	]
})

const leadActivities = reactive({
	columns: [
		{ text: 'Title', value: '' },
		{ text: 'Ref-ID', value: '' },
		{ text: 'Client Name', value: '' },
		{ text: 'Created By', value: '' },
	]
})

const emailStatusTable = reactive({
	columns: [
		{ text: 'Id', value: 'id' },
		{ text: 'Email Subject', value: 'email_subject' },
		{ text: 'Email Address', value: 'email_address' },
		{ text: 'Status', value: 'email_status' },
		{ text: 'Reason', value: 'reason' },
		{ text: 'Template Id', value: 'template_id' },
		{ text: 'Customer Id', value: 'customer_id' },
		{ text: 'Created At', value: 'created_at' },
		{ text: 'Updated At', value: 'updated_at' },
	]
})

const availablePlansItems = computed(() => {
	if (! Array.isArray(page.props.listQuotePlans)) {
		return [];
	}
	return page.props.listQuotePlans.filter(plan => plan.id).map(plan => {
		return {
			providerName: plan.providerName,
			
		}
	})
})

const paymentItems = computed(() => {
  return page.props.payments.map(payment => {
    return {
      code: payment.code,
      payment_status: payment.payment_status.text,
      plan_name: page.props.plan.text,
      captured_amount: payment.captured_amount,
      created_at: payment.payment_status_logs.length > 0 ? payment.payment_status_logs.at(-1).created_at : null,
      captured_at: payment.captured_at,
      authorized_at: payment.authorized_at,
      payment_method_name: payment.payment_method.name,
      reference: payment.reference
    }
  })
})

const leadStatusOptions = computed(() => {
  return page.props.leadStatuses.map(status => ({
    value: status.id,
    label: status.text,
  }));
});

const assumptionsForm = useForm({
  cylinder: page.props.record.cylinder || null,
  seat_capacity: page.props.record.seat_capacity || null,
  vehicleType: page.props.record.vehicle_type_id || null,
  is_modified: page.props.paymentEntityModel.is_modified || null,
  is_bank_financed: page.props.paymentEntityModel.is_bank_financed || null,
  is_gcc_standard: page.props.paymentEntityModel.is_gcc_standard ||null,
  current_insurance_status: page.props.record.current_insurance_status || null,
  year_of_first_registration: page.props.record.year_of_first_registration || null,
});

const vehicleTypeOptions = computed(() => {
  return page.props.vehicleTypes.map(type => ({
    value: type.id,
    label: type.text,
  }));
});

const isOptions = computed(() => {
  return [
        { value: 0, label: 'No' },
        { value: 1, label: 'Yes' },
      ];
});
const policyDetailsForm = useForm({
  policy_number: page.props.record.policy_number || null,
  policy_start_date: page.props.record.policy_start_date || null,
  previous_policy_expiry_date: page.props.record.previous_policy_expiry_date || null,
  premium: page.props.record.premium || null,
});

</script>

<template>
	<div>
		<Head title="Car Detail" />
		<div class="flex justify-between items-center flex-wrap gap-2">
			<h2 class="text-xl font-semibold">E-COM Detail</h2>
		</div>
		<x-divider class="my-4" />

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
				<div class="grid sm:grid-cols-1 mt-3">
					<dt class="font-medium mb-3">ADDONS</dt>
					<dd>
						<table style="width: 100%;">
							<thead></thead>
							<tbody>
								<tr v-for="(addon, index) in carQuotePlanAddons" :key="addon" class="flex justify-between w-100">
									<td style="width: 20%;">{{ index + 1 }}</td>
									<td style="width: 20%;">{{ addon.car_addon_text }}</td>
									<td style="width: 20%;">{{ addon.car_addon_option_value }}</td>
									<td style="width: 20%;">{{ addon.car_quote_request_addon_price == 0 ? 'Free' : addon.car_quote_request_addon_price }}</td>
									<td>
										<input v-if="addon.car_quote_request_addon_price" type="checkbox" disabled checked class="car-quote-ecom-non-free-plan-check">
										<input v-else type="checkbox" checked class="car-quote-ecom-free-plan-check" disabled>
									</td>
								</tr>
							</tbody>
						</table>
					</dd>
				</div>
			</div>
		</div>

		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">Car Details </h3>
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
    
		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Payments
					<x-tag size="sm">{{ payments.length || 0 }}</x-tag>
				</h3>
				<x-button v-if="is(rolesEnum.PA) && $page.props.plan && (!can(permissionEnum.ApprovePayments) && can(permissionEnum.PaymentsCreate))" @click.prevent="onAddPaymentModal" size="sm" color="orange">
					Add Payment
				</x-button>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				:headers="paymentDetailsTable.columns"
				:items="paymentItems || []"
				show-index
				border-cell
				hide-rows-per-page
				hide-footer
			>
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
		</div> 

		<div class="p-4 rounded shadow mb-6 bg-white">
			<div>
				<h3 class="font-semibold text-primary-800 text-lg">Assumptions</h3>
				<x-divider class="mb-4 mt-1" />
			</div>
			<div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
				<div class="w-full md:w-1/2">
					<x-textarea
						v-model="assumptionsForm.cylinder"
						type="text"
						label="cylinder"
						placeholder="cylinder"
						class="w-full"
						disabled="true"

					/>
				</div>
				<div class="w-full md:w-1/2">
					<x-textarea
						v-model="assumptionsForm.seat_capacity"
						type="text"
						label="Seat Capacity"
						placeholder="Seat Capacity"
						class="w-full"
						disabled="true"
					/>
				</div>
			</div>
			<div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
				<div class="w-full md:w-1/2">
					<div class="flex flex-col gap-4">
						<x-select
							v-model="assumptionsForm.vehicleType"
							label="Vehicle Body Type"
							:options="vehicleTypeOptions"
							placeholder="Vehicle Body Type"
							class="w-full"
							disabled="true"
						/>
					</div>
				</div>
			<div class="w-full md:w-1/2">
						<div class="flex flex-col gap-4">
							<x-select
								v-model="assumptionsForm.is_modified"
								label="Is Vehicle modified?"
								:options="isOptions"
								placeholder="Is Modified"
								class="w-full"
				disabled="true"
							/>
						</div>
					</div>
		</div>
		<div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
			<div class="w-full md:w-1/2">
						<div class="flex flex-col gap-4">
							<x-select
								v-model="assumptionsForm.is_bank_financed"
								label="Is Bank Financed"
								:options="isOptions"
								placeholder="Is Bank Financed"
								class="w-full"
				disabled="true"
							/>
						</div>

					</div>
			<div class="w-full md:w-1/2">
						<div class="flex flex-col gap-4">
							<x-select
								v-model="assumptionsForm.is_gcc_standard"
								label="Is GCC Standard?"
								:options="isOptions"
								placeholder="Is GCC Standard"
								class="w-full"
				disabled="true"
							/>
						</div>
					</div>
		</div>
		</div>

		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Available Plans
				<x-tag size="sm">{{ availablePlansItems.length || 0 }}</x-tag>
				</h3>
				<div>
					<x-button @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
						Send OCB Email to Customer
					</x-button>
					<x-button @click.prevent="onAddPaymentModal" size="sm" color="emerald" class="mr-2">
						Download PDF
					</x-button>
					<x-button @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
						Create Quote
					</x-button>
					<x-button @click.prevent="onAddPaymentModal" size="sm" color="emerald" >
						Copy Link
					</x-button>
				</div>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				:headers="availablePlansTable.columns"
				:items="availablePlansItems || []"
				show-index
				border-cell
				fixed-checkbox
				hide-rows-per-page
				hide-footer
			>
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
		</div> 

		<div class="p-4 rounded shadow mb-6 bg-white" v-if="isQuoteDocumentEnabled">
			<div>
				<h3 class="font-semibold text-primary-800 text-lg">Policy Details</h3>
				<x-divider class="mb-4 mt-1" />
			</div>
			<div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
				<div class="w-full md:w-1/2">
					<x-textarea
						v-model="policyDetailsForm.policy_number"
						type="text"
						label="Policy Number"
						placeholder="Policy Number"
						class="w-full"
						disabled="true"
					/>
				</div>
				<div class="w-full md:w-1/2">
					<x-textarea
						v-model="policyDetailsForm.policy_issuance_date"
						type="text"
						label="Issuance Date"
						placeholder="Issuance Date"
						class="w-full"
						disabled="true"
					/>
				</div>
			</div>
			<div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
				<div class="w-full md:w-1/2">
					<x-textarea
						v-model="policyDetailsForm.policy_start_date"
						type="text"
						label="Policy Start Date"
						placeholder="Policy Start Date"
						class="w-full"
						disabled="true"
					/>
				</div>
				<div class="w-full md:w-1/2">
					<x-textarea
						v-model="policyDetailsForm.previous_policy_expiry_date"
						type="text"
						label="Expiry Date"
						placeholder="Expiry Date"
						class="w-full"
						disabled="true"
					/>
				</div>
			</div>
			<div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
				<div class="w-full md:w-1/2">
					<x-textarea
						v-model="policyDetailsForm.premium"
						type="text"
						label="premium"
						placeholder="premium"
						class="w-full"
						disabled="true"
					/>
				</div>
			</div>
		</div>

		<div class="p-4 rounded shadow mb-6 bg-white" v-if="isQuoteDocumentEnabled">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Documents
				</h3>
				<div>
					<template v-if="! can(permissionEnum.ApprovePayments) && ! is(rolesEnum.PA)">
						<Link :href="`quotes/${quoteType}/${record.uuid}/documents`" class="btn btn-primary btn-sm" style="float:right;">Upload Documents</Link>

						<template v-if="displaySendPolicyButton">
							<!-- <a class="btn btn-sm btn-primary" style="float:right;" data-quote-type="{{ $quoteType }}"
                            data-quote-uuid="{{ $record->uuid }}" onclick="sendQuoteDocumentsToCustomer(this)">Send Policy</a> -->
						</template>

					</template>
					<x-button v-if="record.payment_status_id === permissionEnum.AUTHORISED && ! is(rolesEnum.PA)" @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
						Copy upload Link
					</x-button>
				</div>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				:headers="documentsTable.columns"
				:items="documentsTableItems || []"
				show-index
				border-cell
				fixed-checkbox
				hide-rows-per-page
				hide-footer
			>
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
		</div> 

		<div class="p-4 rounded shadow mb-6 bg-white">
			<div>
				<h3 class="font-semibold text-primary-800 text-lg">Booking Details</h3>
				<x-divider class="mb-4 mt-1" />
			</div>
			<x-form @submit="onSubmitBookingDetails" :auto-focus="false">
			
				<div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.requestType"
							type="text"
							label="Request Type"
							placeholder="Request Type"
							class="w-full"
							:readonly="true"					
						/>
					</div>
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.bookingDate"
							type="text"
							label="Booking Date"
							placeholder="Booking Date"
							class="w-full"
							:readonly="true"					
						/>
					</div>
				</div>
				<div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.invoiceDescription"
							type="text"
							label="Invoice Description"
							placeholder="Invoice Description"
							class="w-full"
							:readonly="true"						
						/>
					</div>
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.mainClassInsurance"
							type="text"
							label="Main Class of Insurance"
							placeholder="Main Class of Insurance"
							class="w-full"
							:readonly="true"					
						/>
					</div>
				</div>
				<div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.invoicePaymentStatus"
							type="text"
							label="Invoice Payment Status"
							placeholder="Invoice Payment Status"
							class="w-full"
						/>
					</div>
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.subClass"
							type="text"
							label="Sub Class"
							placeholder="Sub Class"
							class="w-full"						
						/>
					</div>
				</div>
				<div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.insurerInvoiceDate"
							type="text"
							label="Insurer Invoice Date"
							placeholder="Insurer Invoice Date"
							class="w-full"						
						/>
					</div>
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.brokerInvoiceNumber"
							type="text"
							label="Broker Invoice Number"
							placeholder="Broker Invoice Number"
							class="w-full"						
						/>
					</div>
				</div>
				<div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.insurerPremiumTaxInvoiceNumber"
							type="text"
							label="Insurer Premium Tax Invoice Number"
							placeholder="Insurer Premium Tax Invoice Number"
							class="w-full"						
						/>
					</div>
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.discount"
							type="text"
							label="Discount"
							placeholder="Discount"
							class="w-full"						
						/>
					</div>
				</div>
				<div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.insurerCommissionTaxInvoiceNumber"
							type="text"
							label="Insurer Commission Tax Invoice Number"
							placeholder="Insurer Commission Tax Invoice Number"
							class="w-full"						
						/>
					</div>
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.commissionPercentage"
							type="text"
							label="Commission(%)"
							placeholder="Commission(%)"
							class="w-full"						
						/>
					</div>
				</div>
				<div class="flex flex-wrap md:flex-nowrap gap-6 w-full pb-5">
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.commission"
							type="text"
							label="Commission (VAT not applicable)"
							placeholder="Commission (VAT not applicable)"
							class="w-full"						
						/>
					</div>
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.vatOnCommission"
							type="text"
							label="VAT on Commission"
							placeholder="VAT on Commission"
							class="w-full"						
						/>
					</div>
				</div>
				<div class="flex flex-wrap md:flex-nowrap gap-6 w-full">
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.commissionhVatApplicable"
							type="text"
							label="Commission (VAT applicable)"
							placeholder="Commission (VAT applicable)"
							class="w-full"						
						/>
					</div>
					<div class="w-full md:w-2/2">
						<x-input
							v-model="bookingDetailForm.commissionIncludingVat"
							type="text"
							label="Commission (incld VAT)"
							placeholder="Commission (incld VAT)"
							class="w-full"						
						/>
					</div>
				</div>

				<div class="flex flex-wrap md:flex-nowrap gap-6 w-full">				
					<div class="w-full">
						<div class="flex justify-end">
							<x-button
								class="mt-4"
								color="orange"
								size="sm"
								:loading="bookingDetailForm.processing"
								type="submit"
							>
								Send Policy
							</x-button>
						</div>
					</div>
				</div>
			</x-form>
    	</div>












		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Email Status
				</h3>
				<div>
					<template v-if="! can(permissionEnum.ApprovePayments) && ! is(rolesEnum.PA)">
						<Link :href="`quotes/${quoteType}/${record.uuid}/documents`" class="btn btn-primary btn-sm" style="float:right;">Upload Documents</Link>

						<template v-if="displaySendPolicyButton">
							<!-- <a class="btn btn-sm btn-primary" style="float:right;" data-quote-type="{{ $quoteType }}"
                            data-quote-uuid="{{ $record->uuid }}" onclick="sendQuoteDocumentsToCustomer(this)">Send Policy</a> -->
						</template>

					</template>
					<x-button v-if="record.payment_status_id === permissionEnum.AUTHORISED && ! is(rolesEnum.PA)" @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
						Copy upload Link
					</x-button>
				</div>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				:headers="emailStatusTable.columns"
				:items="emailStatuses || []"
				show-index
				border-cell
				fixed-checkbox
				hide-rows-per-page
				hide-footer
			>
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
		</div> 

		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Notes for Customer
				</h3>
				<div>
					<template v-if="! can(permissionEnum.ApprovePayments) && ! is(rolesEnum.PA)">
						<x-button @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
							Send Notes to Customer
						</x-button>
					</template>
				</div>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				:headers="notesForCustomer.columns"
				:items="emailStatuses || []"
				show-index
				border-cell
				fixed-checkbox
				hide-rows-per-page
				hide-footer
			>
				<!-- <template #item-action="item">
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
				</template> -->
			</DataTable>
		</div> 

		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Lead Activities
				</h3>
				<div>
					<template v-if="! can(permissionEnum.ApprovePayments) && ! is(rolesEnum.PA)">
						<x-button @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
							Add Activity
						</x-button>
					</template>					
				</div>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				:headers="leadActivities.columns"
				:items="activities || []"
				show-index
				border-cell
				fixed-checkbox
				hide-rows-per-page
				hide-footer
			>
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
		</div> 
		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Customer Additional Contacts
				</h3>
				<div>
					<template v-if="! can(permissionEnum.ApprovePayments) && ! is(rolesEnum.PA)">
						<Link :href="`quotes/${quoteType}/${record.uuid}/documents`" class="btn btn-primary btn-sm" style="float:right;">Upload Documents</Link>

						<template v-if="displaySendPolicyButton">
							<!-- <a class="btn btn-sm btn-primary" style="float:right;" data-quote-type="{{ $quoteType }}"
                            data-quote-uuid="{{ $record->uuid }}" onclick="sendQuoteDocumentsToCustomer(this)">Send Policy</a> -->
						</template>

					</template>
					<x-button v-if="record.payment_status_id === permissionEnum.AUTHORISED && ! is(rolesEnum.PA)" @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
						Copy upload Link
					</x-button>
				</div>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				:headers="emailStatusTable.columns"
				:items="emailStatuses || []"
				show-index
				border-cell
				fixed-checkbox
				hide-rows-per-page
				hide-footer
			>
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
		</div> 
		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Lead History
				</h3>
				<div>
					<template v-if="! can(permissionEnum.ApprovePayments) && ! is(rolesEnum.PA)">
						<Link :href="`quotes/${quoteType}/${record.uuid}/documents`" class="btn btn-primary btn-sm" style="float:right;">Upload Documents</Link>

						<template v-if="displaySendPolicyButton">
							<!-- <a class="btn btn-sm btn-primary" style="float:right;" data-quote-type="{{ $quoteType }}"
                            data-quote-uuid="{{ $record->uuid }}" onclick="sendQuoteDocumentsToCustomer(this)">Send Policy</a> -->
						</template>

					</template>
					<x-button v-if="record.payment_status_id === permissionEnum.AUTHORISED && ! is(rolesEnum.PA)" @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
						Copy upload Link
					</x-button>
				</div>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				:headers="emailStatusTable.columns"
				:items="emailStatuses || []"
				show-index
				border-cell
				fixed-checkbox
				hide-rows-per-page
				hide-footer
			>
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
		</div> 
		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Audit Logs
				</h3>
				<div>
					<template v-if="! can(permissionEnum.ApprovePayments) && ! is(rolesEnum.PA)">
						<Link :href="`quotes/${quoteType}/${record.uuid}/documents`" class="btn btn-primary btn-sm" style="float:right;">Upload Documents</Link>

						<template v-if="displaySendPolicyButton">
							<!-- <a class="btn btn-sm btn-primary" style="float:right;" data-quote-type="{{ $quoteType }}"
                            data-quote-uuid="{{ $record->uuid }}" onclick="sendQuoteDocumentsToCustomer(this)">Send Policy</a> -->
						</template>

					</template>
					<x-button v-if="record.payment_status_id === permissionEnum.AUTHORISED && ! is(rolesEnum.PA)" @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
						Copy upload Link
					</x-button>
				</div>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				:headers="emailStatusTable.columns"
				:items="emailStatuses || []"
				show-index
				border-cell
				fixed-checkbox
				hide-rows-per-page
				hide-footer
			>
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
		</div> 
	</div>
</template>
