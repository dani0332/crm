<script setup>
import { computed } from "vue";
import LazyDocumentUploader from '../Partials/QuoteDocuments.vue';
import LazyAvailablePlan from './../Partials/AvailablePlans.vue';
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
	carPlanFeaturesCodeEnum: Object,
	carPlanExclusionsCodeEnum: Object,
	carPlanAddonsCodeEnum: Object,
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
	access: Object,
	record: Object,
	quoteType: String,
  	paymentEntityModel: Object,
	displaySendPolicyButton: Number,
	isRenewalUser: Boolean,
	emailStatuses: Array,
	carQuotePlanAddons: Array,
	notesForCustomers:Object,
	websiteURL: String
});
const page = usePage();
const notification = useNotifications('toast');

const permissionEnum = page.props.permissionsEnum;
const rolesEnum = page.props.rolesEnum;

const hasRole = role => useHasRole(role);
const can = permission => useCan(permission);
const { isRequired, isEmail, isNumber, isMobile } = useRules();

const leadStatusForm = useForm({
  modelType: 'Car',
  leadId: page.props.record.id,
  quote_uuid: page.props.record.uuid,
  assigned_to_user_id: page.props.record.advisor_id,
  leadStatus: page.props.record.quote_status_id || null,
  notes: page.props.record.notes || null,
  trans_code: page.props.record.transapp_code || null,
  lostReason: page.props.record.lost_reason_id || null,
});

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
const coreInsurer = ['AXA', 'OIC', 'TM', 'QIC', 'RSA'];
const selectedPlans = [];
const selectedPlan = ref({});

const availablePlansTable = reactive({
	columns: [
		{ text: 'Provider Name', value: 'providerName' },
		{ text: 'Plan Name', value: 'name' },
		{ text: 'Repair Type', value: 'repairType' },
		{ text: 'Insurer Quote No.', value: 'insurerQuoteNo' },
		{ text: 'TPL Limit', value: 'benefits' },
		{ text: 'Car Trim', value: 'insurerTrimText' },
		{ text: 'PAB cover', value: 'addons' },
		{ text: 'Roadside assistance', value: 'roadSideAssistance' },
		{ text: 'Oman cover TPL', value: 'omanCoverTPL' },
		{ text: 'Actual Premium', value: 'actualPremium' },
		{ text: 'Discounted Premium', value: 'discountPremium' },
		{ text: 'Premium with VAT.', value: 'premiumWithVat' },
		{ text: 'Excess', value: 'excess' },
		{ text: 'Action', value: 'action' },
	]
})

const documentsTable = reactive({
	columns: [
		{ text: 'Document Type', value: 'document_type_text' },
		{ text: 'Document Name', value: 'document_name_text' },
		{ text: 'Created At', value: 'created_at' },
		{ text: 'Created By', value: 'created_by' },
		{ text: 'Action', value: 'action' },
	]
})

const documentsTableItems = computed(() => {
	return page.props.quoteDocuments.map(doc => {
		return {
			document_type_text: doc.document_type_text.length > 0 ? doc.document_type_text : "",
			document_name_text: doc.doc_name,
			created_at: doc.created_at,
			created_by: doc.createdBy ? doc.createdBy.name : "",
		}
	})
})

const notesForCustomersTable = reactive({
	columns: [
		{ text: 'Id', value: 'id' },
		{ text: 'Description', value: 'description' },
		{ text: 'Created At', value: 'created_at' },
		{ text: 'Created By', value: 'created_by' },
	]
})

const notesForCustomersTableItems = computed(() => {
	return page.props.notesForCustomers.map(notesForCustomer => {
		return {
			id: notesForCustomer.id,
			description: notesForCustomer.description,
			created_at: notesForCustomer.created_at,
			created_by: notesForCustomer.createdby ? notesForCustomer.createdby.name : "",
		}
	})
})

