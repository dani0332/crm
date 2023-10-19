<script setup>
import { useDateFormat } from '@vueuse/shared';

defineProps({
    reportData: Object,
    filterOptions: Object,
    defaultFilters: Object,
    renewalBatchesList: Object
});


const loaders = reactive({
    table: false,
    advisorsOptions: false,
    subTeamsOptions: false,
});

const page = usePage();
const dataTableRef = ref();
const isMounted = ref(false);
const isDirty = ref(false);


const advisorOptions = ref(
    Object.keys(page.props.filterOptions.advisors).map(key => ({
        value: key,
        label: page.props.filterOptions.advisors[key],
    }))
);

const defaultAdvisorOptions = advisorOptions;

const subTeamsOptions = ref(
    Object.keys(page.props.filterOptions.subTeams).map(key => ({
        value: key,
        label: page.props.filterOptions.subTeams[key].toUpperCase(),
    }))
);

const defaultSubTeamsOptions = subTeamsOptions;

const params = useUrlSearchParams('history');
const tableHeader = [
    {
        text: 'Batch No.',
        value: 'renewal_batch',
    },
    {
        text: 'Week Ending',
        value: 'end_date',
    },
    {
        text: 'Renewed',
        value: 'renewed',
    },
    {
        text: 'Total Allocated',
        value: 'total_allocated_leads',
    },
    {
        text: 'Car Sold/Cancelled',
        value: 'car_sold',
    },
    {
        text: 'Uncontactable',
        value: 'uncontactable',
    },
    {
        text: 'Total Allocated (excluding cancelled and uncontactable)',
        value: 'total_allocate_minus_cancelled_uncontactable',
    },
    {
        text: 'Advisor Retention',
        value: 'advisor_retention',
    },
    {
        text: 'Value Segment Retention',
        value: 'value_segment_retention',
    },
    {
        text: 'Volume Segment Retention',
        value: 'volume_segment_retention',
    },
    {
        text: 'Relative Retention on Value Segment',
        value: 'relative_retention_value_segment',
    },
    {
        text: 'Relative Retention on  Volume Segment',
        value: 'relative_retention_volume_segment',
    },
    {
        text: 'IM Retention',
        value: 'im_retention',
    },
    {
        text: 'Relative Retention',
        value: 'relative_retention',
    },
    {
        text: 'Monthly Retention',
        value: 'monthly_retention',
    },
];

const filters = reactive({
    reportDate: '',
    batchNo: [],
    subTeams: [],
    segment: '',
    advisors: [],
    teams: [],
    page: 1,
});

function onSubmit(isValid) {
    if (isValid) {
        isDirty.value = false;
        filters.page = 1;
        const payLoad = cleanFilters(filters);
        router.visit('/reports/renewal-report', {
            method: 'get',
            data: {
                ...payLoad,
                ...(payLoad.advisors && {
                    advisors: Array.isArray(payLoad.advisors)
                        ? payLoad.advisors
                        : [payLoad.advisors],
                }),
                ...(payLoad.subTeams && {
                    subTeams: Array.isArray(payLoad.subTeams) ? payLoad.subTeams : [payLoad.subTeams],
                }),
                ...(payLoad.teams && {
                    teams: Array.isArray(payLoad.teams) ? payLoad.teams : [payLoad.teams],
                }),
            },
            preserveState: false,
            preserveScroll: true,
            onBefore: () => (loaders.table = true),
            onSuccess: () => {
                loaders.table = false;
                calculateValuesAndHighlight();
            },
        });
    } else {
        console.log('Invalid');
    }
}

function onReset() {
    if (page.props.defaultFilters) {
        filters.reportDate =
            page.props.defaultFilters.reportDate;
    }

    isDirty.value = false;
    router.visit('/reports/renewal-report', {
        method: 'get',
        data: {
            reportDate: filters.reportDate,
            page: 1,
        },
        preserveScroll: true,
        onBefore: () => (loaders.table = true),
        onSuccess: () => (loaders.table = false),
    });
}

const cleanFilters = filters => {
    Object.keys(filters).forEach(
        key =>
            (filters[key] === '' ||
                filters[key] == null ||
                filters[key].length == 0) &&
            delete filters[key],
    );
    return filters;
};

