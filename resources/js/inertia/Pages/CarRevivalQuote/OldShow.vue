<script setup>

import LeadHistory from "@/inertia/Pages/PersonalQuote/Partials/LeadHistory.vue";
import AuditLogs from "@/inertia/Components/AuditLogs.vue";
import AdditionalContacts from "@/inertia/Pages/PersonalQuote/Partials/AdditionalContacts.vue";
import {computed} from "vue";
import QuoteDocuments from "@/inertia/Pages/PersonalQuote/Partials/QuoteDocuments.vue";
import QuotePolicy from "@/inertia/Pages/PersonalQuote/Partials/QuotePolicy.vue";

const page = usePage();
const notification = useToast();
const hasRole = role => useHasRole(role);
const dateFormat = date =>
    date ? useDateFormat(date, 'DD-MM-YYYY HH:mm:ss').value : '-';

const fixedValue = number => {
    if (number === Math.floor(number)) {
        return number;
    } else {
        return number.toFixed(2);
    }
};

defineProps({
    quote: Object,
    // leadStatuses: Array,
    ecomDetails: Object,
    // membersDetail: Array,
    // memberCategories: Array,
    // salaryBands: Array,
    // nationalities: Array,
    // emirates: Array,
    // advisors: Array,
    listQuotePlans: Array,
    // quoteDocuments: Object,
    documentTypes: Object,
    // cdnPath: String,
    // ecomHealthInsuranceQuoteUrl: String,
    // activities: Array,
    // customerAdditionalContacts: Array,
    lostReasons: Array,
    quoteStatusEnum: Object,
    // modelType: String,
    quoteType: String,
    // notProductionApproval: Boolean,
    allowedDuplicateLOB: Array,
    // permissions: Object,
    // genderOptions: Object,
    // isQuoteDocumentEnabled: Boolean,
    // isBetaUser: Boolean,
    // payments: Array,
    // quoteRequest: Object,
    can: Object,
    // paymentMethods: Object,
    // sendPolicy: Boolean,
});

const modals = reactive({
    duplicate: false,
//     member: false,
//     memberConfirm: false,
//     doc: false,
//     docConfirm: false,
//     plan: false,
//     createPlan: false,
//     activity: false,
//     activityConfirm: false,
//     addContact: false,
//     contactDeleteConfirm: false,
//     contactPrimaryConfirm: false,
});