const leadActivities = reactive({
	columns: [
		{ text: 'Title', value: 'title' },
		{ text: 'Client Name', value: 'client_name' },
		{ text: 'Followup Date', value: 'due_date' },
		{ text: 'Assigned To', value: 'assignee' },
		{ text: 'Done', value: 'status', width: 60, align: 'center' },
		{ text: 'Action', value: 'action' },

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

const customerAdditionalContactsTable = reactive({
	columns: [
		{ text: 'Type', value: 'key' },
		{ text: 'Value', value: 'value' },
		{ text: 'Created At', value: 'created_at' },
		{text: 'Action', value: 'action' },
	]
})

// history data
const historyData = ref(null);
const historyLoading = ref(false);


const onLoadHistoryData = async () => {
  historyLoading.value = true;
  const res = await fetch(
    `/quotes/getLeadHistory?modelType=car&recordId=${page.props.paymentEntityModel.id}`,
  );
  const finalRes = await res.json();
  historyData.value = finalRes;
  historyLoading.value = false;
};

const historyDataTable = [
  { text: 'Modified At', value: 'ModifiedAt' },
  { text: 'Modified By', value: 'ModifiedBy' },
  { text: 'Lead Status', value: 'NewStatus' },
  { text: 'Advisor', value: '' },
  { text: 'Notes', value: 'NewNotes' },
];

const availablePlansItems = computed(() => {
	if (! Array.isArray(page.props.listQuotePlans)) {
		return [];
	}
	return typeof page.props.listQuotePlans !== 'string' ? page.props.listQuotePlans : [];
})

const totalPriceVAT = computed(() => {
	let vat = 0;
	availablePlansItems?.value.forEach(item => {
		item.addons.forEach(addon => {
			addon.carAddonOption.forEach(option => {
				if (option.isSelected && option.price != 0) {
					vat += option.price + option.vat;
				}
			})
		})		
	})
	return vat;
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

const rules = {
  isRequired: v => !!v || 'This field is required',
};

const modals = reactive({
  duplicate: false,
  doc: false,
  addContact: false,
  contactPrimaryConfirm: false,
  contactDeleteConfirm:false,
  activity:false,
  activityConfirm:false,
  notes:false,
  plan: false
});

const confirmData = reactive({
  contactPrimary: null,
});

const leadDuplicateForm = useForm({
  modelType: 'car',
  parentType: 'car',
  entityId: page.props.record.id,
  entityCode: page.props.record.code,
  entityUId: page.props.record.uid,
  lob_team: [],
  lob_team_sub_selection: null,
});

const openDuplicate = () => {
  modals.duplicate = true;
  leadDuplicateForm.reset();
};

const onCreateDuplicate = isValid => {
  if (!isValid) return;
  leadDuplicateForm.post('/quotes/createDuplicate', {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Quote duplicated successfully',
        position: 'top',
      });
    },
    onFinish: () => {
      modals.duplicate = false;
    },
  });
};

const memberCategoryText = memberCategoryId =>
  computed(() => {
    return page.props.memberCategories.find(
      category => category.id === memberCategoryId,
    )?.text;
  });

const memberDataDocs = membersDetail => {
  return membersDetail
    .map(member => ({
      id: member.id,
      name: memberCategoryText(member.member_category_id).value,
    }))
    .filter(member => member.name !== undefined);
};
const contactLoader = ref(false);

const additionalContact = useForm({
  id: null,
  additional_contact_type: null,
  additional_contact_val: null,
  quote_id: page.props.record.id,
  customer_id: page.props.record.customer_id,
  quote_type: 'car',
});

const onAdditionalContactSubmit = isValid => {
  if (!isValid) return;
  additionalContact
    .transform(data => ({
      ...data,
      isInertia: true,
    }))
    .post(`/customer-additional-contact/add`, {
      preserveScroll: true,
      onSuccess: () => {
        additionalContact.reset();
        notification.success({
          title: 'Additional Contact Added',
          position: 'top',
        });
      },
      onError: err => {
        notification.error({ title: err.error, position: 'top' });
      },
      onFinish: () => {
        modals.addContact = false;
      },
    });
};

const additionalContactPrimary = data => {
  modals.contactPrimaryConfirm = true;
  confirmData.contactPrimary = data;
};

const additionalContactDelete = id => {
  modals.contactDeleteConfirm = true;
  confirmDeleteData.contact = id;
};

const confirmDeleteData = reactive({
  contact: null,
  activity: null,
});

const additionalContactPrimaryConfirmed = () => {
  const isEmail = confirmData.contactPrimary.key === 'email';
  router.post(
    `/customer-additional-contact/${
      isEmail ? confirmData.contactPrimary.id : 0
    }/make-primary`,
    {
      isInertia: true,
      quote_id: page.props.record.id,
      key: confirmData.contactPrimary.key,
      value: confirmData.contactPrimary.value,
      quote_type: 'car',
    },
    {
      preserveScroll: true,
      onBefore: () => {
        contactLoader.value = true;
      },
      onSuccess: () => {
        notification.success({
          title: 'Primary Contact Updated',
          position: 'top',
        });
      },
      onFinish: () => {
        contactLoader.value = false;
        modals.contactPrimaryConfirm = false;
      },
    },
  );
};

const additionalContactDeleteConfirmed = () => {
  router.post(
    `/customer-additional-contact/${confirmDeleteData.contact}/delete`,
    {
      isInertia: true,
    },
    {
      preserveScroll: true,
      onBefore: () => {
        contactLoader.value = true;
      },
      onSuccess: () => {
        notification.error({
          title: 'Additional Contact Deleted',
          position: 'top',
        });
      },
      onFinish: () => {
        contactLoader.value = false;
        modals.contactDeleteConfirm = false;
      },
    },
  );
};

const advisorOptions = computed(() => {
  return page.props.advisors.map(advisor => ({
    value: advisor.id,
    label: advisor.name,
  }));
});

const activityActionEdit = ref(false);

const activityForm = useForm({
  entityUId: page.props.record.uuid,
  entityId: page.props.record.id,
  modelType: 'Car',
  parentType: 'Car',
  quoteType: 1,
  title: null,
  description: null,
  due_date: null,
  assignee_id: null,
  status: null,
  activity_id: null,
  uuid: null,
});

const addActivity = () => {
  activityForm.reset();
  activityActionEdit.value = false;
  modals.activity = true;
};

const onActivitySubmit = isValid => {
  if (!isValid) return;
  if (activityActionEdit.value) {
    activityForm.post(`/activities/${activityForm.uuid}/update`, {
      preserveScroll: true,
      onSuccess: () => {
        activityForm.reset();
        notification.success({
          title: 'Activity Updated',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.activity = false;
      },
    });
  } else {
    activityForm.post(`/activities/create-activity`, {
      preserveScroll: true,
      onSuccess: () => {
        activityForm.reset();
        notification.success({
          title: 'Activity Added',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.activity = false;
      },
    });
  }
};

const onActivityStatusUpdate = id => {
  activityForm.activity_id = id;
  activityForm.post(route('activities.updateStatus'), {
    preserveScroll: true,
    onSuccess: () => {
      notification.success({
        title: 'Lead Activity Done',
        position: 'top',
      });
    },
  });
};

const activityEdit = data => {
  activityActionEdit.value = true;
  modals.activity = true;
  activityForm.activity_id = data.id;
  activityForm.uuid = data.uuid;
  activityForm.title = data.title;
  activityForm.description = data.description;
  activityForm.due_date = data.due_date
    ? data.due_date.split(' ')[0].split('-').reverse().join('-') +
      'T' +
      data.due_date.split(' ')[1]
    : null;
  activityForm.assignee_id = data.assignee_id;
  activityForm.status = data.status;
};

const activityDelete = id => {
  modals.activityConfirm = true;
  confirmDeleteData.activity = id;
};

const activityDeleteConfirmed = () => {
  router.post(
    `/activities/${confirmDeleteData.activity}/delete`,
    {
      isInertia: true,
      quote_uuid: page.props.record.uuid,
    },
    {
      preserveScroll: true,
      onSuccess: () => {
        notification.error({
          title: 'Activity Deleted',
          position: 'top',
        });
      },
      onFinish: () => {
        modals.activityConfirm = false;
      },
    },
  );
};

const notesForm = useForm({

	quote_id: page.props.record.id,
	quote_type_id:page.props.quoteTypeId,	
	quote_uuid: page.props.record.uuid,
	customer_name: page.props.record.first_name,
	customer_email: page.props.record.email,
	quote_cdb_id: page.props.record.code,
	description:null,
});

const addNotes = () => {
  notesForm.reset();
  modals.notes = true;
};

const onNoteSubmit = isValid => {
  if (!isValid) return;
  notesForm
    .transform(data => ({
      ...data,
      isInertia: true,
    }))
    .post(`/quotes/car/addNoteForCustomer`, {
      preserveScroll: true,
      onSuccess: () => {
        notesForm.reset();
        notification.success({
          title: 'Note Send To Customer',
          position: 'top',
        });
      },
      onError: err => {
        notification.error({ title: err.error, position: 'top' });
      },
      onFinish: () => {
        modals.notes = false;
      },
    });
};
const { copy, copied } = useClipboard();
const copyPlanURL = (item) => {
	var paymentLink = `${page.props.websiteURL}/car-insurance/quote/${page.props.record.uuid}/payment/?providerCode=${item.providerCode}&planId=${item.id}`;
	copy(paymentLink);
  	if (copied)
		notification.success({
			title: 'Link copied to clipboard',
			position: 'top',
		});
}

const selectPlan = (item) => {
	selectedPlan.value = item;
  	modals.plan = true;
}
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
		<x-modal v-model="modals.duplicate" size="lg" show-close backdrop>
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
				<!-- <x-select
					v-model="leadDuplicateForm.lob_team_sub_selection"
					label="Reason"
					:rules="[rules.isRequired]"
					class="w-full"
					:options="[
					{ value: 'new_enquiry', label: 'New enquiry' },
					{ value: 'record_only', label: 'Record purposes only' },
					]"
				/> -->

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
					<template v-if="hasRole(rolesEnum.Admin)">
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
				<x-button v-if="! hasRole(rolesEnum.PA) && $page.props.plan && (!can(permissionEnum.ApprovePayments) && can(permissionEnum.PaymentsCreate))" @click.prevent="onAddPayment" size="sm" color="emerald">
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
				<div v-if="! hasRole(rolesEnum.PA)">
					<x-button @click.prevent="onSendOCBEmail" size="sm" color="orange" class="mr-2" :disabled="record.advisor_id != $page.props.auth.user.id || !record.previous_quote_policy_number">
						Send OCB Email to Customer
					</x-button>
					<x-button @click.prevent="onDownloadPDF" size="sm" color="emerald" class="mr-2">
						Download PDF
					</x-button>
					<x-button @click.prevent="onAddPlan" size="sm" color="orange" class="mr-2" v-if="(access.carManagerCanEdit || access.carAdvisorCanEdit) && can(permissionEnum.CarQuotesPlansCreate)">
						Add Plan
					</x-button>
					<x-button v-else-if="hasRole(rolesEnum.Admin) && can(permissionEnum.CarQuotesPlansCreate)" @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
						Add Plan
					</x-button>
					<x-button @click.prevent="onAddPaymentModal" size="sm" color="#ff5e00" v-if="typeof listQuotePlans !== 'string' && listQuotePlans.length > 0">
						Copy Link
					</x-button>
				</div>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				v-model:items-selected="selectedPlans"
				:headers="availablePlansTable.columns"
				:items="availablePlansItems || []"
				border-cell
				hide-rows-per-page
				:rows-per-page="15"
        		:hide-footer="availablePlansItems.length < 15"
			>
				<template #item-providerName="{ providerName, isManualPlan, isRenewal, isDisabled }">
					<p>{{ providerName }}</p>
					<div class="flex gap-1">
						<x-tag v-if="isManualPlan" size="xs" color="primary" class="mt-0.5 text-[10px]">
							Manual
						</x-tag>
						<x-tag v-if="isRenewal" size="xs" color="success" class="mt-0.5 text-[10px]">
							Renewal
						</x-tag>
						<x-tag v-if="isDisabled" size="xs" color="error" class="mt-0.5 text-[10px]">
							Hidden
						</x-tag>
					</div>
				</template>
				<template #item-benefits="{ benefits }">
					<!-- <span>{{ benefits.feature }}</span> -->
					<template v-for="feature in benefits.feature" :key="feature">
						<template v-if="feature.code">
							<span v-if="feature.code === carPlanFeaturesCodeEnum.TPL_DAMAGE_LIMIT || feature.code === carPlanFeaturesCodeEnum.DAMAGE_LIMIT">
								{{ feature.value }}
							</span>
						</template>
						<span v-else-if="feature.text === carPlanFeaturesCodeEnum.TPL_DAMAGE_LIMIT_TEXT">
							{{ feature.value }}
						</span>
					</template>
				</template>
				<template #item-addons="{ addons }">
					<template v-for="addon in addons" :key="addon">
						<template v-for="option in addon.carAddonOption" :key="option">
							<span v-if="addon.code">
								<template v-if="addon.code.toLowerCase() === carPlanAddonsCodeEnum.DRIVER_COVER.toLowerCase() || addon.code.toLowerCase() === carPlanAddonsCodeEnum.PASSENGER_COVER.toLowerCase()">									
									{{ addon.text }}: {{ option.value }} <br />
								</template>
							</span>
							<template v-else-if="addon.text.toLowerCase() === carPlanAddonsCodeEnum.DRIVER_COVER_TEXT.toLowerCase() || addon.text.toLowerCase() === carPlanAddonsCodeEnum.PASSENGER_COVER_TEXT.toLowerCase()">
								{{ addon.text }}: {{ option.value }} <br />
							</template>
						</template>
					</template>
				</template>
				<template #item-omanCoverTPL="{ benefits }">
					<template v-for="planExc in benefits.exclusion" :key="planExc">
						<span v-if="planExc.code && (planExc.code.toLowerCase() === carPlanExclusionsCodeEnum.TPL_OMAN_COVER.toLowerCase() || planExc.code.toLowerCase() === carPlanExclusionsCodeEnum.OMAN_COVER.toLowerCase())">
							{{ planExc.text }}: {{ planExc.value }}
						</span>
					</template>
					<template v-for="planInc in benefits.inclusion" :key="planInc">
						<span v-if="planInc.code && (planInc.code.toLowerCase() === carPlanExclusionsCodeEnum.TPL_OMAN_COVER.toLowerCase() || planInc.code.toLowerCase() === carPlanExclusionsCodeEnum.OMAN_COVER.toLowerCase())">
							{{ planInc.text }}: {{ planInc.value }}
						</span>
					</template>
				</template>
				<template #item-roadSideAssistance="{ benefits }">
					<template v-for="planAss in benefits.roadSideAssistance" :key="planAss.text">
						{{ planAss.text }}: {{ planAss.value }} <br />
					</template>
				</template>
				<template #item-actualPremium="{ actualPremium }">
					{{ actualPremium ? parseFloat(actualPremium).toFixed(2) : '0.00' }}
				</template>
				<template #item-discountPremium="{ discountPremium }">
					{{ discountPremium ? parseFloat(discountPremium).toFixed(2) : '0.00' }}
				</template>
				<template #item-premiumWithVat="item">
					{{ parseFloat(item.discountPremium + item.vat + totalPriceVAT).toFixed(2) }}
				</template>
				<template #item-action="item">
					<div class="flex gap-2">
						<x-button size="xs" color="primary" outlined @click.prevent="selectPlan(item)">
							View
						</x-button>
						<x-button size="xs" color="error" outlined @click.prevent="copyPlanURL(item)" v-if="(item.discountPremium + item.vat + totalPriceVAT) > 0">
							Copy
						</x-button>
						<template v-if="item.actualPremium > 0 && item.id != record.plan_id">
							<x-button v-if="access.carAdvisorCanEditPaymentCancelledRefund || access.carAdvisorCanEditInsurer || access.carManagerCanEditInsurer" size="xs" color="error" outlined>
								Change Insurer
							</x-button>
						</template>
					</div>
				</template>
			</DataTable>

			<x-modal v-model="modals.plan" size="xl" show-close backdrop>
				<template #header>
				{{ selectedPlan.providerName }} - {{ selectedPlan.name }}
				</template>
				<LazyAvailablePlan :plan="selectedPlan" :genders="genderOptions" />
			</x-modal>
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
					<template v-if="! can(permissionEnum.ApprovePayments) && ! hasRole(rolesEnum.PA)">
						<!-- <Link :href="`${record.uuid}/documents`" class="btn btn-primary btn-sm" style="float:right;">Upload Documents</Link> -->
						<x-button @click.prevent="modals.doc = true" size="sm" color="orange">
							Upload Documents
						</x-button>
						<template v-if="displaySendPolicyButton">
							<!-- <a class="btn btn-sm btn-primary" style="float:right;" data-quote-type="{{ $quoteType }}"
                            data-quote-uuid="{{ $record->uuid }}" onclick="sendQuoteDocumentsToCustomer(this)">Send Policy</a> -->
						</template>

					</template>
					<x-button v-if="record.payment_status_id === permissionEnum.AUTHORISED && ! hasRole(rolesEnum.PA)" @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
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
			<x-modal v-model="modals.doc" size="xl" show-close backdrop>
				<template #header> Upload Documents </template>
				<LazyDocumentUploader
				:members="memberDataDocs(membersDetail)"
				:doc-types="documentTypes"
				:docs="quoteDocuments || []"
				:cdn="cdnPath"
				/>
			</x-modal>
		</div> 

		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Email Status
				</h3>
				<div>
					<template v-if="! can(permissionEnum.ApprovePayments) && ! hasRole(rolesEnum.PA)">

						<template v-if="displaySendPolicyButton">
							<!-- <a class="btn btn-sm btn-primary" style="float:right;" data-quote-type="{{ $quoteType }}"
                            data-quote-uuid="{{ $record->uuid }}" onclick="sendQuoteDocumentsToCustomer(this)">Send Policy</a> -->
						</template>

					</template>
					<x-button v-if="record.payment_status_id === permissionEnum.AUTHORISED && ! hasRole(rolesEnum.PA)" @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
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
					<x-tag size="sm">{{ notesForCustomers.length || 0 }}</x-tag>
				</h3>
				<div>
					<template v-if="! can(permissionEnum.ApprovePayments) && ! hasRole(rolesEnum.PA)">
						<x-button @click.prevent="addNotes" size="sm" color="orange" class="mr-2">
							Send Notes to Customer
						</x-button>
					</template>
				</div>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				:headers="notesForCustomersTable.columns"
				:items="notesForCustomersTableItems || []"
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
			<x-modal v-model="modals.notes" size="lg" show-close backdrop>
        		<template #header> New Note for Customer </template>

				<x-form @submit="onNoteSubmit" :auto-focus="false">
				<div class="grid">

					<x-textarea
					v-model="notesForm.description"
					label=""
					placeholder = "Type Here.."
					rows = 10
					:adjust-to-text="false"
					:rules="[
						isRequired,
					]"
					class="w-full"
					/>
					<small class = "">Max allowed 500 characters</small>

				</div>

				<div class="text-right space-x-4 mt-12">
					<small class = "text-red-600">(Note: Once added it cannot be edited or deleted.)</small>
					<x-button size="sm" @click.prevent="modals.notes = false">
					Cancel
					</x-button>

					<x-button
					size="sm"
					color="emerald"
					:loading="notesForm.processing"
					type="submit"
					>
					Send Note
					</x-button>
				</div>
				</x-form>
          </x-modal>		
		</div> 

		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Lead Activities
				<x-tag size="sm">{{ activities.length || 0 }}</x-tag>
				</h3>
				<div>
					<template v-if="! can(permissionEnum.ApprovePayments) && ! hasRole(rolesEnum.PA)">
						<x-button @click.prevent="addActivity" size="sm" color="orange" class="mr-2">
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
			<template #item-status="{ status, id }">
			<x-checkbox
				color="emerald"
				size="xl"
				:modelValue="status === 1"
				:disabled="status === 1"
				@change="onActivityStatusUpdate(id)"
			/>
			</template>
				<template #item-action="item">
					<div class="flex gap-2">
						<x-button
							size="xs"
							color="primary"
							outlined
							:disabled="item.status === 1"
							@click.prevent="activityEdit(item)"
						>
							Edit
						</x-button>
						<x-button
							size="xs"
							color="error"
							outlined
							:disabled="item.status === 1"
							@click.prevent="activityDelete(item.id)"
						>
							Delete
						</x-button>
					</div>
				</template>
			</DataTable>
			<x-modal v-model="modals.activityConfirm" show-close backdrop>
				<template #header> Delete Activity </template>
				<p>Are you sure you want to delete this activity?</p>
				<template #actions>
				<div class="text-right space-x-4">
					<x-button
					size="sm"
					ghost
					@click.prevent="modals.activityConfirm = false"
					>
					Cancel
					</x-button>
					<x-button
					size="sm"
					color="error"
					:loading="activityForm.processing"
					@click.prevent="activityDeleteConfirmed"
					>
					Delete
					</x-button>
				</div>
				</template>
      		</x-modal>
			<x-modal v-model="modals.activity" size="lg" show-close backdrop>
				<template #header>
				{{ activityActionEdit ? 'Edit' : 'Add' }} Lead Activity
				</template>

				<x-form @submit="onActivitySubmit" :auto-focus="false">
				<div class="grid gap-4">
					<x-input
					v-model="activityForm.title"
					label="Title"
					:rules="[isRequired]"
					class="w-full"
					/>

					<x-textarea
					v-model="activityForm.description"
					label="Description"
					:adjust-to-text="false"
					class="w-full"
					/>

					<x-select
					v-model="activityForm.assignee_id"
					label="Assignee"
					:options="advisorOptions"
					:rules="[isRequired]"
					placeholder="Select Assignee"
					class="w-full"
					/>

					<date-picker
					v-model="activityForm.due_date"
					label="Due Date"
					:rules="[isRequired]"
					class="w-full"
					withTime
					:timezone="'UTC'"
					/>
				</div>

				<div class="text-right space-x-4 mt-12">
					<x-button size="sm" @click.prevent="modals.activity = false">
					Cancel
					</x-button>

					<x-button
					size="sm"
					color="emerald"
					:loading="activityForm.processing"
					type="submit"
					>
					{{ activityActionEdit ? 'Update' : 'Save' }}
					</x-button>
				</div>
				</x-form>
      		</x-modal>
		</div> 
		<div class="p-4 rounded shadow mb-6 bg-white">
			<div class="flex justify-between items-center mb-4">
				<h3 class="font-semibold text-primary-800 text-lg">
					Customer Additional Contacts
					<x-tag size="sm">{{ customerAdditionalContacts.length || 0 }}</x-tag>
				</h3>
				<div>
					<template v-if="! can(permissionEnum.ApprovePayments) && ! hasRole(rolesEnum.PA)">
						<x-button
						size="sm"
						color="orange"
						@click.prevent="
							additionalContact.reset();
							modals.addContact = true;
						"
						>
						Add Additional Contacts
						</x-button>
						<template v-if="displaySendPolicyButton">
							<!-- <a class="btn btn-sm btn-primary" style="float:right;" data-quote-type="{{ $quoteType }}"
                            data-quote-uuid="{{ $record->uuid }}" onclick="sendQuoteDocumentsToCustomer(this)">Send Policy</a> -->
						</template>

					</template>
					<x-button v-if="record.payment_status_id === permissionEnum.AUTHORISED && ! hasRole(rolesEnum.PA)" @click.prevent="onAddPaymentModal" size="sm" color="orange" class="mr-2">
						Copy upload Link
					</x-button>
				</div>
			</div>
			<DataTable
				table-class-name="tablefixed compact"
				:headers="customerAdditionalContactsTable.columns"
				:items="customerAdditionalContacts || []"
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
							@click.prevent="additionalContactPrimary(item)"
						>
							Make Primary
						</x-button>
						<x-button
							size="xs"
							color="error"
							outlined
							@click.prevent="additionalContactDelete(item.id)"
						>
							Delete
						</x-button>
					</div>
				</template>
			</DataTable>
			<x-modal v-model="modals.contactDeleteConfirm" show-close backdrop>
			<template #header> Delete Additional Contact </template>
			<p>Are you sure you want to delete this?</p>
				<template #actions>
					<div class="text-right space-x-4">
					<x-button
						size="sm"
						ghost
						@click.prevent="modals.contactDeleteConfirm = false"
					>
						Cancel
					</x-button>
					<x-button
						size="sm"
						color="error"
						@click.prevent="additionalContactDeleteConfirmed"
						:loading="contactLoader"
					>
						Delete
					</x-button>
					</div>
				</template>
			</x-modal>
			<x-modal v-model="modals.contactPrimaryConfirm" show-close backdrop>
				<template #header> Primary Additional Contact </template>
				<p>Are you sure you want to make this information as Primary?</p>
				<template #actions>
				<div class="text-right space-x-4">
					<x-button
					size="sm"
					ghost
					@click.prevent="modals.contactPrimaryConfirm = false"
					>
					Cancel
					</x-button>
					<x-button
					size="sm"
					color="emerald"
					@click.prevent="additionalContactPrimaryConfirmed"
					:loading="contactLoader"
					>
					Confirm
					</x-button>
				</div>
				</template>
      		</x-modal>
			<x-modal v-model="modals.addContact" size="lg" show-close backdrop>
				<template #header> Add Additional Contacts </template>

				<x-form @submit="onAdditionalContactSubmit" :auto-focus="false">
					<div class="grid gap-4">
						<x-select
						v-model="additionalContact.additional_contact_type"
						label="Type"
						:options="[
							{ value: 'email', label: 'Email' },
							{ value: 'mobile_no', label: 'Mobile Number' },
						]"
						:rules="[isRequired]"
						placeholder="Select Type"
						class="w-full"
						/>

						<x-input
						v-model="additionalContact.additional_contact_val"
						label="Value"
						:rules="[
							isRequired,
							additionalContact.additional_contact_type === 'email'
							? isEmail
							: isNumber,
						]"
						class="w-full"
						/>
					</div>

					<div class="text-right space-x-4 mt-12">
						<x-button size="sm" @click.prevent="modals.addContact = false">
						Cancel
						</x-button>

						<x-button
						size="sm"
						color="emerald"
						:loading="additionalContact.processing"
						type="submit"
						>
						Save
						</x-button>
					</div>
				</x-form>
      		</x-modal>
		</div> 

		<div class="p-4 rounded shadow mb-6 bg-white">
		<div>
			<h3 class="font-semibold text-primary-800 text-lg">Lead History</h3>
			<x-divider class="mb-4 mt-1" />
		</div>
		<div v-if="historyData === null" class="text-center py-3">
			<x-button
			size="sm"
			color="primary"
			outlined
			@click.prevent="onLoadHistoryData"
			:loading="historyLoading"
			>
			Load History Data
			</x-button>
		</div>
		<DataTable
			v-else
			table-class-name="compact"
			:headers="historyDataTable"
			:items="historyData || []"
			border-cell
			hide-rows-per-page
			:rows-per-page="15"
			:hide-footer="historyData.length < 15"
		/>
		</div>

	</div>
	<AuditLogs :type="'App\\Models\\CarQuote'" :id="$page.props.quoteTypeId" />

</template>