function setQueryStringFilters() {
    for (const [key] of Object.entries(params)) {
        if (key.includes('[]')) {
            filters[key.substring(0, key.length - 2)] = params[key];
        } else {
            filters[key] = params[key];
        }
    }
}


const onTeamChange = e => {
    if (e.length == 0) {
        // filters.teams = [];
        filters.subTeams = [];
        advisorOptions.value = [];
        subTeamsOptions.value = [];

        return;
    }

    if (isMounted.value) {
        isDirty.value = true;
    }

    loaders.advisorOptions = true;
    loaders.subTeamsOptions = true;

    axios
        .post(`/reports/fetch-subteams-advisor-by-team`, {
            teamIds: Array.isArray(e) ? e : [e],
        })
        .then(res => {

            if (res.data.advisors.length > 0) {
                advisorOptions.value = Object.keys(res.data.advisors).map(key => ({
                    value: res.data.advisors[key].id,
                    label: res.data.advisors[key].name,
                }));
            }
            if (res.data.subTeams) {
                subTeamsOptions.value = Object.keys(res.data.subTeams).map(key => ({
                    value: key,
                    label: res.data.subTeams[key],
                }));
            }
        })
        .finally(() => {
            loaders.advisorOptions = false;
            loaders.subTeamsOptions = false;
        });
};

const hasRole = role => useHasRole(role);
const hasAnyRole = role => useHasAnyRole(role);

let lastMonthSummedIndex = 0;
let currentRowSpan = 0;

const rolesEnum = page.props.rolesEnum;

let avgImRetentionArr = {};
let avgRawRetentionArr = {};

let monthlyIMAverages = {};
let monthlyRawAverages = {};

const reportDataRef = reactive(page.props.reportData.data);

function getMonthName(monthNumber) {
    const date = new Date();
    date.setMonth(monthNumber - 1);

    return date.toLocaleString('en-US', { month: 'short' });
}