const leadDuplicateForm = useForm({
    modelType: 'car',
    parentType: 'car',
    entityId: page.props.quote.id,
    entityCode: page.props.quote.code,
    entityUId: page.props.quote.uid,
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

// const confirmDeleteData = reactive({
//     docs: null,
//     member: null,
//     activity: null,
//     contact: null,
// });
//
// const confirmData = reactive({
//     contactPrimary: null,
// });
//
const assignSubteam = ref(page.props.quote.health_team_type || ''),
    assignLead = ref(null),
    memberActionEdit = ref(false),
    activityActionEdit = ref(false),
    selectedPlan = ref(null),
    selectedPlans = ref([]),
    exportLoader = ref(false),
    toggleLoader = ref(false),
    contactLoader = ref(false),
    historyLoading = ref(false),
    isDisabled = ref(false);

// const { copy, copied } = useClipboard();
//
// const { isRequired, isEmail, isNumber, isMobile } = useRules();
//
// const onCopyText = text => {
//     copy(text);
//     if (copied)
//         notification.success({
//             title: 'Link copied to clipboard',
//             position: 'top',
//         });
// };

const subTeamOptions = [
    { value: 'RM-NB', label: 'RM-NB' },
    { value: 'RM-Speed', label: 'RM-Speed' },
    { value: 'EBP', label: 'EBP' },
    { value: 'Wow-Call', label: 'Wow-Call' },
    { value: 'No-Type', label: 'No-Type' },
];

const advisorOptions = computed(() => {
    return page.props.advisors.map(advisor => ({
        value: advisor.id,
        label: advisor.name,
    }));
});

const leadStatusOptions = computed(() => {
    return page.props.leadStatuses.map(status => ({
        value: status.id,
        label: status.text,
    }));
});

// const onTeamAssign = () => {
//     if (!assignSubteam.value) {
//         notification.error({
//             title: 'Please select a subteam',
//             position: 'top',
//         });
//         return;
//     }
//     router.post(
//         `/quotes/health/healthTeamAssign`,
//         {
//             modelType: 'Health',
//             entityId: page.props.quote.id,
//             assign_team: assignSubteam.value,
//         },
//         {
//             preserveScroll: true,
//             onBefore: () => {
//                 isDisabled.value = true;
//             },
//             onSuccess: () => {
//                 notification.success({
//                     title: 'Team Assigned',
//                     position: 'top',
//                 });
//             },
//             onFinish: () => {
//                 isDisabled.value = false;
//             },
//         },
//     );
// };
//
// const onAssignLead = () => {
//     if (!assignLead.value) {
//         notification.error({
//             title: 'Please select a lead',
//             position: 'top',
//         });
//         return;
//     }
//     router.post(
//         `/quotes/health/manualLeadAssign`,
//         {
//             modelType: 'Health',
//             entityId: page.props.quote.id,
//             assigned_to_id_new: assignLead.value,
//         },
//         {
//             preserveScroll: true,
//             onBefore: () => {
//                 isDisabled.value = true;
//             },
//             onSuccess: () => {
//                 notification.success({
//                     title: 'Lead Assigned',
//                     position: 'top',
//                 });
//             },
//             onFinish: () => {
//                 isDisabled.value = false;
//             },
//         },
//     );
// };
//
const leadStatusForm = useForm({
    modelType: 'car',
    leadId: page.props.quote.id,
    quote_uuid: page.props.quote.uuid,
    assigned_to_user_id: page.props.quote.advisor_id,
    leadStatus: page.props.quote.quote_status_id || null,
    notes: page.props.quote.notes || null,
    trans_code: page.props.quote.transapp_code || null,
    lostReason: page.props.quote.lost_reason_id || null,
});

const onLeadStatus = () => {
    leadStatusForm.post(
        `/quotes/car-revival/${page.props.quote.id}/update-lead-status`,
        {
            preserveScroll: true,
            onError: errors => {
                console.log(errors);
            },
            onSuccess: () => {
                notification.success({
                    title: 'Lead Status Updated',
                    position: 'top',
                });
            },
        },
    );
};

// // plans
// const plansTable = reactive({
//     isLoading: false,
//     columns: [
//         {
//             text: 'Provider Name',
//             value: 'providerName',
//         },
//         {
//             text: 'Plan Name',
//             value: 'name',
//         },
//         {
//             text: 'Network Provider',
//             value: 'eligibilityName',
//         },
//         {
//             text: 'Base Premium',
//             value: 'actualPremium',
//         },
//         {
//             text: 'Basmah',
//             value: 'basmah',
//         },
//         {
//             text: 'Policy Fee (if applicable)',
//             value: 'policyFee',
//         },
//         {
//             text: 'Total Indicative Premium (with VAT)',
//             value: 'total',
//         },
//         {
//             text: 'Action',
//             value: 'action',
//         },
//     ],
// });
//
// const planClicked = plan => {
//     selectedPlan.value = plan;
//     modals.plan = true;
// };
//
// const onExportPlans = () => {
//     if (selectedPlans.value.length < 3 || selectedPlans.value.length > 5) {
//         notification.error({
//             title: 'Please select 3 to 5 plans to download PDF.',
//             position: 'top',
//         });
//         return;
//     }
//     exportLoader.value = true;
//     const planIds = selectedPlans.value.map(p => {
//         return p.id;
//     });
//     axios
//         .post(
//             '/api/v1/quotes/health/export-plans-pdf',
//             {
//                 plan_ids: planIds,
//                 quote_uuid: page.props.quote.uuid,
//             },
//             {
//                 responseType: 'json',
//             },
//         )
//         .then(response => {
//             const link = document.createElement('a');
//             let fileName = response.data.name;
//             link.href = response.data.data;
//             link.setAttribute('download', fileName);
//             document.body.appendChild(link);
//             link.click();
//             notification.success({
//                 title: 'Plans Exported',
//                 position: 'top',
//             });
//         })
//         .catch(error => {
//             console.log(error);
//         })
//         .finally(() => {
//             exportLoader.value = false;
//         });
// };

const onTogglePlans = toggle => {
    toggleLoader.value = true;

    const planIds = useArrayUnique(
        selectedPlans.value.map(p => {
            return p.id;
        }),
    ).value;

    axios
        .post('/quotes/health/manual-plan-toggle', {
            modelType: 'Health',
            planIds: planIds,
            quote_uuid: page.props.quote.uuid,
            toggle: toggle,
        })
        .then(response => {
            notification.success({
                title: 'Plans has been updated',
                position: 'top',
            });
            router.reload({
                preserveScroll: true,
            });
        })
        .catch(error => {
            notification.error({
                title: error,
                position: 'top',
            });
        })
        .finally(() => {
            toggleLoader.value = false;
            selectedPlans.value = [];
        });
};

// const onCreatePlan = () => {
//     router.reload({
//         preserveState: true,
//         preserveScroll: true,
//         only: ['listQuotePlans'],
//         onStart: () => {
//             modals.createPlan = false;
//         },
//         onFinish: () => {
//             notification.success({
//                 title: 'Plan Created',
//                 position: 'top',
//             });
//         },
//     });
// };
//
// const onPlanError = () => {
//     modals.createPlan = false;
//     notification.error({
//         title: 'Plan Creation Failed',
//         position: 'top',
//     });
// };
//

// //activities
// const activityTable = [
//     { text: 'Done', value: 'status', width: 60, align: 'center' },
//     { text: 'Title', value: 'title' },
//     { text: 'Client Name', value: 'client_name' },
//     { text: 'Followup Date', value: 'due_date' },
//     { text: 'Assigned To', value: 'assignee' },
//     { text: 'Action', value: 'action' },
// ];
//
// const activityForm = useForm({
//     entityUId: page.props.quote.uuid,
//     entityId: page.props.quote.id,
//     modelType: 'Health',
//     parentType: 'Health',
//     quoteType: 3,
//     title: null,
//     description: null,
//     due_date: null,
//     assignee_id: null,
//     status: null,
//     activity_id: null,
//     uuid: null,
// });
//
// const addActivity = () => {
//     activityForm.reset();
//     activityActionEdit.value = false;
//     modals.activity = true;
// };
//
// const onActivityStatusUpdate = id => {
//     activityForm.activity_id = id;
//     activityForm.post(`/activities/updateStatus`, {
//         preserveScroll: true,
//         onSuccess: () => {
//             notification.success({
//                 title: 'Lead Activity Done',
//                 position: 'top',
//             });
//         },
//     });
// };
//
// const activityEdit = data => {
//     activityActionEdit.value = true;
//     modals.activity = true;
//     activityForm.activity_id = data.id;
//     activityForm.uuid = data.uuid;
//     activityForm.title = data.title;
//     activityForm.description = data.description;
//     activityForm.due_date = data.due_date
//         ? data.due_date.split(' ')[0].split('-').reverse().join('-') +
//         'T' +
//         data.due_date.split(' ')[1]
//         : null;
//     activityForm.assignee_id = data.assignee_id;
//     activityForm.status = data.status;
// };
//
// const onActivitySubmit = isValid => {
//     if (!isValid) return;
//     if (activityActionEdit.value) {
//         activityForm.post(`/activities/${activityForm.uuid}/update`, {
//             preserveScroll: true,
//             onSuccess: () => {
//                 activityForm.reset();
//                 notification.success({
//                     title: 'Activity Updated',
//                     position: 'top',
//                 });
//             },
//             onFinish: () => {
//                 modals.activity = false;
//             },
//         });
//     } else {
//         activityForm.post(`/activities/create-activity`, {
//             preserveScroll: true,
//             onSuccess: () => {
//                 activityForm.reset();
//                 notification.success({
//                     title: 'Activity Added',
//                     position: 'top',
//                 });
//             },
//             onFinish: () => {
//                 modals.activity = false;
//             },
//         });
//     }
// };
//
// const activityDelete = id => {
//     modals.activityConfirm = true;
//     confirmDeleteData.activity = id;
// };
//
// const activityDeleteConfirmed = () => {
//     router.post(
//         `/activities/${confirmDeleteData.activity}/delete`,
//         {
//             isInertia: true,
//             quote_uuid: page.props.quote.uuid,
//         },
//         {
//             preserveScroll: true,
//             onSuccess: () => {
//                 notification.error({
//                     title: 'Activity Deleted',
//                     position: 'top',
//                 });
//             },
//             onFinish: () => {
//                 modals.activityConfirm = false;
//             },
//         },
//     );
// };
//

</script>
<template>
    <div>
        <Head title="Car Revival Detail" />
        <div class="flex justify-between items-center flex-wrap gap-2">
            <h2 class="text-xl font-semibold">Car Revival Detail</h2>
            <div class="flex gap-2">
                <x-button size="sm" color="#ff5e00" @click.prevent="openDuplicate">
                    Duplicate Lead
                </x-button>

                <Link href="/quotes/car-revival" preserve-scroll>
                    <x-button size="sm" color="primary" tag="div"> Car Revival List </x-button>
                </Link>

                <Link :href="`${quote.uuid}/edit`">
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
                    v-if="!hasRole($page.props.rolesEnum.CarRevivalAdvisor)"
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
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CDB ID</dt>
                        <dd>{{ quote.code }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CREATED DATE</dt>
                        <dd>{{ quote.created_at }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">ADVISOR</dt>
                        <dd>{{ quote.advisor?.name }}</dd>
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
<!--                        <dd>{{ quote.parent_duplicate_quote_id }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">IS ECOMMERCE</dt>
                        <dd>{{ quote.is_ecommerce ? 'Yes' : 'No' }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">BATCH</dt>
                        <dd>{{ quote.batch?.name }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LOST REASON</dt>
                        <dd>{{ quote.car_quote_request_detail?.lost_reason?.text }}</dd>
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
                        <dt class="font-medium">CUSTOMER AGE</dt>
<!--                        <dd>{{ quote.gender }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">NATIONALITY</dt>
                        <dd>{{ quote.nationality?.text }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">DATE OF BIRTH</dt>
                        <dd>{{ quote.dob }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">EMIRATE OF REGISTRATION</dt>
<!--                        <dd>{{ quote.emirate_of_your_visa_id_text }}</dd>-->
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
                        <dt class="font-medium">UAE LISENCE HELD FOR</dt>
                        <dd>{{ quote.uae_license_held_for?.text }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">HOME COUNTRY DRIVING LISENCE HELD FOR</dt>
<!--                        <dd>{{ quote.cover_for_id_text }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CURRENTLY INSURED WITH</dt>
                        <dd>{{ quote.currently_insured_with?.text }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CAR MAKE</dt>
                        <dd>{{ quote.car_make?.text }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CAR MODEL</dt>
                        <dd>{{ quote.car_model?.text }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CYLINDER</dt>
<!--                        <dd>{{ quote.currently_insured_with_id_text }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">TRIM</dt>
<!--                        <dd>{{ quote.currently_insured_with_id_text }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CAR MODEL YEAR</dt>
<!--                        <dd>{{ quote.currently_insured_with_id_text }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">FIRST REGISTRATION DATE</dt>
<!--                        <dd>{{ quote.currently_insured_with_id_text }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CAR VALUE</dt>
<!--                        <dd>{{ quote.currently_insured_with_id_text }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CAR VALUE (AT ENQUIRY)</dt>
<!--                        <dd>{{ quote.currently_insured_with_id_text }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">VEHICLE TYPE</dt>
                        <dd>{{ quote.vehicle_type?.text }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">SEAT CAPACITY</dt>
<!--                        <dd>{{ quote.currently_insured_with_id_text }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">TYPE OF CAR INSURANCE</dt>
                        <dd>{{ quote.car_type_insurance?.text }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">NEXT FOLLOWUP DATE</dt>
                        <dd>{{ dateFormat(quote.car_quote_request_detail?.next_followup_date) }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CLAIM HISTORY</dt>
                        <dd>{{ quote.claim_history?.text }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">ADVISOR ASSIGN DATE</dt>
                        <dd>{{ dateFormat(quote.car_quote_request_detail?.advisor_assigned_date) }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">LEAD COST</dt>
<!--                        <dd>{{ quote.details }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">UPDATED BY</dt>
<!--                        <dd>{{ dateFormat(quote.next_followup_date) }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">ADDITIONAL NOTES</dt>
<!--                        <dd>{{ quote.details }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">ADVISOR/PROMO CODE</dt>
                        <dd>{{ quote.details }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CALCULATED VALUE</dt>
<!--                        <dd>{{ quote.details }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CREATED BY</dt>
<!--                        <dd>{{ quote.details }}</dd>-->
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">CAN YOU PROVIDE NO-CLAIM LETTER FROM YOUR PREVIOUS INSURERS?</dt>
<!--                        <dd>{{ quote.details }}</dd>-->
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
                        <dt class="font-medium">Renewal Batch#</dt>
                        <dd>{{ quote.previous_quote_policy_number }}</dd>
                    </div>
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
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Previous Import Code</dt>
                        <dd>{{ quote.previous_quote_policy_number }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Policy Number</dt>
                        <dd>{{ quote.previous_quote_policy_premium }}</dd>
                    </div>
                    <div class="grid sm:grid-cols-2">
                        <dt class="font-medium">Renewal Expiry Date</dt>
                        <dd>{{ dateFormat(quote.previous_policy_expiry_date) }}</dd>
                    </div>
                </dl>
            </div>
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

<!--        <div class="p-4 rounded shadow mb-6 bg-white">-->
<!--            <div class="flex flex-wrap gap-4 justify-between items-center mb-4">-->
<!--                <h3 class="font-semibold text-primary-800 text-lg">-->
<!--                    Available Plans-->
<!--                    <x-tag size="sm">{{ listQuotePlans.length || 0 }}</x-tag>-->
<!--                </h3>-->
<!--                <div class="flex flex-wrap gap-3">-->
<!--                    <x-button-group v-if="selectedPlans.length > 0" size="sm">-->
<!--                        <x-button-->
<!--                            @click.prevent="onTogglePlans(false)"-->
<!--                            :loading="toggleLoader"-->
<!--                        >-->
<!--                            Show-->
<!--                        </x-button>-->
<!--                        <x-button-->
<!--                            @click.prevent="onTogglePlans(true)"-->
<!--                            :loading="toggleLoader"-->
<!--                        >-->
<!--                            Hide-->
<!--                        </x-button>-->
<!--                    </x-button-group>-->

<!--                    &lt;!&ndash; <x-button-->
<!--                      v-if="selectedPlans.length > 0"-->
<!--                      size="sm"-->
<!--                      color="emerald"-->
<!--                      @click.prevent="onExportPlans"-->
<!--                      :loading="exportLoader"-->
<!--                    >-->
<!--                      Download PDF-->
<!--                    </x-button> &ndash;&gt;-->

<!--                    &lt;!&ndash; hide create quote button for rm deployment &ndash;&gt;-->
<!--                    <x-button-->
<!--                        size="sm"-->
<!--                        color="primary"-->
<!--                        v-show="false"-->
<!--                        @click.prevent="modals.createPlan = true"-->
<!--                    >-->
<!--                        Create Quote-->
<!--                    </x-button>-->

<!--                    <x-button-->
<!--                        v-if="listQuotePlans.length > 0"-->
<!--                        size="sm"-->
<!--                        color="orange"-->
<!--                        @click.prevent="-->
<!--              onCopyText(ecomHealthInsuranceQuoteUrl + quote.uuid)-->
<!--            "-->
<!--                    >-->
<!--                        Copy Link-->
<!--                    </x-button>-->
<!--                </div>-->
<!--            </div>-->
<!--            <DataTable-->
<!--                v-model:items-selected="selectedPlans"-->
<!--                table-class-name="tablefixed compact"-->
<!--                :headers="plansTable.columns"-->
<!--                :items="listQuotePlans || []"-->
<!--                border-cell-->
<!--                hide-rows-per-page-->
<!--                :rows-per-page="15"-->
<!--                :hide-footer="listQuotePlans.length < 15"-->
<!--            >-->
<!--                <template #item-providerName="{ providerName, isManualPlan, isHidden }">-->
<!--                    <p>{{ providerName }}</p>-->
<!--                    <div class="flex gap-1">-->
<!--                        <x-tag-->
<!--                            v-if="isManualPlan"-->
<!--                            size="xs"-->
<!--                            color="primary"-->
<!--                            class="mt-0.5 text-[10px]"-->
<!--                        >-->
<!--                            Manual Plan-->
<!--                        </x-tag>-->
<!--                        <x-tag-->
<!--                            v-if="isHidden"-->
<!--                            size="xs"-->
<!--                            color="error"-->
<!--                            class="mt-0.5 text-[10px]"-->
<!--                        >-->
<!--                            Hidden-->
<!--                        </x-tag>-->
<!--                    </div>-->
<!--                </template>-->
<!--                <template #item-total="{ actualPremium, vat, basmah }">-->
<!--                    {{ fixedValue(actualPremium + (vat || 0) + (basmah || 0)) }}-->
<!--                </template>-->
<!--                <template #item-action="item">-->
<!--                    <div class="flex gap-2 pr-2">-->
<!--                        <x-button-->
<!--                            size="xs"-->
<!--                            color="primary"-->
<!--                            outlined-->
<!--                            @click.prevent="planClicked(item)"-->
<!--                        >-->
<!--                            View-->
<!--                        </x-button>-->
<!--                        <x-button-->
<!--                            size="xs"-->
<!--                            color="emerald"-->
<!--                            outlined-->
<!--                            @click.prevent="-->
<!--                onCopyText(-->
<!--                  ecomHealthInsuranceQuoteUrl +-->
<!--                    quote.uuid +-->
<!--                    `/payment/?providerCode=${item.providerCode}_${item.planCode}&planId=${item.id}`,-->
<!--                )-->
<!--              "-->
<!--                        >-->
<!--                            Copy-->
<!--                        </x-button>-->
<!--                    </div>-->
<!--                </template>-->
<!--            </DataTable>-->

<!--            <x-modal v-model="modals.plan" size="xl" show-close backdrop>-->
<!--                <template #header>-->
<!--                    {{ selectedPlan.providerName }} - {{ selectedPlan.name }}-->
<!--                </template>-->
<!--                <LazyAvailablePlan :plan="selectedPlan" :genders="genderOptions" />-->
<!--            </x-modal>-->

<!--            <x-modal v-model="modals.createPlan" size="lg" show-close backdrop>-->
<!--                <template #header> Create Heath Quote </template>-->
<!--                <LazyCreatePlan-->
<!--                    :uuid="quote.uuid"-->
<!--                    @success="onCreatePlan"-->
<!--                    @error="onPlanError"-->
<!--                />-->
<!--            </x-modal>-->
<!--        </div>-->

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
                </dl>
            </div>
        </div>

        <QuotePolicy
            :quote="quote"
            :can="can"
            :quoteStatusEnum="quoteStatusesEnum"
        />

        <QuoteDocuments
            :document-types="documentTypes"
            :quote-documents="quote.documents || []"
            :storageUrl="storageUrl"
            :quote="quote"
        />

<!--        <div class="p-4 rounded shadow mb-6 bg-white">-->
<!--            <div class="flex justify-between items-center mb-4">-->
<!--                <h3 class="font-semibold text-primary-800 text-lg">-->
<!--                    Lead Activities-->
<!--                    <x-tag size="sm">{{ activities.length || 0 }}</x-tag>-->
<!--                </h3>-->
<!--                <x-button size="sm" color="orange" @click.prevent="addActivity">-->
<!--                    Add Activity-->
<!--                </x-button>-->
<!--            </div>-->
<!--            <x-divider class="my-4" />-->

<!--            <DataTable-->
<!--                table-class-name="compact"-->
<!--                :headers="activityTable"-->
<!--                :items="activities"-->
<!--                border-cell-->
<!--                hide-rows-per-page-->
<!--                :rows-per-page="15"-->
<!--                :hide-footer="activities.length < 15"-->
<!--            >-->
<!--                <template #item-status="{ status, id }">-->
<!--                    <x-checkbox-->
<!--                        color="emerald"-->
<!--                        size="xl"-->
<!--                        :modelValue="status === 1"-->
<!--                        :disabled="status === 1"-->
<!--                        @change="onActivityStatusUpdate(id)"-->
<!--                    />-->
<!--                </template>-->
<!--                <template #item-action="item">-->
<!--                    <div class="space-x-4">-->
<!--                        <x-button-->
<!--                            size="xs"-->
<!--                            color="primary"-->
<!--                            outlined-->
<!--                            :disabled="item.status === 1"-->
<!--                            @click.prevent="activityEdit(item)"-->
<!--                        >-->
<!--                            Edit-->
<!--                        </x-button>-->
<!--                        <x-button-->
<!--                            size="xs"-->
<!--                            color="error"-->
<!--                            :disabled="item.status === 1"-->
<!--                            outlined-->
<!--                            @click.prevent="activityDelete(item.id)"-->
<!--                        >-->
<!--                            Delete-->
<!--                        </x-button>-->
<!--                    </div>-->
<!--                </template>-->
<!--            </DataTable>-->
<!--            <x-modal v-model="modals.activity" size="lg" show-close backdrop>-->
<!--                <template #header>-->
<!--                    {{ activityActionEdit ? 'Edit' : 'Add' }} Lead Activity-->
<!--                </template>-->

<!--                <x-form @submit="onActivitySubmit" :auto-focus="false">-->
<!--                    <div class="grid gap-4">-->
<!--                        <x-input-->
<!--                            v-model="activityForm.title"-->
<!--                            label="Title"-->
<!--                            :rules="[isRequired]"-->
<!--                            class="w-full"-->
<!--                        />-->

<!--                        <x-textarea-->
<!--                            v-model="activityForm.description"-->
<!--                            label="Description"-->
<!--                            :adjust-to-text="false"-->
<!--                            class="w-full"-->
<!--                        />-->

<!--                        <x-select-->
<!--                            v-model="activityForm.assignee_id"-->
<!--                            label="Assignee"-->
<!--                            :options="advisorOptions"-->
<!--                            :rules="[isRequired]"-->
<!--                            placeholder="Select Assignee"-->
<!--                            class="w-full"-->
<!--                        />-->

<!--                        <date-picker-->
<!--                            v-model="activityForm.due_date"-->
<!--                            label="Due Date"-->
<!--                            :rules="[isRequired]"-->
<!--                            class="w-full"-->
<!--                            withTime-->
<!--                            :timezone="'UTC'"-->
<!--                        />-->
<!--                    </div>-->

<!--                    <div class="text-right space-x-4 mt-12">-->
<!--                        <x-button size="sm" @click.prevent="modals.activity = false">-->
<!--                            Cancel-->
<!--                        </x-button>-->

<!--                        <x-button-->
<!--                            size="sm"-->
<!--                            color="emerald"-->
<!--                            :loading="activityForm.processing"-->
<!--                            type="submit"-->
<!--                        >-->
<!--                            {{ activityActionEdit ? 'Update' : 'Save' }}-->
<!--                        </x-button>-->
<!--                    </div>-->
<!--                </x-form>-->
<!--            </x-modal>-->
<!--            <x-modal v-model="modals.activityConfirm" show-close backdrop>-->
<!--                <template #header> Delete Activity </template>-->
<!--                <p>Are you sure you want to delete this activity?</p>-->
<!--                <template #actions>-->
<!--                    <div class="text-right space-x-4">-->
<!--                        <x-button-->
<!--                            size="sm"-->
<!--                            ghost-->
<!--                            @click.prevent="modals.activityConfirm = false"-->
<!--                        >-->
<!--                            Cancel-->
<!--                        </x-button>-->
<!--                        <x-button-->
<!--                            size="sm"-->
<!--                            color="error"-->
<!--                            :loading="activityForm.processing"-->
<!--                            @click.prevent="activityDeleteConfirmed"-->
<!--                        >-->
<!--                            Delete-->
<!--                        </x-button>-->
<!--                    </div>-->
<!--                </template>-->
<!--            </x-modal>-->
<!--        </div>-->

<!--        <div class="p-4 rounded shadow mb-6 bg-white">-->
<!--            <div class="flex flex-wrap gap-3 justify-between items-center mb-4">-->
<!--                <h3 class="font-semibold text-primary-800 text-lg">-->
<!--                    Customer Additional Contacts-->
<!--                    <x-tag size="sm">{{ customerAdditionalContacts.length || 0 }}</x-tag>-->
<!--                </h3>-->
<!--                <x-button-->
<!--                    size="sm"-->
<!--                    color="orange"-->
<!--                    @click.prevent="-->
<!--            additionalContact.reset();-->
<!--            modals.addContact = true;-->
<!--          "-->
<!--                >-->
<!--                    Add Additional Contacts-->
<!--                </x-button>-->
<!--            </div>-->

<!--            <DataTable-->
<!--                table-class-name="compact"-->
<!--                :headers="additionalContactTable"-->
<!--                :items="customerAdditionalContacts || []"-->
<!--                border-cell-->
<!--                hide-rows-per-page-->
<!--                hide-footer-->
<!--            >-->
<!--                <template #item-key="{ key }">-->
<!--                    <span v-if="key === 'email'"> Email Address </span>-->
<!--                    <span v-else> Mobile Number </span>-->
<!--                </template>-->
<!--                <template #item-action="item">-->
<!--                    <x-button-->
<!--                        size="xs"-->
<!--                        color="emerald"-->
<!--                        outlined-->
<!--                        @click.prevent="additionalContactPrimary(item)"-->
<!--                    >-->
<!--                        Make Primary-->
<!--                    </x-button>-->
<!--                </template>-->
<!--            </DataTable>-->

<!--            <x-modal v-model="modals.addContact" size="lg" show-close backdrop>-->
<!--                <template #header> Add Additional Contacts </template>-->

<!--                <x-form @submit="onAdditionalContactSubmit" :auto-focus="false">-->
<!--                    <div class="grid gap-4">-->
<!--                        <x-select-->
<!--                            v-model="additionalContact.additional_contact_type"-->
<!--                            label="Type"-->
<!--                            :options="[-->
<!--                { value: 'email', label: 'Email' },-->
<!--                { value: 'mobile_no', label: 'Mobile Number' },-->
<!--              ]"-->
<!--                            :rules="[isRequired]"-->
<!--                            placeholder="Select Type"-->
<!--                            class="w-full"-->
<!--                        />-->

<!--                        <x-input-->
<!--                            v-model="additionalContact.additional_contact_val"-->
<!--                            label="Value"-->
<!--                            :rules="[-->
<!--                isRequired,-->
<!--                additionalContact.additional_contact_type === 'email'-->
<!--                  ? isEmail-->
<!--                  : isNumber,-->
<!--              ]"-->
<!--                            class="w-full"-->
<!--                        />-->
<!--                    </div>-->

<!--                    <div class="text-right space-x-4 mt-12">-->
<!--                        <x-button size="sm" @click.prevent="modals.addContact = false">-->
<!--                            Cancel-->
<!--                        </x-button>-->

<!--                        <x-button-->
<!--                            size="sm"-->
<!--                            color="emerald"-->
<!--                            :loading="additionalContact.processing"-->
<!--                            type="submit"-->
<!--                        >-->
<!--                            Save-->
<!--                        </x-button>-->
<!--                    </div>-->
<!--                </x-form>-->
<!--            </x-modal>-->

<!--            <x-modal v-model="modals.contactPrimaryConfirm" show-close backdrop>-->
<!--                <template #header> Primary Additional Contact </template>-->
<!--                <p>Are you sure you want to make this information as Primary?</p>-->
<!--                <template #actions>-->
<!--                    <div class="text-right space-x-4">-->
<!--                        <x-button-->
<!--                            size="sm"-->
<!--                            ghost-->
<!--                            @click.prevent="modals.contactPrimaryConfirm = false"-->
<!--                        >-->
<!--                            Cancel-->
<!--                        </x-button>-->
<!--                        <x-button-->
<!--                            size="sm"-->
<!--                            color="emerald"-->
<!--                            @click.prevent="additionalContactPrimaryConfirmed"-->
<!--                            :loading="contactLoader"-->
<!--                        >-->
<!--                            Confirm-->
<!--                        </x-button>-->
<!--                    </div>-->
<!--                </template>-->
<!--            </x-modal>-->
<!--        </div>-->
            <AdditionalContacts :quote="quote" />

            <LeadHistory :quote="$page.props.quote" />

            <AuditLogs
                :id="$page.props.quote.id"
                :quote-type="quoteType"
            />
    </div>
</template>