function calculateValuesAndHighlight() {
    lastMonthSummedIndex = 0;
    currentRowSpan = 0;


    let segmentFilter = filters.segment ? filters.segment : '';

    reportDataRef.forEach((item, index) => {

        if (segmentFilter == 'volume') {
            item.total_allocated_leads = item.total_by_volume_segment_advisors
            item.renewed = item.renewed_by_volume_segment_advisors
            item.car_sold = item.car_sold_by_volume_segment
            item.uncontactable = item.uncontactable_by_volume_segment
        } else if (segmentFilter == 'value') {
            item.total_allocated_leads = item.total_by_value_segment_advisors
            item.renewed = item.renewed_by_value_segment_advisors
            item.car_sold = item.car_sold_by_value_segment
            item.uncontactable = item.uncontactable_by_value_segment
        }
    });

    reportDataRef.forEach((item, index) => {

        let advisorRetention =
            (
                (
                    parseInt(item.renewed) /
                    (
                        parseInt(item.total_allocated_leads) -
                        parseInt(item.car_sold) - parseInt(item.uncontactable)
                    )) * 100

            ).toFixed(2);
        advisorRetention = advisorRetention == 'NaN' ? '0.00' : advisorRetention;

        let imRetention = 0.00;
        let rawRetention = 0.00;

        if (item.total_allocated_leads_by_all_advisors != undefined || item.total_allocated_leads_by_all_advisors == ''
            && item.renewed_by_all_advisors != undefined || item.renewed_by_all_advisors == '') {
            imRetention = (
                (
                    parseInt(item.renewed_by_all_advisors) /
                    (
                        parseInt(item.total_allocated_leads_by_all_advisors) -
                        parseInt(item.car_sold_by_all_advisors) - parseInt(item.uncontactable_by_all_advisors)
                    )) * 100

            ).toFixed(2);

            rawRetention = ((item.renewed_by_all_advisors / item.total_allocated_leads_by_all_advisors) * 100).toFixed(2);

        }
        else {
            imRetention = (
                (
                    parseInt(item.renewed) /
                    (
                        parseInt(item.total_allocated_leads) -
                        parseInt(item.car_sold) - parseInt(item.uncontactable)
                    )) * 100

            ).toFixed(2);

            rawRetention = ((item.renewed / item.total_allocated_leads) * 100).toFixed(2);

        }
        imRetention = imRetention == 'NaN' ? '0.00' : imRetention;

        let valueSegmentConversion = (
            (
                parseInt(item.renewed_by_value_segment_advisors) /
                (
                    parseInt(item.total_by_value_segment_advisors) -
                    parseInt(item.car_sold_by_value_segment) - parseInt(item.uncontactable_by_value_segment)
                )) * 100
        ).toFixed(2);
        valueSegmentConversion = valueSegmentConversion == 'NaN' ? '0.00' : valueSegmentConversion;

        let volumeSegmentConversion = (
            (
                parseInt(item.renewed_by_volume_segment_advisors) /
                (
                    parseInt(item.total_by_volume_segment_advisors) -
                    parseInt(item.car_sold_by_volume_segment) - parseInt(item.uncontactable_by_volume_segment)
                )) * 100
        ).toFixed(2);
        volumeSegmentConversion = volumeSegmentConversion == 'NaN' ? '0.00' : volumeSegmentConversion;

        const monthlySum = calculateMonthlySum(reportDataRef, index);

        let ratioCarSoldUncontactable = (
            (
                (
                    parseInt(item.car_sold) + parseInt(item.uncontactable)) /
                parseInt(item.total_allocated_leads)
            ) * 100
        ).toFixed(2);
        ratioCarSoldUncontactable = ratioCarSoldUncontactable == 'NaN' ? '0.00' : ratioCarSoldUncontactable;

        item.ratioCarSoldUncontactable = ratioCarSoldUncontactable == 'NaN' ? '0.00' : ratioCarSoldUncontactable;
        item.advisorRetention = advisorRetention == 'NaN' ? '0.00' : advisorRetention;

        item.volumeSegmentConversion = volumeSegmentConversion == 'NaN' ? '0.00' : volumeSegmentConversion;
        item.valueSegmentConversion = valueSegmentConversion == 'NaN' ? '0.00' : valueSegmentConversion;
        item.imRetention = imRetention == 'NaN' ? '0.00' : imRetention;
        item.monthlySum = monthlySum == 'NaN' ? '0.00' : monthlySum;
        item.rawRetention = rawRetention == 'NaN' ? '0.00' : rawRetention;
        item.rowSpan = currentRowSpan;
        item.highlight = (advisorRetention < valueSegmentConversion) ||
            (advisorRetention < volumeSegmentConversion) ||
            (advisorRetention < imRetention);

        let year = useDateFormat(new Date(), 'YY').value
        let monthName = getMonthName(item.month) + "-" + year;

        // Check if the property exists and initialize it as an array if it doesn't
        if (!avgImRetentionArr[monthName]) {
            avgImRetentionArr[monthName] = [];
        }
        if (!avgRawRetentionArr[monthName]) {
            avgRawRetentionArr[monthName] = [];
        }

        avgImRetentionArr[monthName].push(imRetention == 'NaN' ? parseFloat('0.00') : parseFloat(imRetention));
        avgRawRetentionArr[monthName].push(rawRetention == 'NaN' ? parseFloat('0.00') : parseFloat(rawRetention));

        page.props.renewalBatchesList.forEach(batch => {
            batch.slabs.forEach(slab => {

                batch.teams.forEach(team => {
                    let teamName = team.name;

                    if (teamName.includes('BDM')) {
                        let slabId = slab.pivot.slab_id;
                        let slabMax = slab.pivot.max;
                        let slabMin = slab.pivot.min;

                        if (slabId === 3 && item.advisorRetention > slabMin) {
                            item.advisorRetentionClass = "text-green-500";
                        }
                        else if (slabId === 2 && item.advisorRetention < slabMax && item.advisorRetention > slabMin) {
                            item.advisorRetentionClass = "text-amber-500";
                        }
                        else if (slabId === 1 && item.advisorRetention < slabMax) {
                            item.advisorRetentionClass = "text-red-500";
                        }

                    }
                    else if (teamName.includes('volume') || teamName.includes('value')) {
                        let slabId = slab.pivot.slab_id;
                        let slabMax = slab.pivot.max;
                        let slabMin = slab.pivot.min;

                        if (slabId === 4 && item.advisorRetention > slabMin) {
                            item.advisorRetentionClass = "text-green-500";
                        }
                        if (slabId === 3 && item.advisorRetention < slabMax && item.advisorRetention > slabMin) {
                            item.advisorRetentionClass = "text-amber-500";
                        }
                        else if (slabId === 2 && item.advisorRetention < slabMax && item.advisorRetention > slabMin) {
                            item.advisorRetentionClass = "text-orange-500";
                        }
                        else if (slabId === 1 && item.advisorRetention < slabMax) {
                            item.advisorRetentionClass = "text-red-500";
                        }
                    }
                });

            })
        });
    });

    monthlyIMAverages = calculateMonthlyAverages(avgImRetentionArr);
    monthlyRawAverages = calculateMonthlyAverages(avgRawRetentionArr);

    // reset arrays
    avgImRetentionArr = {};
    avgRawRetentionArr = {};

}


onMounted(() => {

    if (page.props.defaultFilters && !params['page']) {
        filters.reportDate =
            page.props.defaultFilters.reportDate;
    }

    setQueryStringFilters();

    if (params['teams[]'] && params['teams[]'].length > 0) {
        onTeamChange(params['teams[]']);
    }

    calculateValuesAndHighlight();
    isMounted.value = true;
});

const calculateMonthlySum = (data, index) => {

    var totalRenewed = 0;
    var totalAllocated = 0;
    var totalCarSold = 0;
    var totalCarUncontactable = 0;
    currentRowSpan = 0;

    var currentMonthValue = data[index].month;

    if (lastMonthSummedIndex <= index) {
        while (index <= (data.length - 1) && currentMonthValue == data[index].month) {

            totalRenewed = parseInt(totalRenewed) + parseInt(data[index].renewed);
            totalAllocated = parseInt(totalAllocated) + parseInt(data[index].total_allocated_leads);
            totalCarSold = parseInt(totalCarSold) + parseInt(data[index].car_sold);
            totalCarUncontactable = parseInt(totalCarUncontactable) + parseInt(data[index].uncontactable);
            index++;
            lastMonthSummedIndex = index;
            currentRowSpan++;
        }

        var result = totalRenewed / (totalAllocated - totalCarSold - totalCarUncontactable) * 100;

        return (result).toFixed(2);
    }
};

function calculateAverage(arr) {
    const sum = arr.reduce((acc, val) => acc + val, 0);
    return sum / arr.length;
}

function calculateMonthlyAverages(data) {
    const monthlyAverages = {};

    for (const month in data) {
        const avg = calculateAverage(data[month]);
        monthlyAverages[month] = avg;
    }

    return monthlyAverages;
}

watch(
    () => page.props.reportData.current_page,
    () => {
        calculateValuesAndHighlight()
    },
    { deep: true, immediate: false },
)

</script>
<template>
    <div>

        <Head title="Renewal Batches Report" />
        <h1 class="text-2xl font-bold text-center text-primary-500 mb-4">
            Renewal Batches Report
        </h1>

        <x-divider class="my-4" />
        <x-form @submit="onSubmit" :auto-focus="false">
            <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-4">
                <DatePicker v-model="filters.reportDate" label="Report Date" placeholder="Select Date" size="sm"
                    model-type="yyyy-MM-dd" />

                <ComboBox v-model="filters.batchNo" label="Batch Number" placeholder="Search by Batch Number" :options="Object.keys(filterOptions.batches).map(key => ({
                    value: key,
                    label: filterOptions.batches[key],
                }))
                    " :max-limit="15" deselect-all />

                <ComboBox v-if="hasAnyRole([rolesEnum.SeniorManagement, rolesEnum.Accounts])" v-model="filters.teams"
                    label="Teams" placeholder="Search by Teams" :options="Object.keys(filterOptions.teams).map(key => ({
                        value: key,
                        label: filterOptions.teams[key],
                    }))
                        " @update:model-value="onTeamChange" :select-all="filters.teams?.length > 0"
                    :deselect-all="filters.teams?.length > 0" />

                <ComboBox
                    v-if="hasAnyRole([rolesEnum.CarManager, rolesEnum.RenewalsManager, rolesEnum.CarDeputyManager, rolesEnum.SeniorManagement, rolesEnum.Accounts])"
                    v-model="filters.advisors" label="Advisors" placeholder="Search by Advisors" :options="advisorOptions"
                    :loading="loaders.advisorOptions" :select-all="filters.advisors?.length > 0"
                    :deselect-all="filters.advisors?.length > 0" />

                <ComboBox v-if="hasAnyRole([rolesEnum.CarManager, rolesEnum.RenewalsManager, rolesEnum.SeniorManagement])"
                    v-model="filters.subTeams" label="Sub Team" placeholder="Search by Sub Team" class="w-full"
                    :options="subTeamsOptions" :select-all="filters.subTeams?.length > 0"
                    :deselect-all="filters.subTeams?.length > 0" />

                <x-select v-if="hasAnyRole([rolesEnum.CarManager, rolesEnum.RenewalsManager])" v-model="filters.segment"
                    label="Segment" placeholder="Search by Segment" class="w-full" :options="Object.keys(filterOptions.segments).map(key => ({
                        value: filterOptions.segments[key],
                        label: filterOptions.segments[key].toUpperCase(),
                    }))
                        " />

            </div>
            <div class="flex justify-between gap-3 mb-4 items-center">
                <div class="flex-1">
                    <p v-if="isDirty" class="text-xs text-red-500 text-center font-bold">
                        Please click search, to show updated records based on the selected
                        filters
                    </p>
                </div>
                <div class="flex gap-3">
                    <x-button size="sm" color="#ff5e00" type="submit">Search</x-button>
                    <x-button size="sm" color="primary" @click.prevent="onReset">
                        Reset
                    </x-button>
                </div>
            </div>
        </x-form>

        <!-- ============================================================= -->

        <div class="text-sm my-4">
            <div class="w-full">
                <div class="flex overflow-x-scroll">
                    <table class="x-table w-full relative">
                        <thead class="h-24 align-bottom bg-primary-700">
                            <tr class="text-sm text-gray-600 border-b">
                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Batch
                                </th>
                                <th
                                    class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left w-28">
                                    Week Ending
                                </th>
                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Renewed
                                </th>
                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Total Allocated
                                </th>

                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Approved Car Sold
                                </th>

                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Approved Uncontactable
                                </th>

                                <th v-if="hasAnyRole([rolesEnum.CarManager, rolesEnum.RenewalsManager, rolesEnum.CarDeputyManager, rolesEnum.SeniorManagement, rolesEnum.Accounts])"
                                    class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Ratio - Approved Car Sold and Uncontactable
                                </th>

                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Total Allocations (excluding cancelled and uncontactable)
                                </th>

                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Advisor Retention
                                </th>

                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Monthly Retention
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr :class="{ 'bg-[#ffff00]': item.highlight }" v-for="(item, index) in reportDataRef" :key="index"
                                class="border-b border-gray-200 align-top">
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    {{ item.name }}
                                </td>
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    <!-- {{ moment(item.end_date).format('MMMM do') }} -->
                                    {{ useDateFormat(item.end_date, 'MMM DD').value }}
                                </td>
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    {{ item.renewed.toLocaleString() }}
                                </td>
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    {{ item.total_allocated_leads.toLocaleString() }}
                                </td>
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    {{ item.car_sold.toLocaleString() }}
                                </td>
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    {{ item.uncontactable.toLocaleString() }}
                                </td>
                                <td v-if="hasAnyRole([rolesEnum.CarManager, rolesEnum.RenewalsManager, rolesEnum.CarDeputyManager, rolesEnum.SeniorManagement, rolesEnum.Accounts])"
                                    class="x-table-cell px-3 py-4 align-middle">
                                    {{ item.ratioCarSoldUncontactable }} %
                                </td>
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    <!-- Sum of allocations per batch  - (Approved Car Sold + Approved Uncontactable) -->
                                    <p v-if="item.total_allocated_leads == 0"> 0 </p>
                                    <p v-else>{{ (parseInt(item.total_allocated_leads) - (parseInt(item.car_sold) +
                                        parseInt(item.uncontactable))).toLocaleString() }} </p>
                                </td>
                                <td :class="item.advisorRetentionClass" class="x-table-cell px-3 py-4 align-middle">
                                    {{ item.advisorRetention }} %
                                </td>
                                <td v-if="item.rowSpan > 0" class="x-table-cell px-3 py-4 align-middle text-center"
                                    :rowspan="item.rowSpan">
                                    <b> {{ item.monthlySum }} %</b>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <!-- =================== new table ===================== -->

                    <table class="x-table w-full ms-3 relative">
                        <thead class="h-24 align-bottom bg-primary-700">
                            <tr class="text-sm text-gray-600 border-b">
                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Value Retention
                                </th>

                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Volume Retention
                                </th>

                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Relative Retention on Value
                                </th>

                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Relative Retention on Volume
                                </th>

                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    IM Retention
                                </th>

                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                    Relative Retention
                                </th>

                                <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left"
                                    v-if="hasRole(rolesEnum.CarAdvisor) != true">
                                    Raw Retention
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr :class="{ 'bg-[#ffff00]': item.highlight }" v-for="(item, index) in reportDataRef" :key="index"
                                class="border-b border-gray-200 align-top">
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    {{ item.valueSegmentConversion == '0.00' ? 'N/A' : item.valueSegmentConversion + '%' }}
                                </td>
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    {{ item.volumeSegmentConversion == '0.00' ? 'N/A' : item.volumeSegmentConversion + '%' }}
                                </td>
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    <p>{{ parseFloat(item.valueSegmentConversion) > 0 ? (parseFloat(item.advisorRetention) -
                                        parseFloat(item.valueSegmentConversion)).toFixed(2) + '%' : 'N/A' }}</p>
                                </td>
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    <p>{{ parseFloat(item.volumeSegmentConversion) > 0 ? (parseFloat(item.advisorRetention) -
                                        parseFloat(item.volumeSegmentConversion)).toFixed(2) + '%' : 'N/A' }}</p>
                                </td>
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    {{ item.imRetention }} %
                                </td>
                                <td class="x-table-cell px-3 py-4 align-middle">
                                    <p>{{ (parseFloat(item.advisorRetention) - parseFloat(item.imRetention)).toFixed(2) }} %</p>
                                </td>
                                <td v-if="hasRole(rolesEnum.CarAdvisor) != true"
                                    class="x-table-cell px-3 py-4 align-middle text-center">
                                    <p> {{ item.rawRetention }} %</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- ======================= new table ==================== -->
                <table class="x-table relative w-50 mt-10"
                    v-if="hasAnyRole([rolesEnum.CarManager, rolesEnum.RenewalsManager, rolesEnum.SeniorManagement, rolesEnum.Accounts])">
                    <thead class="align-bottom bg-primary-700">
                        <tr class="text-sm text-gray-600 border-b">
                            <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                Month
                            </th>
                            <th
                                class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left w-28">
                                Avgerage IMRetention
                            </th>
                            <th class="py-2 font-semibold tracking-widest uppercase text-xs px-3 sticky top-0 text-left">
                                Avgerage RawRetention
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(value, index) in monthlyIMAverages" :key="index"
                            class="border-b border-gray-200 align-top">

                            <td class="x-table-cell px-3 py-4 align-middle">
                                {{ index }}
                            </td>
                            <td class="x-table-cell px-3 py-4 align-middle">
                                {{ monthlyIMAverages[index] ? monthlyIMAverages[index].toFixed(2) : 0 }} %
                            </td>
                            <td class="x-table-cell px-3 py-4 align-middle">
                                {{ monthlyRawAverages[index] ? monthlyRawAverages[index].toFixed(2) : 0 }} %
                            </td>

                        </tr>
                    </tbody>
                </table>
            </div>
        </div>


        <!-- ============================================================= -->


        <div>
            <Pagination :links="{

                next: page.props.reportData.next_page_url,
                prev: page.props.reportData.prev_page_url,
                current: page.props.reportData.current_page,
                from: page.props.reportData.from,
                to: page.props.reportData.to,
                total: page.props.reportData.total,
                last: page.props.reportData.last_page
            }" :loading="page.props.reportData.loader" />
        </div>
    </div>
</template>
<style scoped>
thead th {
    color: #e6e6e6;
}

thead,
th,
td {
    border: 1px solid #e6e6e6 !important;
}
</style>
